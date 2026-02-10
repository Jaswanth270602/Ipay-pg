# Badilicash Payment Gateway

**Badilicash** is a production-ready payment gateway MVP built with Laravel 10, Blade templates, and AngularJS (via CDN). It provides a complete payment processing solution similar to Cashfree, with features for merchants, admins, and end users.

## 🚀 Features

- **Payment Processing**: Accept payments via card, netbanking, UPI, and wallets
- **Payment Links**: Generate shareable payment links with expiry and usage limits
- **Order Management**: Track orders and their payment status
- **Transaction Processing**: Handle payments with fee calculation and settlements
- **Refunds**: Full and partial refund support
- **Settlements & Payouts**: Batch settlement processing for merchants
- **Webhooks**: Reliable webhook delivery with automatic retry logic
- **API Key Management**: Secure API keys with scopes and rate limiting
- **Test & Live Modes**: Separate sandbox and production environments
- **Role-Based Access**: Admin, merchant, and user roles with policy-based permissions
- **Audit Logs**: Complete audit trail for all payment actions
- **Reports & Analytics**: Generate reports with CSV export
- **Admin Dashboard**: Manage merchants, view system stats, and control the platform

## 🏗️ Architecture

- **Backend**: Laravel 10 with PHP 8.1+
- **Database**: MySQL 8 with migrations and foreign keys
- **Queue System**: Redis for async webhook delivery and background jobs
- **Authentication**: Laravel Sanctum for API and web auth
- **Frontend**: Blade templates (main engine) + AngularJS 1.8 (via CDN for interactivity)
- **UI Framework**: Bootstrap 5 (via CDN)
- **Containerization**: Docker with docker-compose for local development

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- MySQL 8.0
- Redis
- Docker & Docker Compose (for containerized setup)

## 🛠️ Installation

### Using Docker (Recommended)

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd gateway
   ```

2. **Copy environment file**:
   ```bash
   cp env.example .env
   ```

3. **Update `.env` with your configuration**:
   ```env
   DB_DATABASE=Badilicash
   DB_USERNAME=Badilicash
   DB_PASSWORD=secret
   Badilicash_MODE=test
   ```

4. **Build and start containers**:
   ```bash
   docker-compose up -d --build
   ```

5. **Install dependencies**:
   ```bash
   docker-compose exec app composer install
   ```

6. **Generate application key**:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

7. **Run migrations and seed the database**:
   ```bash
   docker-compose exec app php artisan migrate --seed
   ```

8. **Access the application**:
   - Web: http://localhost:8000
   - MySQL: localhost:3306
   - Redis: localhost:6379

### Manual Installation

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Configure environment**:
   ```bash
   cp env.example .env
   php artisan key:generate
   ```

3. **Configure database in `.env`**

4. **Run migrations and seeders**:
   ```bash
   php artisan migrate --seed
   ```

5. **Start services**:
   ```bash
   # Terminal 1: Application
   php artisan serve

   # Terminal 2: Queue worker
   php artisan queue:work

   # Terminal 3: Scheduler (if needed)
   php artisan schedule:work
   ```

## 👤 Test Credentials

After seeding, use these credentials to login:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@Badilicash.test | Password123! |
| Merchant 1 | merchant1@Badilicash.test | Password123! |
| Merchant 2 | merchant2@Badilicash.test | Password123! |

## 🔑 API Usage

### Authentication

Use API keys for authentication. You can find generated API keys in the `api_keys` table after seeding.

```bash
curl -X POST http://localhost:8000/api/v1/payment \
  -H "X-API-Key: your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "currency": "USD",
    "payment_method": "card",
    "customer_details": {
      "name": "John Doe",
      "email": "john@example.com"
    }
  }'
```

### Idempotency

Include an `idempotency_key` to prevent duplicate charges:

```json
{
  "amount": 100.00,
  "idempotency_key": "unique-request-id-12345"
}
```

## 🧪 Testing

Run PHPUnit tests:

```bash
# Using Docker
docker-compose exec app php artisan test

# Manual
php artisan test
```

Test specific suites:

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📚 Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[API.md](docs/API.md)**: Complete API reference with examples
- **[SECURITY.md](docs/SECURITY.md)**: Security best practices and PCI compliance guide
- **[DEPLOYMENT.md](docs/DEPLOYMENT.md)**: Production deployment checklist
- **[SEEDING.md](docs/SEEDING.md)**: Database seeding documentation

## 🔒 Security Features

- ✅ **No PAN/CVV Storage**: Only tokenized data is stored
- ✅ **TLS Required**: All endpoints require HTTPS in production
- ✅ **CSRF Protection**: Built-in Laravel CSRF protection
- ✅ **HMAC Signatures**: Webhook signature verification
- ✅ **Rate Limiting**: Per-API-key rate limits
- ✅ **Encrypted Secrets**: Sensitive data encrypted at rest
- ✅ **Audit Logging**: Complete audit trail
- ✅ **Security Headers**: X-Frame-Options, CSP, etc.

**⚠️ Important**: This is a demo application. See [SECURITY.md](docs/SECURITY.md) for production requirements.

## 🎯 Key Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/payment` | Create payment |
| GET | `/api/v1/orders/{id}` | Get order details |
| GET | `/api/v1/transactions` | List transactions |
| POST | `/api/v1/refunds` | Create refund |
| POST | `/api/v1/payment_links` | Create payment link |
| GET | `/api/v1/payment_links` | List payment links |

