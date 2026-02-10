# BadliCash Payment Gateway - Testing Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Test vs Live Mode](#test-vs-live-mode)
3. [Setting Up Test Environment](#setting-up-test-environment)
4. [Test Data](#test-data)
5. [Testing Scenarios](#testing-scenarios)
6. [API Testing](#api-testing)
7. [Mode Mismatch Testing](#mode-mismatch-testing)
8. [Common Test Cases](#common-test-cases)

---

## Overview

This guide provides comprehensive instructions for testing the BadliCash Payment Gateway in both TEST and LIVE modes. The system enforces strict separation between test and live data to ensure security and data integrity.

---

## Test vs Live Mode

### Key Differences

| Feature | TEST Mode | LIVE Mode |
|---------|-----------|-----------|
| **API Key Prefix** | `pk_test_...` / `sk_test_...` | `pk_live_...` / `sk_live_...` |
| **Data** | Test data only | Real production data |
| **Payments** | Simulated (no real money) | Real payments processed |
| **Bank Integration** | Sandbox/Mock provider | Real bank APIs |
| **Webhooks** | Test webhook URLs | Production webhook URLs |
| **Visibility** | Can see only test data | Can see only live data |

### Mode Validation Rules

✅ **Allowed:**
- Test API key (`pk_test_...`) + Merchant in TEST mode → Test data
- Live API key (`pk_live_...`) + Merchant in LIVE mode → Live data

❌ **Blocked:**
- Test API key + Merchant in LIVE mode → **403 Error**
- Live API key + Merchant in TEST mode → **403 Error**

---

## Setting Up Test Environment

### Step 1: Run Test Data Seeder

```bash
php artisan db:seed --class=TestDataSeeder
```

This will create:
- ✅ Test merchant account
- ✅ Test and Live API keys
- ✅ 10 sample orders with various statuses
- ✅ Sample transactions (successful, failed, pending)
- ✅ 5 payment links
- ✅ 3 refunds (partial and full)
- ✅ 1 settlement batch

### Step 2: Login Credentials

After running the seeder, use these credentials:

**Test Merchant Login:**
```
Email: test@merchant.com
Password: password123
```

**API Keys:**
```
Test Mode Key: pk_test_[generated]
Test Secret: sk_test_[generated]

Live Mode Key: pk_live_[generated]
Live Secret: sk_live_[generated]
```

*(Check console output after running seeder for actual keys)*

---

## Test Data

### Test Card Numbers (Sandbox)

Use these test card numbers for payment testing:

| Card Type | Number | CVV | Expiry | Expected Result |
|-----------|--------|-----|--------|-----------------|
| Visa Success | `4111 1111 1111 1111` | 123 | 12/25 | ✅ Payment Success |
| Mastercard Success | `5555 5555 5555 4444` | 123 | 12/25 | ✅ Payment Success |
| Visa Failure | `4000 0000 0000 0002` | 123 | 12/25 | ❌ Payment Failed |
| Insufficient Funds | `4000 0000 0000 9995` | 123 | 12/25 | ❌ Insufficient Funds |

### Test UPI IDs

```
success@upi - Payment Success
failure@upi - Payment Failure
pending@upi - Payment Pending (for 2 mins)
```

### Test Bank Accounts (Net Banking)

```
Bank: Test Bank
Username: testuser
Password: test123
OTP: 123456
```

---

## Testing Scenarios

### Scenario 1: Test Mode Operations

**1. Login as Test Merchant**
```
Email: test@merchant.com
Password: password123
```

**2. Verify Test Mode Badge**
- Top-right corner should show: `TEST MODE` (yellow badge)
- Sidebar header should display: `TEST MODE`

**3. Test Operations in Dashboard**
- ✅ View test orders
- ✅ View test transactions
- ✅ Create test payment links
- ✅ Process test refunds
- ✅ View test settlements

**Expected Results:**
- All data should be test data only
- No live data visible
- All transactions show `test_mode: true`

---

### Scenario 2: Mode Switching

**1. Switch to Live Mode**
- Click the `LIVE` button in the top-right mode toggle

**2. Verify**
- Badge changes to `LIVE MODE` (green)
- Dashboard data changes (should be empty if no live data)
- All test data disappears from view

**3. Switch Back to Test Mode**
- Click the `TEST` button
- Test data reappears

---

### Scenario 3: Mode Mismatch Testing (Important!)

This tests that test API keys don't work in live mode and vice versa.

#### Test A: Test API Key in LIVE Mode

**Steps:**
1. Login as `test@merchant.com`
2. Switch merchant to **LIVE MODE** in dashboard
3. Make API call with **TEST API key** (`pk_test_...`)

**Expected Result:**
```json
{
  "error": "API key mode mismatch",
  "message": "Your merchant account is in LIVE MODE. Live mode API key (pk_live_...) is required. Please switch to the correct mode or use the appropriate API key.",
  "merchant_mode": "live",
  "api_key_mode": "test"
}
```
**HTTP Status:** `403 Forbidden`

#### Test B: Live API Key in TEST Mode

**Steps:**
1. Login as `test@merchant.com`
2. Ensure merchant is in **TEST MODE**
3. Make API call with **LIVE API key** (`pk_live_...`)

**Expected Result:**
```json
{
  "error": "API key mode mismatch",
  "message": "Your merchant account is in TEST MODE. Test mode API key (pk_test_...) is required. Please switch to the correct mode or use the appropriate API key.",
  "merchant_mode": "test",
  "api_key_mode": "live"
}
```
**HTTP Status:** `403 Forbidden`

---

## API Testing

### Creating a Test Payment

**Endpoint:** `POST /api/v1/payment`

**Headers:**
```http
X-API-Key: pk_test_[your_test_key]
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": 1000,
  "currency": "INR",
  "payment_method": "upi",
  "customer_details": {
    "name": "Test Customer",
    "email": "customer@test.com",
    "phone": "+919876543210"
  },
  "description": "Test payment",
  "return_url": "https://yoursite.com/success",
  "cancel_url": "https://yoursite.com/cancel"
}
```

**Expected Response (Success):**
```json
{
  "success": true,
  "mode": "test",
  "data": {
    "order_id": "ORD_TEST_...",
    "amount": 1000,
    "currency": "INR",
    "status": "created",
    "test_mode": true,
    "payment_url": "http://localhost/pay/[token]"
  }
}
```

### Fetching Test Orders

**Endpoint:** `GET /api/v1/orders`

**Headers:**
```http
X-API-Key: pk_test_[your_test_key]
```

**Expected Response:**
```json
{
  "success": true,
  "mode": "test",
  "data": [
    {
      "order_id": "ORD_TEST_...",
      "amount": 1000,
      "status": "completed",
      "test_mode": true,
      "created_at": "2024-01-01T12:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 10
  }
}
```

### Creating a Test Refund

**Endpoint:** `POST /api/v1/refunds`

**Headers:**
```http
X-API-Key: pk_test_[your_test_key]
Content-Type: application/json
```

**Request Body:**
```json
{
  "transaction_id": "TXN_TEST_...",
  "amount": 500,
  "reason": "Customer requested refund"
}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "refund_id": "REF_TEST_...",
    "transaction_id": "TXN_TEST_...",
    "amount": 500,
    "status": "pending",
    "is_partial": true,
    "created_at": "2024-01-01T12:00:00Z"
  }
}
```

---

## Mode Mismatch Testing

### Using cURL

#### Test 1: Wrong API Key Mode

```bash
# Merchant in TEST mode, but using LIVE key (should fail)
curl -X GET http://localhost/api/v1/orders \
  -H "X-API-Key: pk_live_[your_live_key]" \
  -H "Content-Type: application/json"
```

**Expected:**
```json
{
  "error": "API key mode mismatch",
  "message": "Your merchant account is in TEST MODE. Test mode API key (pk_test_...) is required...",
  "merchant_mode": "test",
  "api_key_mode": "live"
}
```

#### Test 2: Accessing Wrong Mode Data

```bash
# Using test key to access a live order (should return not found)
curl -X GET http://localhost/api/v1/orders/ORD_LIVE_123 \
  -H "X-API-Key: pk_test_[your_test_key]" \
  -H "Content-Type: application/json"
```

**Expected:**
```json
{
  "error": "Order not found",
  "message": "Order not found or does not match your API key mode. Ensure you are using the correct API key (test/live) for this order."
}
```

---

## Common Test Cases

### ✅ Test Case 1: Complete Payment Flow in Test Mode

1. **Create Order** via API with test key
2. **Open Payment URL** from response
3. **Enter Test Card Details**
4. **Complete Payment**
5. **Verify Transaction** status is `success`
6. **Check Webhook** received (if configured)

### ✅ Test Case 2: Partial Refund

1. **Find a Successful Transaction** in test mode
2. **Create Refund** for 50% of amount
3. **Verify Refund Status** is `pending` or `processing`
4. **Check Transaction** shows refund details

### ✅ Test Case 3: Payment Link Creation

1. **Create Payment Link** in test mode
2. **Share Link** (copy from dashboard)
3. **Open Link** in browser
4. **Complete Payment** with test card
5. **Verify Order Created** in dashboard

### ✅ Test Case 4: Settlement Processing

1. **Navigate to Settlements** page
2. **View Pending Transactions** for settlement
3. **Check Settlement Amount** calculation
4. **Verify Fee Deduction** is correct

### ✅ Test Case 5: Mode Isolation

1. **Create 5 test orders** in TEST mode
2. **Switch to LIVE mode**
3. **Verify** no test orders visible
4. **Create 1 live order** (if in live)
5. **Switch back to TEST mode**
6. **Verify** live order not visible, only test orders

---

## API Testing with Postman

### Import Collection

Create a Postman collection with the following environment variables:

```json
{
  "test_api_key": "pk_test_your_key_here",
  "live_api_key": "pk_live_your_key_here",
  "base_url": "http://localhost/api/v1"
}
```

### Key Endpoints to Test

1. **Create Payment** - `POST {{base_url}}/payment`
2. **Get Orders** - `GET {{base_url}}/orders`
3. **Get Single Order** - `GET {{base_url}}/orders/:orderId`
4. **Get Transactions** - `GET {{base_url}}/transactions`
5. **Create Refund** - `POST {{base_url}}/refunds`
6. **Get Refunds** - `GET {{base_url}}/refunds`

---

## Troubleshooting

### Issue: "API key mode mismatch" error

**Solution:** 
- Check your merchant's current mode (TEST/LIVE) in dashboard
- Ensure you're using matching API key (`pk_test_...` for TEST, `pk_live_...` for LIVE)

### Issue: No data visible in dashboard

**Possible Causes:**
1. Wrong mode selected (switch between TEST/LIVE)
2. No data created yet (run seeder)
3. Filters applied (clear filters)

### Issue: Test payments not processing

**Solution:**
- Verify merchant is in TEST mode
- Use test card numbers provided above
- Check application logs for errors

---

## Summary

### ✅ What We've Tested

1. **Mode Validation** - Test keys only work in test mode, live keys only in live mode
2. **Data Isolation** - Test data is completely separate from live data
3. **API Security** - Proper authentication and authorization
4. **Payment Processing** - End-to-end payment flows
5. **Refunds & Settlements** - Financial operations in test mode

### 🎯 Best Practices

1. **Always test in TEST mode first** before going live
2. **Use test API keys** for development and staging
3. **Never use live keys** in test/development environments
4. **Switch modes carefully** when testing different scenarios
5. **Verify mode badge** before performing operations
6. **Check API responses** for `mode` field to confirm correct mode

---

## Need Help?

- Check logs: `storage/logs/laravel.log`
- Review API documentation: `docs/API.md`
- Contact support: support@badlicash.com

---

**Happy Testing! 🚀**


