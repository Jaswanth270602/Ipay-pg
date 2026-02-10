# Security & PCI Compliance Guide

## ⚠️ Important Security Notice

**BadliCash is a demo/MVP application.** Before deploying to production and handling real payment data, you **MUST**:

1. Complete a full security audit
2. Achieve PCI DSS compliance
3. Implement all security best practices outlined below
4. Never store sensitive cardholder data (PAN, CVV, etc.)

---

## Table of Contents

1. [PCI DSS Overview](#pci-dss-overview)
2. [Tokenization Strategy](#tokenization-strategy)
3. [Data Security](#data-security)
4. [Network Security](#network-security)
5. [Access Control](#access-control)
6. [Logging & Monitoring](#logging--monitoring)
7. [Security Checklist](#security-checklist)
8. [Compliance Requirements](#compliance-requirements)

---

## PCI DSS Overview

### What is PCI DSS?

The Payment Card Industry Data Security Standard (PCI DSS) is a set of security standards designed to ensure that all companies that accept, process, store, or transmit credit card information maintain a secure environment.

### Compliance Levels

BadliCash, as a payment gateway, falls under **PCI DSS Level 1** (processes over 6 million transactions annually) or **Level 2** (1-6 million transactions).

### Self-Assessment Questionnaires (SAQ)

- **SAQ A**: For merchants who outsource all cardholder data functions
- **SAQ A-EP**: For e-commerce merchants with partial outsourcing
- **SAQ D**: For merchants who store, process, or transmit cardholder data

**Recommendation**: Aim for SAQ A by never storing cardholder data and using tokenization.

---

## Tokenization Strategy

### Current Implementation

BadliCash **DOES NOT** store full PAN (Primary Account Number) or CVV. Instead:

```php
// NEVER do this:
$card_number = '4111111111111111'; // ❌ Storing full PAN

// ALWAYS do this:
$payment_details = [
    'card_type' => 'visa',
    'last4' => '1111',          // Only last 4 digits
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'card_holder' => 'John Doe',
    // NO CVV, NO full PAN stored
];
```

### Payment Details Sanitization

The `PaymentService` automatically sanitizes payment details:

```php
protected function sanitizePaymentDetails(array $paymentData): array
{
    $sanitized = $paymentData;
    
    // Remove sensitive fields
    unset($sanitized['card_number'], $sanitized['cvv'], $sanitized['pin']);
    
    // Keep only last 4 digits if card number was provided
    if (isset($paymentData['card_number'])) {
        $sanitized['last4'] = substr($paymentData['card_number'], -4);
    }
    
    return $sanitized;
}
```

### Token Storage

Instead of storing actual card data:

1. **Collect** card data on the frontend (use PCI-compliant forms)
2. **Send** directly to the bank/payment processor
3. **Receive** a token from the processor
4. **Store** only the token and metadata (last4, expiry)

---

## Data Security

### Encryption at Rest

**Database Fields** that contain sensitive data MUST be encrypted:

```php
// Use Laravel's encryption for sensitive JSON fields
$merchant->update([
    'webhook_secret' => encrypt($secret),
    'bank_credentials' => encrypt($credentials),
]);
```

**Recommended**: Use database-level encryption (MySQL Enterprise Encryption, AWS RDS encryption).

### Encryption in Transit

**All communication MUST use TLS 1.2+**:

- API endpoints: `https://` only
- Database connections: SSL/TLS
- Redis connections: TLS enabled
- Webhook delivery: HTTPS only

**Implementation**:

```nginx
# In nginx config
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_prefer_server_ciphers on;
```

### Environment Variables

**NEVER commit secrets to git**:

```bash
# .env (DO NOT commit)
APP_KEY=base64:...
DB_PASSWORD=...
BANK_PROVIDER_API_SECRET=...
```

**Use**:
- AWS Secrets Manager
- HashiCorp Vault
- Kubernetes Secrets
- Azure Key Vault

### Key Management

**API Keys**:
- Generate with cryptographically secure random: `Str::random(32)`
- Hash secrets before storage (not done in MVP—implement before production)
- Rotate keys regularly (every 90 days)
- Revoke compromised keys immediately

---

## Network Security

### Firewall Rules

```
Allow:
- Port 443 (HTTPS) from internet
- Port 22 (SSH) from specific IPs only
- Port 3306 (MySQL) from app servers only
- Port 6379 (Redis) from app servers only

Deny:
- All other inbound traffic
```

### VPC / Network Segmentation

```
Public Subnet:
- Load Balancer
- Bastion Host

Private Subnet:
- Application Servers
- Database Servers
- Redis Servers

No Direct Internet Access for Private Subnet
```

### DDoS Protection

- Use CloudFlare, AWS Shield, or similar
- Implement rate limiting (already in place)
- Monitor for unusual traffic patterns

---

## Access Control

### Authentication

**API Authentication**:
- API key in `X-API-Key` header
- Validate key on every request
- Check key status (active/revoked)
- Rate limit per key

**Web Authentication**:
- Laravel Sanctum for session-based auth
- Strong password requirements (min 12 chars, complexity)
- Multi-factor authentication (TODO: implement before production)

### Authorization

**Role-Based Access Control (RBAC)**:

```php
// Already implemented
Gate::define('admin-access', function (User $user) {
    return $user->role->name === 'admin';
});

// Policy-based authorization
$this->authorize('view', $transaction);
```

**Principle of Least Privilege**:
- Merchants can only access their own data
- Admins have full access but actions are logged
- API keys have specific scopes (implement before production)

### Password Security

```php
// Use bcrypt or Argon2
Hash::make($password, [
    'rounds' => 12, // Increase for production
]);

// Implement password policies:
- Minimum 12 characters
- Mix of uppercase, lowercase, numbers, symbols
- Password expiry (every 90 days for sensitive accounts)
- No password reuse (last 5 passwords)
```

---

## Logging & Monitoring

### What to Log

✅ **DO Log**:
- Authentication attempts (success/failure)
- API key usage
- Payment transactions (without sensitive data)
- Refund requests
- Admin actions
- Security events (failed auth, rate limit exceeded)

❌ **DO NOT Log**:
- Full PAN
- CVV
- Full API keys/secrets
- Passwords

### Log Masking

```php
// Mask sensitive data in logs
Log::info('Payment processed', [
    'transaction_id' => $txn->txn_id,
    'amount' => $txn->amount,
    'card_last4' => $txn->payment_details['last4'] ?? 'N/A',
    // NO full card number, NO CVV
]);
```

### Audit Logs

BadliCash includes audit logging:

```php
AuditLog::logAction(
    'payment.created',
    'Order',
    $order->id,
    [
        'amount' => $order->amount,
        'merchant_id' => $order->merchant_id,
    ]
);
```

**Retention**: Keep audit logs for at least 1 year (PCI requirement).

### Monitoring

**Implement**:
- Real-time alerts for:
  - Failed payment spikes
  - Unusual refund patterns
  - Multiple failed auth attempts
  - API rate limit violations
- Log aggregation (ELK Stack, Splunk, Datadog)
- Uptime monitoring (Pingdom, UptimeRobot)
- Performance monitoring (New Relic, Scout APM)

---

## Security Headers

**Already Implemented** in `nginx.conf`:

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "..." always;
```

**Additional Recommendations**:
- Strict-Transport-Security (HSTS)
- Permissions-Policy

---

## Security Checklist

### Pre-Production

- [ ] Complete security audit by certified firm
- [ ] Penetration testing
- [ ] Vulnerability scanning (OWASP ZAP, Nessus)
- [ ] Code review focusing on security
- [ ] Implement MFA for admin accounts
- [ ] Set up secrets management (Vault, AWS Secrets)
- [ ] Configure TLS certificates (Let's Encrypt, DigiCert)
- [ ] Enable database encryption at rest
- [ ] Set up WAF (Web Application Firewall)
- [ ] Implement IP whitelisting for admin access
- [ ] Configure SIEM (Security Information and Event Management)

### Ongoing

- [ ] Regular security patches and updates
- [ ] Quarterly security audits
- [ ] Annual PCI DSS compliance validation
- [ ] Key rotation (every 90 days)
- [ ] Review access logs weekly
- [ ] Test backup/restore procedures monthly
- [ ] Incident response drills quarterly

---

## Compliance Requirements

### PCI DSS 12 Requirements

1. **Install and maintain a firewall** ✅ Configured in docker/nginx
2. **No default passwords** ✅ All credentials configurable
3. **Protect stored cardholder data** ✅ Tokenization, no PAN storage
4. **Encrypt transmission** ⚠️ TLS required (configure in production)
5. **Use and update antivirus** ⚠️ Server-level requirement
6. **Develop secure systems** ✅ Following best practices
7. **Restrict access by business need** ✅ RBAC implemented
8. **Unique IDs for access** ✅ User-based authentication
9. **Restrict physical access** ⚠️ Data center requirement
10. **Track access to network resources** ✅ Audit logs implemented
11. **Test security systems** ⚠️ Requires regular pen testing
12. **Maintain information security policy** ⚠️ Document and enforce

### GDPR Compliance (if applicable)

- Right to access: Provide customer data on request
- Right to deletion: Implement data deletion endpoints
- Data portability: Export customer data in standard format
- Privacy by design: Minimize data collection
- Breach notification: Within 72 hours

---

## Incident Response

### In Case of Security Breach

1. **Immediate Actions**:
   - Isolate affected systems
   - Revoke compromised credentials
   - Enable additional logging
   - Notify security team

2. **Investigation**:
   - Review audit logs
   - Identify scope of breach
   - Document timeline
   - Preserve evidence

3. **Notification**:
   - Notify affected customers (within legal timeframes)
   - Report to PCI Council (if cardholder data compromised)
   - Inform regulatory authorities (GDPR, etc.)

4. **Remediation**:
   - Patch vulnerabilities
   - Reset all credentials
   - Review and update security policies
   - Conduct post-incident review

---

## Additional Resources

- [PCI DSS Standards](https://www.pcisecuritystandards.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks/)

---

## Contact

For security concerns or to report vulnerabilities:
- Email: security@badlicash.com
- Use PGP key for sensitive communications

**Bug Bounty Program**: TBD

---

**Remember**: Security is not a one-time implementation but an ongoing process. Stay vigilant, keep updated, and prioritize security in every decision.