## 🔄 Webhook Events

Badilicash sends webhooks for the following events:

- `payment.created`
- `payment.success`
- `payment.failed`
- `refund.created`

Webhook payloads include an `X-Badilicash-Signature` header for verification.

## 📊 Database Schema

The system uses a normalized database schema with:

- **13 Core Tables**: users, roles, merchants, api_keys, banks, orders, transactions, refunds, settlements, payment_links, webhook_events, audit_logs, payouts
- **Foreign Keys**: Proper relationships with cascade deletes
- **Indexes**: Optimized for common queries (merchant_id, status, created_at)

## 🚦 Test vs Live Mode

Each merchant can operate in two modes:

- **Test Mode**: Uses `SandboxBankProvider` for simulated payments
- **Live Mode**: Uses `DummyBankApi` (replace with real bank integration)

Switch modes via the `test_mode` flag in the `merchants` table.

### API Keys per Mode

- API keys include their mode in the prefix: `pk_test_...` / `pk_live_...` and `sk_test_...` / `sk_live_...`.
- Test mode uses sandbox processing; live mode uses production provider (configure credentials in merchant settings or `.env`).
- Middleware attaches `api_key_mode` to the request for downstream logic.

### Subscriptions & Plans (Merchant Access)

- Merchants can now view active plans and create/manage their own subscriptions at `Merchant > Subscriptions`.
- Subscriptions created from the merchant portal automatically inherit the merchant's current mode (`test_mode`).
- Admins continue to manage global plan definitions and view all subscriptions.

### Payment Links UX Fixes

- The create payment link modal controller has been rewritten to avoid Angular digest/timeout issues and to prevent duplicate submissions.
- Checkout payment method selection reliably enables the Pay button and carries the selected method to the server.

### Developer Notes

#### Recent Fixes and Improvements

**1. Subscriptions & Plans System**
- **Merchant Access**: Merchants can now view all active plans and create/manage subscriptions from the merchant portal (`/merchant/subscriptions`)
- **Test Mode Handling**: Subscriptions automatically inherit the merchant's `test_mode` setting when created
- **Model Updates**: 
  - `Subscription` model now includes `test_mode` in fillable and casts
  - `Merchant` model includes `subscriptions()` relationship
- **Controllers**: Both merchant and admin controllers properly handle test mode when creating subscriptions
- **Database**: Migration `2025_11_06_000201_add_test_mode_to_subscriptions_table.php` adds test_mode column

**2. API Keys - Test vs Live Mode**
- **Separation**: Test and live mode API keys are completely separate
  - Test keys: `pk_test_...` / `sk_test_...`
  - Live keys: `pk_live_...` / `sk_live_...`
- **Key Generation**: The `ApiKey::generate()` method creates keys with mode-specific prefixes
- **Middleware**: `AuthenticateApiKey` middleware extracts the mode from the API key and attaches it to the request as `api_key_mode`
- **Usage**: Controllers use `$request->get('api_key_mode')` to determine the effective mode, overriding merchant's default test_mode if needed
- **Seeder**: Updated to always create both test and live keys for all merchants (previously only created live keys for non-test merchants)

**3. Payment Link Creation Modal - Angular Controller Fixes**
- **Issues Fixed**:
  - Modal always showing "Creating..." state
  - Missing `initModal()`, `applyFilters()`, and `clearFilters()` methods
  - Angular digest cycle not being triggered properly
  - Form not resetting after successful creation
- **Solutions Implemented**:
  - Added `$timeout` and `$scope` to controller dependencies for proper digest handling
  - Implemented `initModal()` to reset form when modal opens
  - Added `applyFilters()` and `clearFilters()` for filter management
  - Fixed `getPaginationPages()` method (was `getPages()` in template)
  - Improved error handling with proper validation messages
  - Added proper Bootstrap 5 modal instance management
  - Fixed form submission to prevent default and handle errors gracefully

**4. Payment Checkout Page - Payment Method Selection**
- **Issues Fixed**:
  - Clicking on payment method cards (Card/UPI) didn't enable the Pay button
  - Payment method selection not being captured properly
  - Form validation not working correctly
