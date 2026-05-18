# 🚀 Quick Test - Razorpay Integration

## ✅ Prerequisites Check

- ✅ Razorpay account created (`razorpay_test`)
- ✅ Keys are set in database (Additional Key 1: 23 chars, Secret Key: 24 chars)
- ✅ Merchant linked to Razorpay account (verify this!)
- ✅ API key obtained

---

## 🧪 Step-by-Step Test

### STEP 1: Verify Merchant is Linked

1. Go to: `http://127.0.0.1:8000/admin/acquirer-accounts`
2. Click **Edit** on `razorpay_test_001`
3. Check **Merchants** dropdown - make sure your test merchant is selected
4. Click **Save** if you made changes

---

### STEP 2: Create Payment via PowerShell

Open PowerShell and run:

```powershell
cd D:\agdp_projects\Badlicash-Payment-Gateway

# Update with your actual API key
$apiKey = "pk_test_DaTlh8RppMvyz76Oh2SPUPU9BNdec7sR"

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

$response | ConvertTo-Json -Depth 5
```

**Expected:** You'll get a `payment_url`

---

### STEP 3: Complete Payment

1. **Open** the `payment_url` in your browser
2. **Fill the form:**
   - Customer Name: `Test Customer`
   - Email: `test@example.com`
   - Phone: `9876543210`
   - Payment Method: **Card**
   - Card Number: `4111 1111 1111 1111`
   - Card Holder: `Test User`
   - Expiry: `12/25`
   - CVV: `123`
3. **Click Pay**

---

### STEP 4: Verify Results

#### ✅ Check Your Gateway Dashboard
- Go to: `http://127.0.0.1:8000/admin/payments/transactions`
- You should see the transaction with status "success"

#### ✅ Check Razorpay Dashboard
- Go to: https://dashboard.razorpay.com/
- Navigate to: **Payments** (make sure you're in **Test Mode**)
- You should see the payment transaction!

#### ✅ Check Laravel Logs
```powershell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "Razorpay"
```

You should see:
- "Razorpay order created successfully"
- "Razorpay payment created successfully"
- "Processing payment through acquirer adapter"

---

## 🎯 Success Indicators

✅ **Payment appears in your gateway dashboard**
✅ **Payment appears in Razorpay Dashboard**
✅ **Transaction has Razorpay order ID and payment ID**
✅ **No errors in Laravel logs**

---

## 🆘 If Payment Doesn't Appear in Razorpay

1. **Check Laravel logs** for errors:
   ```powershell
   Get-Content storage/logs/laravel.log -Tail 100 | Select-String "error|Razorpay" -Context 3
   ```

2. **Verify credentials are correct:**
   - Additional Key 1 should be your Razorpay Key ID (starts with `rzp_test_...`)
   - Secret Key should be your Razorpay Secret Key

3. **Check if merchant is linked:**
   - Edit Razorpay account
   - Verify merchant is selected

4. **Verify Razorpay account is active:**
   - Is Active = Yes

---

## 📝 Quick Test Script

You can also use the test script:

```powershell
.\test-and-view.ps1
```

This will:
- Create payment
- Open payment page
- Show you where to view transactions

---

**Ready to test!** 🚀

Start with STEP 1 to verify merchant is linked, then proceed with payment creation.

