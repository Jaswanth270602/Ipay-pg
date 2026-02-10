# Payment Gateway Architecture

## Overview

BadliCash uses a clean, factory-based architecture to route payments to different acquirer accounts (CashFree, Razorpay, PayU, etc.) based on merchant configuration. This ensures complete isolation between different payment gateways.

## Architecture Components

### 1. PaymentGatewayInterface (`app/Contracts/PaymentGatewayInterface.php`)

Standardized interface that all payment gateway implementations must follow:

```php
interface PaymentGatewayInterface
{
    public function initialize($merchant, $acquirerAccount): self;
    public function createOrder(array $orderData): array;
    public function charge(array $paymentData): array;
    public function getPaymentStatus(string $paymentId): array;
    public function getGatewayName(): string;
    public function requiresFrontendSdk(): bool;
    public function getFrontendSdkConfig(): ?array;
}
```

### 2. GatewayFactory (`app/Services/PaymentGateways/GatewayFactory.php`)

Routes payment requests to the appropriate gateway based on merchant's acquirer configuration:

```php
$gateway = GatewayFactory::make($merchant, $acquirerAccount);
$result = $gateway->charge($paymentData);
```

**Supported Gateways:**
- `cashfree` - CashFree payment gateway
- `razorpay` - Razorpay payment gateway

### 3. Gateway Services

#### CashfreeGatewayService (`app/Services/PaymentGateways/CashfreeGatewayService.php`)

- **Processing**: Server-side (no frontend SDK)
- **Payment Flow**: 
  1. Create CashFree order
  2. Initiate payment with card details
  3. Return payment result
- **Response Format**:
  ```json
  {
    "success": true,
    "gateway": "cashfree",
    "transaction_id": "TXN_XXX",
    "status": "success",
    "gateway_payment_id": "CF_PAYMENT_XXX",
    "message": "Payment processed successfully through CashFree"
  }
  ```

#### RazorpayGatewayService (`app/Services/PaymentGateways/RazorpayGatewayService.php`)

- **Processing**: Frontend SDK (Checkout.js)
- **Payment Flow**:
  1. Create Razorpay order
  2. Return order details for Checkout.js
  3. Frontend handles payment collection
- **Response Format**:
  ```json
  {
    "success": true,
    "gateway": "razorpay",
    "use_razorpay_checkout": true,
    "razorpay_key": "rzp_test_XXX",
    "razorpay_order_id": "order_XXX",
    "amount": 10000,
    "currency": "INR"
  }
  ```

## Payment Flow

### CashFree Flow

1. **Merchant Configuration**: Merchant has CashFree acquirer account assigned
2. **Payment Request**: Customer submits payment with card details
3. **Gateway Detection**: `GatewayFactory` detects CashFree from acquirer name
4. **Order Creation**: CashFree order is created via `CashfreeGatewayService`
5. **Payment Processing**: Payment is processed server-side through CashFree API
6. **Response**: Standardized response with `gateway: "cashfree"` (NO Razorpay fields)

### Razorpay Flow

1. **Merchant Configuration**: Merchant has Razorpay acquirer account assigned
2. **Payment Request**: Customer submits payment (card details collected by Checkout.js)
3. **Gateway Detection**: `GatewayFactory` detects Razorpay from acquirer name
4. **Order Creation**: Razorpay order is created via `RazorpayGatewayService`
5. **Frontend SDK**: Razorpay Checkout.js modal opens for payment collection
6. **Response**: Response includes `use_razorpay_checkout: true` and Razorpay keys

## Key Principles

### ✅ Gateway Isolation

- **CashFree flow** has ZERO Razorpay references
- **Razorpay flow** has ZERO CashFree references
- Each gateway service is completely independent

### ✅ Standardized Responses

All gateways return responses with:
- `success`: boolean
- `gateway`: string (gateway name)
- `status`: string (success/failed/pending)
- `message`: string (human-readable message)

### ✅ Frontend Handling

Frontend checks `result.gateway` to determine which flow to use:

```javascript
if (result.gateway === 'razorpay' && result.use_razorpay_checkout) {
    // Open Razorpay Checkout.js
} else if (result.gateway === 'cashfree') {
    // Show success message (server-side processing complete)
}
```

## Adding New Gateways

To add a new payment gateway:

1. **Create Gateway Service**: Implement `PaymentGatewayInterface`
   ```php
   class NewGatewayService implements PaymentGatewayInterface
   {
       // Implement all interface methods
   }
   ```

2. **Register in GatewayFactory**: Add gateway detection logic
   ```php
   if (stripos($acquirerName, 'newgateway') !== false) {
       $gateway = new NewGatewayService();
       $gateway->initialize($merchant, $acquirerAccount);
       return $gateway;
   }
   ```

3. **Update Controller**: Add handling in `PaymentCheckoutController`
   ```php
   elseif ($gatewayName === 'newgateway') {
       // Handle new gateway response
   }
   ```

## Testing

### Test CashFree Flow

1. Create merchant with CashFree acquirer account
2. Create payment link
3. Submit payment with card details
4. Verify:
   - Response contains `gateway: "cashfree"`
   - NO Razorpay fields in response
   - Payment appears in CashFree dashboard
   - Transaction status is correct

### Test Razorpay Flow

1. Create merchant with Razorpay acquirer account
2. Create payment link
3. Submit payment (card details collected by Checkout.js)
4. Verify:
   - Response contains `gateway: "razorpay"`
   - Razorpay Checkout.js modal opens
   - Payment appears in Razorpay dashboard

## Files Modified

- `app/Contracts/PaymentGatewayInterface.php` - Interface definition
- `app/Services/PaymentGateways/GatewayFactory.php` - Gateway routing
- `app/Services/PaymentGateways/CashfreeGatewayService.php` - CashFree implementation
- `app/Services/PaymentGateways/RazorpayGatewayService.php` - Razorpay implementation
- `app/Http/Controllers/PaymentCheckoutController.php` - Payment processing logic
- `resources/views/checkout/payment.blade.php` - Frontend payment handling

## Notes

- **Razorpay SDK is NOT disturbed** - All Razorpay functionality remains intact
- **CashFree is completely isolated** - No Razorpay references in CashFree flow
- **Easy to extend** - Adding new gateways is straightforward with the factory pattern

