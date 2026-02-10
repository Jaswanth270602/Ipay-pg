# Razorpay Acquirer Integration - Complete Implementation

## Overview

This document describes the complete Razorpay integration implemented using the Acquirer Adapter pattern. The integration supports all Razorpay functionalities including payments, payment links, UPI, cards, netbanking, wallets, settlements, and refunds.

## Architecture

### Adapter Pattern

The system uses an **Adapter/Strategy design pattern** for acquirer integrations:

1. **AcquirerInterface** - Base contract that all acquirer adapters must implement
2. **RazorpayAcquirerAdapter** - Razorpay-specific implementation
3. **AcquirerResolver** - Resolves and initializes the correct adapter based on AcquirerAccount
4. **AcquirerCallbackController** - Unified callback handler for all acquirers

### Key Components

#### 1. AcquirerInterface (`app/Services/Acquirers/AcquirerInterface.php`)

Defines the contract for all acquirer adapters with methods for:
- Order creation
- Payment initiation
- Payment verification
- Refund processing
- Webhook signature verification
- Event normalization
- Status normalization
- Payment links
- Settlements

#### 2. RazorpayAcquirerAdapter (`app/Services/Acquirers/RazorpayAcquirerAdapter.php`)

Complete Razorpay implementation supporting:
- ✅ Order creation
- ✅ Payment initiation (Cards, UPI, Netbanking, Wallets)
- ✅ Payment verification with signature validation
- ✅ Refund processing
- ✅ Payment links
- ✅ Settlements
- ✅ Webhook signature verification
- ✅ Event type normalization
- ✅ Status normalization

#### 3. AcquirerResolver (`app/Services/Acquirers/AcquirerResolver.php`)

Service that:
- Resolves adapter class from AcquirerAccount
- Initializes adapter with credentials
- Detects provider from webhook payloads
- Supports multiple providers (extensible)

#### 4. AcquirerCallbackController (`app/Http/Controllers/Api/AcquirerCallbackController.php`)

Unified callback handler that:
- Detects provider automatically
- Verifies webhook signatures
- Routes to appropriate adapter
- Stores all callbacks in `provider_responses` table
- Processes events (payment, refund, settlement, dispute)
- Updates transaction/order/refund status

#### 5. ProviderResponse Model (`app/Models/ProviderResponse.php`)

Stores all provider callbacks with:
- Provider name
- Event type (normalized)
- Raw payload
- Reference IDs (payment_id, order_id, refund_id, etc.)
- Signature verification status
- Processing status

## Database Schema

### provider_responses Table

Stores all acquirer callbacks/webhooks:

```sql
- id
- provider (razorpay, paytm, etc.)
- acquirer_account_id (FK)
- event_type (payment.success, refund.created, etc.)
- provider_event_type (original provider event)
- raw_payload (JSON)
- normalized_status (success, failed, pending)
- provider_status (provider-specific status)
- payment_id, order_id, refund_id, settlement_id, dispute_id
- signature, signature_verified
- ip_address
- error_message
- processed, processed_at
- timestamps
```

## Configuration

### AcquirerAccount Setup

To use Razorpay, create an AcquirerAccount record with:

1. **acquirer_name**: `razorpay` or `razorpay_test` or `razorpay_live`
2. **mode**: `TEST` or `LIVE`
3. **additional_key_1**: Razorpay Key ID (e.g., `rzp_test_...` or `rzp_live_...`)
4. **additional_key_2**: Razorpay Key Secret
5. **is_active**: `true`

**Example:**
```
acquirer_name: razorpay_test
mode: TEST
additional_key_1: rzp_test_xxxxxxxxxxxxx
additional_key_2: xxxxxxxxxxxxxxxxxxxxxx
is_active: true
```

### Link Merchant to AcquirerAccount

Link merchants to acquirer accounts via the `acquirer_account_merchant` pivot table. This can be done through the admin interface.

## API Endpoints

### Unified Callback Endpoint

**POST** `/api/webhooks/acquirer`

Handles callbacks from all acquirer providers. Automatically:
- Detects provider (Razorpay, Paytm, etc.)
- Verifies signature
- Routes to appropriate adapter
- Processes event

**Razorpay Webhook URL:** `https://your-domain.com/api/webhooks/acquirer`

**Razorpay Webhook Headers:**
- `X-Razorpay-Signature`: Webhook signature

## Usage Examples

### 1. Creating a Payment Order

```php
use App\Services\Acquirers\AcquirerResolver;
use App\Models\AcquirerAccount;

$acquirerAccount = AcquirerAccount::where('acquirer_name', 'razorpay_test')
    ->where('is_active', true)
    ->first();

$resolver = app(AcquirerResolver::class);
$adapter = $resolver->resolve($acquirerAccount);

$orderResult = $adapter->createOrder([
    'order_id' => 'order_123',
    'amount' => 1000.00, // Amount in rupees
    'currency' => 'INR',
    'customer_details' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '9876543210',
    ],
    'metadata' => [
        'product_id' => 'prod_123',
    ],
]);
```

### 2. Initiating Payment

