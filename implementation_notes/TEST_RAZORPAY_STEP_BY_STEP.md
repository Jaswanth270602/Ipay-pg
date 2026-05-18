# 🧪 Razorpay Testing - Step by Step Guide

## ✅ Goal: Create a Payment and See Transaction Display

This guide will show you **exactly** how to test Razorpay so that transactions appear in your dashboard.

---

## 📋 Prerequisites Checklist

Before starting, make sure:

- [ ] Razorpay account created (`razorpay_test_001`) with Key ID and Secret Key
- [ ] Merchant linked to Razorpay account
- [ ] API key obtained (starts with `pk_test_...`)
- [ ] Laravel server running (`php artisan serve`)

---

## 🚀 STEP-BY-STEP TESTING

### STEP 1: Start Laravel Server

Open PowerShell and run:
```powershell
cd D:\agdp_projects\Badlicash-Payment-Gateway
php artisan serve
```

**Expected:** You should see:
```
INFO  Server running on [http://127.0.0.1:8000]
```

**Keep this window open!**

---

### STEP 2: Create Payment via PowerShell

Open a **NEW** PowerShell window and run:

```powershell
cd D:\agdp_projects\Badlicash-Payment-Gateway

# Update this with your actual API key
$apiKey = "pk_test_YOUR_API_KEY_HERE"

# Create payment
$apiKey = "IAXgUU6DMjVXeFaNR8K2Wz20"
$body = @{
    amount = 100.00
    currency = "INR"
    description = "Razorpay Test Payment"
    customer_name = "Test Customer"
    customer_email = "test@example.com"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/payments" `
    -Method POST `
    -Headers @{
        "X-API-Key" = $apiKey
        "Content-Type" = "application/json"
    } `
    -Body $body

# Show response
$response | ConvertTo-Json -Depth 5
```

**Expected Output:**
```json
{
  "success": true,
  "payment_url": "http://127.0.0.1:8000/pay/abc123xyz...",
  "link_token": "abc123xyz...",
  "amount": 100.00,
  "currency": "INR"
}
```

**✅ Copy the `payment_url`!**

---

### STEP 3: Open Payment Page

1. Copy the `payment_url` from Step 2
2. Open it in your browser (e.g., `http://127.0.0.1:8000/pay/abc123xyz...`)
3. You should see the payment checkout page

---

### STEP 4: Fill Payment Form

On the payment page:

1. **Customer Details:**
   - Name: `Test Customer`
   - Email: `test@example.com`
   - Phone: `9876543210`

2. **Payment Method:** Select `Card`

3. **Card Details:**
   - Card Number: `4111 1111 1111 1111`
   - Card Holder: `Test User`
   - Expiry Month: `12`
   - Expiry Year: `2025`
   - CVV: `123`

4. Click **Pay** or **Submit**

---

### STEP 5: Payment Processing

After clicking Pay:

- The payment will be processed
- You'll be redirected to a success/failure page
- **A transaction will be created in the database**

---

### STEP 6: View Transaction in Dashboard

#### Option A: Admin Dashboard

1. Go to: `http://127.0.0.1:8000/admin/dashboard`
2. Navigate to: **Payments** → **Transactions**
   - OR go directly to: `http://127.0.0.1:8000/admin/payments/transactions`
3. You should see your transaction with:
   - Transaction ID
   - Amount: ₹100.00
   - Status: Success/Failed
   - Merchant name
   - Payment method: Card
   - Date/Time

#### Option B: Merchant Dashboard

1. Go to: `http://127.0.0.1:8000/merchant/dashboard`
2. Navigate to: **Transactions**
   - OR go directly to: `http://127.0.0.1:8000/merchant/transactions`
3. You should see your transaction

---

### STEP 7: Verify Transaction in Database (Optional)

Open PowerShell and run:

```powershell
php artisan tinker
```

Then in tinker:
```php
// Get latest transaction
$txn = \App\Models\Transaction::latest()->first();
echo "Transaction ID: " . $txn->txn_id . "\n";
echo "Status: " . $txn->status . "\n";
echo "Amount: " . $txn->amount . "\n";
echo "Merchant: " . $txn->merchant->name . "\n";
echo "Created: " . $txn->created_at . "\n";
```

