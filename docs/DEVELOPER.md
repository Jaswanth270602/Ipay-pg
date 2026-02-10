# BadliCash Developer Guide

## Modes: Test vs Live
- Test uses `SandboxBankProvider`
- Live uses `ProductionBankProvider`
- Binding happens in `AppServiceProvider` based on `merchant.test_mode`

## Payment Widget (Checkout)
Include SDK in your page:

```html
<script src="/sdk/badlicash.js"></script>
<button id="pay">Pay</button>
<script>
document.getElementById('pay').onclick=function(){
  var checkout = new BadliCash.Checkout({
    key: 'pk_test_xxxxx',
    amount: 50000,
    currency: 'INR',
    prefill: { name:'John', email:'john@example.com', phone:'+919876543210' },
    link_token: 'lnk_XXXX' // optional if you already created a link
  });
  checkout.open();
};
</script>
```

## Create Payment Link via API

```bash
POST /api/v1/payment_links
Authorization: Bearer sk_test_xxx
{
  "amount": 50000,
  "currency": "INR",
  "description": "Order #123"
}
```

Response contains `link_token`; open `/pay/{link_token}`.

## PCI Guidance
- Do not store full PAN or CVV
- Use tokenization and encrypt-at-rest
- Expose only last4 and brand in responses

## Webhooks
- Configure in Settings
- Verify `X-BadliCash-Signature` using HMAC with your webhook secret