- **Solutions Implemented**:
  - Replaced inline `onclick` handlers with proper event listeners
  - Added proper initialization in DOM ready handler
  - Fixed hidden input value updates with event triggering
  - Improved form validation with visual feedback
  - Added error highlighting for payment method section
  - Enhanced accessibility with proper ARIA labels and keyboard support
  - Fixed button state management (disabled/enabled)

**5. Code Quality Improvements**
- **Error Handling**: Improved error messages and validation feedback throughout
- **Angular Best Practices**: Proper use of `$timeout` for digest cycles, dependency injection
- **JavaScript**: Modern event handling, proper DOM manipulation
- **Accessibility**: Added ARIA labels, keyboard navigation support

#### Migration Requirements

Ensure all migrations are up to date:
```bash
php artisan migrate
```

Key migrations:
- `2025_11_05_000100_create_plans_table.php` - Plans table
- `2025_11_05_000101_create_subscriptions_table.php` - Subscriptions table
- `2025_11_06_000201_add_test_mode_to_subscriptions_table.php` - Test mode support

#### Test Mode vs Live Mode

- **Test Mode**: Uses `SandboxBankProvider` for simulated payments (always works)
- **Live Mode**: Requires real bank API integration (currently uses `DummyBankApi` as placeholder)
- **API Keys**: Each merchant has separate test and live API keys
- **Subscriptions**: Inherit merchant's test_mode when created
- **Payment Links**: Inherit merchant's test_mode when created

#### Frontend Architecture Notes

- **Blade + Angular Pattern**: Blade renders initial HTML, Angular handles interactivity
- **Angular Controllers**: Located in `public/js/` matching view folder structure
- **Digest Cycles**: Use `$timeout` when updating scope outside Angular context
- **Bootstrap 5**: Modal instances must be properly managed (getInstance vs new Modal)
- **Event Handling**: Prefer `addEventListener` over inline `onclick` for better control

#### Common Issues and Solutions

**Issue**: Payment link modal stuck on "Creating..."
- **Solution**: Ensure Angular controller has `initModal()` method and proper digest cycle handling

**Issue**: Payment method selection not working on checkout page
- **Solution**: Check that event listeners are properly attached in DOM ready handler

**Issue**: Subscriptions not creating
- **Solution**: Verify `test_mode` is in Subscription model fillable array and migration is run

**Issue**: API keys not working in live mode
- **Solution**: Ensure live mode API keys exist and are active. Check middleware is extracting mode correctly.

## 🛠️ Development

### Queue Workers

Process webhooks and background jobs:

```bash
php artisan queue:work
```

### Scheduled Tasks

Run the scheduler for periodic tasks:

```bash
php artisan schedule:work
```

Or add to cron:

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Refresh Database

To reset and reseed the database:

```bash
php artisan migrate:fresh --seed
```

## 📦 Project Structure

```
gateway/
├── app/
│   ├── Events/              # Payment events
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/         # API controllers
│   │   │   ├── Merchant/    # Merchant web controllers
│   │   │   └── Admin/       # Admin web controllers
│   │   ├── Middleware/      # Auth & API key middleware
│   │   └── Policies/        # Authorization policies
│   ├── Jobs/                # Queue jobs (webhooks)
│   ├── Listeners/           # Event listeners
│   ├── Models/              # Eloquent models
│   └── Services/            # Business logic services
├── config/
│   └── Badilicash.php        # Custom configuration
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Data seeders
├── docs/                    # Documentation
├── public/
│   └── js/                  # Angular controllers
├── resources/
│   └── views/               # Blade templates
├── routes/
│   ├── api.php              # API routes
│   └── web.php              # Web routes
├── tests/                   # PHPUnit tests
├── docker/                  # Docker configs
├── docker-compose.yml       # Docker services
└── Dockerfile               # App container
```

## 🌐 Frontend (Blade + Angular Pattern)

The frontend uses **Blade as the main view engine** with **AngularJS (via CDN)** for interactivity:

- Blade renders the page structure and includes partials
- Angular (1.8) handles two-way binding, AJAX, and pagination
- Each view folder has a corresponding Angular controller in `public/js/`

Example:
- `resources/views/merchant/paymentlinks/index.blade.php` (Blade)
- `public/js/paymentlinks/mainController.js` (Angular)

## 🐛 Troubleshooting

### Port Already in Use

If port 8000 is in use:

```bash
docker-compose down
docker-compose up -d
```

Or change ports in `docker-compose.yml`.

### Permission Issues

Fix storage permissions:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Failed

Ensure MySQL is running and credentials in `.env` match your setup.

## 🤝 Contributing

This is a demo project. For production use, ensure:

1. Real bank API integration
2. PCI DSS compliance assessment
3. Full security audit
4. Load testing
5. Proper monitoring and alerting

## 📄 License

MIT License

## 📧 Support

For issues and questions, please open an issue in the repository.

---

**Built with ❤️ using Laravel, Blade, and AngularJS**

