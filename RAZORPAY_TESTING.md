# 🧪 Razorpay Testing Guide - PowerShell

## Quick Start

### Option 1: Quick Test (Recommended for First Time)

1. **Edit the script:**
   ```powershell
   notepad test-razorpay-quick.ps1
   ```
   
2. **Update your API key:**
   - Find line: `$API_KEY = "pk_test_YOUR_API_KEY_HERE"`
   - Replace with your actual API key (e.g., `$API_KEY = "pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp"`)

3. **Run the script:**
   ```powershell
   .\test-razorpay-quick.ps1
   ```

4. **The script will:**
   - Create a payment
   - Show payment details
   - Open payment page in browser
   - Display test card details

### Option 2: Interactive Menu (Full Testing)

1. **Edit the script:**
   ```powershell
   notepad test-razorpay.ps1
   ```
   
2. **Update your API key** (same as above)

3. **Run the script:**
   ```powershell
   .\test-razorpay.ps1
   ```

4. **Use the menu to:**
   - Create payments
   - Check payment status
   - Test refunds
   - List transactions

---

## Prerequisites

### 1. Get Your API Key

**Option A: From Admin Dashboard**
1. Go to: `http://127.0.0.1:8000/admin/acquirer-accounts`
2. Find your merchant
3. Go to API Keys section
4. Copy a test API key (starts with `pk_test_...`)

**Option B: Create Test Data**
```powershell
php artisan db:seed --class=TestDataSeeder
```
The output will show your API key.

### 2. Link Merchant to Razorpay Account

1. Go to: `http://127.0.0.1:8000/admin/acquirer-accounts`
2. Find your Razorpay account (`razorpay_test_001`)
3. Click **Edit**
4. In **Merchants** dropdown, select your test merchant
5. Click **Update**

### 3. Start Laravel Server

```powershell
php artisan serve
```

Server should be running at `http://127.0.0.1:8000`

---

## Test Scenarios

### Scenario 1: Basic Payment Flow

```powershell
# Run quick test
.\test-razorpay-quick.ps1

# Payment page opens automatically
# Use test card: 4111 1111 1111 1111
```

### Scenario 2: Check Payment Status

```powershell
# Run interactive menu
.\test-razorpay.ps1

# Select option 3
# Enter transaction ID when prompted
```

### Scenario 3: Test Refund

```powershell
# Run interactive menu
.\test-razorpay.ps1

# Select option 4
# Enter payment ID (from Razorpay dashboard)
```

---

## Manual API Testing

If you prefer to test manually, use these PowerShell commands:

### Create Payment
```powershell
$apiKey = "pk_test_YOUR_API_KEY"
$body = @{
    amount = 100.00
    currency = "INR"
    description = "Test Payment"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/payments" `
    -Method POST `
    -Headers @{
        "X-API-Key" = $apiKey
        "Content-Type" = "application/json"
    } `
    -Body $body
```

### Check Payment Status
```powershell
$apiKey = "pk_test_YOUR_API_KEY"
$transactionId = "YOUR_TRANSACTION_ID"

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/payments/$transactionId" `
    -Method GET `
    -Headers @{ "X-API-Key" = $apiKey }
```

---

## Razorpay Test Cards

### Success Cards
- **Card Number:** `4111 1111 1111 1111`
- **CVV:** Any 3 digits (e.g., `123`)
- **Expiry:** Any future date (e.g., `12/25`)
- **Name:** Any name

### Failure Cards
- **Card Number:** `4000 0000 0000 0002` (Declined)
- **Card Number:** `4000 0000 0000 0069` (Expired)

### UPI Test
- **UPI ID:** `success@razorpay` (Success)
- **UPI ID:** `failure@razorpay` (Failure)

---

## Troubleshooting

### Error: "Cannot connect to server"
**Solution:** Make sure Laravel server is running
```powershell
php artisan serve
```

### Error: "Invalid or expired API key"
**Solution:** 
1. Check API key is correct
2. Ensure API key starts with `pk_test_...`
3. Verify merchant exists and is active

### Error: "Payment not using Razorpay"
**Solution:**
1. Check merchant is linked to Razorpay account
2. Verify Razorpay account `is_active = true`
3. Check `mode = TEST` matches merchant test mode

### Payment page not loading
**Solution:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify payment URL is correct
3. Check browser console for errors

---

## Expected Results

### Successful Payment Creation
```json
{
  "success": true,
  "payment_url": "http://127.0.0.1:8000/pay/abc123...",
  "link_token": "abc123...",
  "amount": 100.00,
  "currency": "INR"
}
```

### Successful Payment Status
```json
{
  "transaction_id": "txn_...",
  "status": "success",
  "amount": 100.00,
  "currency": "INR"
}
```

---

## Next Steps

After basic testing:

1. **Test Webhooks** - Configure Razorpay webhook URL
2. **Test Refunds** - Create refunds via API
3. **Test Settlements** - Check settlement processing
4. **Test Different Payment Methods** - UPI, Netbanking, Wallets

---

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console (F12)
3. Verify database: Check `transactions`, `orders`, `provider_responses` tables
4. Review Razorpay dashboard for payment status

