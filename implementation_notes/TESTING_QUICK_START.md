# 🚀 Quick Start - Testing Guide

## Step 1: Create Test Data

```bash
php artisan db:seed --class=TestDataSeeder
```

**Output will show:**
- ✅ Test merchant credentials
- ✅ Test API keys
- ✅ Live API keys (for mode mismatch testing)

## Step 2: Login to Dashboard

```
URL: http://localhost/login
Email: test@merchant.com
Password: password123
```

## Step 3: Verify Test Mode

Look for **yellow badge** "TEST MODE" in:
- Top-right corner
- Sidebar header

## Step 4: Test Basic Features

### In Dashboard (UI Testing)

1. **View Test Orders**
   - Navigate to "Orders" in sidebar
   - Should see 10 test orders

2. **View Test Transactions**
   - Navigate to "Transactions"
   - Should see test transaction data

3. **Create Payment Link**
   - Go to "Payment Links" → Click "Create"
   - Fill form and create
   - Copy link and test payment

4. **Process Refund**
   - Go to "Refunds"
   - Select a successful transaction
   - Create partial or full refund

5. **Check Settlements**
   - Navigate to "Settlements"
   - View pending settlement batches

### Via API (API Testing)

Get your test API key from seeder output or dashboard.

**Test 1: Create Payment**
```bash
curl -X POST http://localhost/api/v1/payment \
  -H "X-API-Key: pk_test_YOUR_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "currency": "INR",
    "payment_method": "upi",
    "customer_details": {
      "name": "Test Customer",
      "email": "test@example.com",
      "phone": "+919876543210"
    },
    "description": "Test payment"
  }'
```

**Test 2: Get Orders**
```bash
curl -X GET http://localhost/api/v1/orders \
  -H "X-API-Key: pk_test_YOUR_KEY_HERE"
```

**Test 3: Get Transactions**
```bash
curl -X GET http://localhost/api/v1/transactions \
  -H "X-API-Key: pk_test_YOUR_KEY_HERE"
```

## Step 5: Test Mode Mismatch (Important!)

### Test A: Live Key in Test Mode ❌

```bash
# This should FAIL with 403 error
curl -X GET http://localhost/api/v1/orders \
  -H "X-API-Key: pk_live_YOUR_LIVE_KEY_HERE"
```

**Expected Error:**
```json
{
  "error": "API key mode mismatch",
  "message": "Your merchant account is in TEST MODE. Test mode API key (pk_test_...) is required.",
  "merchant_mode": "test",
  "api_key_mode": "live"
}
```

### Test B: Test Key in Live Mode ❌

1. Switch merchant to **LIVE MODE** in dashboard (toggle in top-right)
2. Try using test API key:

```bash
# This should FAIL with 403 error
curl -X GET http://localhost/api/v1/orders \
  -H "X-API-Key: pk_test_YOUR_TEST_KEY_HERE"
```

**Expected Error:**
```json
{
  "error": "API key mode mismatch",
  "message": "Your merchant account is in LIVE MODE. Live mode API key (pk_live_...) is required.",
  "merchant_mode": "live",
  "api_key_mode": "test"
}
```

## Step 6: Test Mode Switching

1. **In TEST Mode:**
   - View orders → Should see test orders
   - Note the count

2. **Switch to LIVE Mode:**
   - Click "LIVE" toggle in top-right
   - View orders → Should be empty (or different data)
   - Badge should be **green** "LIVE MODE"

3. **Switch back to TEST Mode:**
   - Click "TEST" toggle
   - Test orders reappear

## Test Card Numbers

Use these for payment testing:

| Card Number | Result |
|-------------|--------|
| 4111 1111 1111 1111 | ✅ Success |
| 5555 5555 5555 4444 | ✅ Success |
| 4000 0000 0000 0002 | ❌ Failed |
| 4000 0000 0000 9995 | ❌ Insufficient Funds |

**CVV:** Any 3 digits (e.g., 123)  
**Expiry:** Any future date (e.g., 12/25)

## Test UPI IDs

- `success@upi` → Payment Success
- `failure@upi` → Payment Failed
- `pending@upi` → Payment Pending

## Expected Results Summary

### ✅ Should Work:
- Test API key + TEST mode → Shows test data
- Live API key + LIVE mode → Shows live data
- Mode switching in dashboard
- Creating orders, refunds, settlements in test mode

### ❌ Should Fail (403 Error):
- Test API key + LIVE mode
- Live API key + TEST mode
- Accessing test orders with live key
- Accessing live orders with test key

## Verification Checklist

- [ ] Test data seeder runs successfully
- [ ] Can login with test credentials
- [ ] Can see test data in dashboard
- [ ] Test mode badge shows correctly
- [ ] Can create test payments via API
- [ ] Mode mismatch returns 403 error
- [ ] Test key only shows test data
- [ ] Live key shows 403 in test mode
- [ ] Mode switching works in dashboard
- [ ] Test orders disappear when switching to live mode

## Need More Details?

See full documentation: `docs/TESTING_GUIDE.md`

---

**Happy Testing! 🎉**