```php
$paymentResult = $adapter->initiatePayment([
    'order_id' => $orderResult['gateway_order_id'],
    'payment_method' => 'card',
    'card_number' => '4111111111111111',
    'cvv' => '123',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'card_holder' => 'John Doe',
]);
```

### 3. Creating Payment Link

```php
$linkResult = $adapter->createPaymentLink([
    'amount' => 1000.00,
    'currency' => 'INR',
    'description' => 'Payment for Product XYZ',
    'customer_details' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '9876543210',
    ],
    'notify' => [
        'sms' => true,
        'email' => true,
    ],
    'reminder_enable' => true,
]);
```

### 4. Processing Refund

```php
$refundResult = $adapter->processRefund(
    'pay_xxxxxxxxxxxxx', // Razorpay payment ID
    500.00, // Refund amount
    [
        'notes' => [
            'reason' => 'Customer request',
        ],
        'speed' => 'normal', // or 'optimum'
    ]
);
```

### 5. Getting Settlements

```php
$settlements = $adapter->getSettlements([
    'count' => 10,
    'skip' => 0,
]);
```

## Integration with PaymentService

The `PaymentService` has been updated to automatically use acquirer adapters when available:

1. Checks if merchant has an active AcquirerAccount
2. Resolves appropriate adapter
3. Falls back to legacy BankProvider if no acquirer account found
4. Creates order and initiates payment through adapter

## Webhook Processing Flow

1. **Callback Received**: `/api/webhooks/acquirer` receives webhook
2. **Provider Detection**: Automatically detects provider (Razorpay)
3. **Account Resolution**: Finds active AcquirerAccount for provider
4. **Adapter Resolution**: Resolves RazorpayAcquirerAdapter
5. **Signature Verification**: Verifies webhook signature
6. **Response Storage**: Stores in `provider_responses` table
7. **Event Processing**: Routes to appropriate handler:
   - `payment.success` → Updates transaction, triggers PaymentSuccess event
   - `payment.failed` → Updates transaction, triggers PaymentFailed event
   - `refund.created` → Updates refund, triggers RefundCreated event
   - `settlement.processed` → Handles settlement
   - `dispute.created` → Handles dispute

## Event Normalization

Razorpay events are normalized to gateway-level events:

| Razorpay Event | Gateway Event |
|----------------|---------------|
| payment.authorized | payment.authorized |
| payment.captured | payment.success |
| payment.failed | payment.failed |
| payment.pending | payment.pending |
| refund.created | refund.created |
| refund.processed | refund.success |
| order.paid | order.completed |
| settlement.processed | settlement.processed |
| dispute.created | dispute.created |
| dispute.resolved | dispute.resolved |

## Status Normalization

Razorpay statuses are normalized to gateway-level statuses:

| Razorpay Status | Gateway Status |
|-----------------|----------------|
| created | pending |
| authorized | authorized |
| captured | success |
| refunded | refunded |
| failed | failed |
| pending | pending |
| paid | success |
| attempted | pending |

## Testing

### Test Mode Setup

1. Create AcquirerAccount with:
   - `acquirer_name`: `razorpay_test`
   - `mode`: `TEST`
   - `additional_key_1`: Your Razorpay test key ID
   - `additional_key_2`: Your Razorpay test key secret

2. Link merchant to this acquirer account

3. Use Razorpay test credentials:
   - Test Cards: https://razorpay.com/docs/payments/test-cards/
   - Test UPI IDs: `success@razorpay`, `failure@razorpay`

### Webhook Testing

Use Razorpay Dashboard to send test webhooks:
1. Go to Settings → Webhooks
2. Add webhook URL: `https://your-domain.com/api/webhooks/acquirer`
3. Select events to receive
4. Test webhook delivery

## Security

- ✅ Webhook signature verification
- ✅ PCI-DSS compliant (card data sanitization)
- ✅ Secure credential storage in database
- ✅ IP address logging
- ✅ Error handling and logging

## Extensibility

To add a new acquirer:

1. Create adapter class implementing `AcquirerInterface`
2. Add to `AcquirerResolver::$adapterMap`
3. Implement provider detection in `AcquirerResolver::detectProvider()`
4. No changes needed to callback controller or other services

## Files Created/Modified

### New Files
- `app/Services/Acquirers/AcquirerInterface.php`
- `app/Services/Acquirers/RazorpayAcquirerAdapter.php`
- `app/Services/Acquirers/AcquirerResolver.php`
- `app/Http/Controllers/Api/AcquirerCallbackController.php`
- `app/Models/ProviderResponse.php`
- `database/migrations/2026_01_08_104726_create_provider_responses_table.php`

### Modified Files
- `app/Services/PaymentService.php` - Added acquirer adapter support
- `app/Models/Merchant.php` - Added acquirer accounts relationship
- `routes/api.php` - Added unified callback route
- `composer.json` - Added razorpay/razorpay package

## Next Steps

1. Run migration: `php artisan migrate`
2. Create Razorpay AcquirerAccount via admin interface
3. Link merchants to acquirer accounts
4. Configure Razorpay webhook URL in Razorpay Dashboard
5. Test payments, refunds, and webhooks

## Support

For Razorpay API documentation, visit: https://razorpay.com/docs/

For issues or questions, check the code comments in the adapter implementation.