---

## 🔍 Troubleshooting

### Transaction Not Appearing?

**Check 1: Verify Transaction Was Created**
```powershell
php artisan tinker
```
```php
\App\Models\Transaction::count(); // Should be > 0
\App\Models\Transaction::latest()->first(); // Check latest transaction
```

**Check 2: Check Laravel Logs**
```powershell
Get-Content storage/logs/laravel.log -Tail 50
```

Look for:
- "Payment processed"
- "Transaction created"
- Any error messages

**Check 3: Verify Payment Was Completed**
- Did you see a success page after payment?
- Check if order status is "completed" or "failed"

**Check 4: Check Dashboard Filters**
- Make sure you're viewing **TEST** mode transactions (if using test API key)
- Check date filters aren't excluding your transaction
- Try clearing all filters

---

## 📊 What You Should See

### In Transactions Table:

| Transaction ID | Merchant | Amount | Status | Payment Method | Date |
|---------------|----------|--------|--------|----------------|------|
| txn_abc123... | Your Merchant | ₹100.00 | Success | Card | 2026-01-09 17:30:00 |

### Transaction Details Should Include:

- ✅ Transaction ID (txn_...)
- ✅ Order ID (ord_...)
- ✅ Amount: ₹100.00
- ✅ Currency: INR
- ✅ Status: success/failed/pending
- ✅ Payment Method: card
- ✅ Merchant Name
- ✅ Customer Details
- ✅ Created Date/Time

---

## 🎯 Quick Test Script

Save this as `test-and-view.ps1`:

```powershell
# Step 1: Create Payment
$apiKey = "pk_test_YOUR_API_KEY_HERE"
$body = @{
    amount = 100.00
    currency = "INR"
    description = "Quick Test"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/payments" `
    -Method POST `
    -Headers @{
        "X-API-Key" = $apiKey
        "Content-Type" = "application/json"
    } `
    -Body $body

Write-Host "✅ Payment Created!" -ForegroundColor Green
Write-Host "Payment URL: $($response.payment_url)" -ForegroundColor Cyan
Write-Host "Link Token: $($response.link_token)" -ForegroundColor Cyan

# Step 2: Open payment page
Start-Process $response.payment_url

Write-Host "`n📝 Instructions:" -ForegroundColor Yellow
Write-Host "1. Complete payment on the page that opened" -ForegroundColor White
Write-Host "2. Use test card: 4111 1111 1111 1111" -ForegroundColor White
Write-Host "3. After payment, go to: http://127.0.0.1:8000/admin/payments/transactions" -ForegroundColor White
Write-Host "4. You should see your transaction!" -ForegroundColor White
```

Run it:
```powershell
.\test-and-view.ps1
```

---

## ✅ Success Checklist

After completing all steps, you should have:

- [ ] Payment created via API
- [ ] Payment page opened in browser
- [ ] Payment form filled and submitted
- [ ] Payment processed (success or failure)
- [ ] Transaction visible in Admin → Payments → Transactions
- [ ] Transaction visible in Merchant → Transactions
- [ ] Transaction details showing correct information

---

## 🆘 Still Not Working?

If transactions still don't appear:

1. **Check Database Connection:**
   ```powershell
   php artisan migrate:status
   ```

2. **Check Transaction Table:**
   ```powershell
   php artisan tinker
   ```
   ```php
   DB::table('transactions')->count();
   DB::table('transactions')->latest()->first();
   ```

3. **Check for Errors:**
   ```powershell
   Get-Content storage/logs/laravel.log -Tail 100 | Select-String "error" -Context 5
   ```

4. **Verify Payment Service:**
   - Check if `PaymentService` is being called
   - Check if `PaymentSimulationService` is creating transactions

---

## 📞 Next Steps

Once you see transactions displaying:

1. Test different payment methods (UPI, Netbanking)
2. Test refunds
3. Test webhooks
4. Check Razorpay dashboard for payment status

---

**Remember:** Transactions are created when you **complete the payment form** on the checkout page, not just when you create the payment via API!

