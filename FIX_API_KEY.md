# 🔧 Fix: Invalid API Key Error (401 Unauthorized)

## ❌ Problem

You're getting `401 Unauthorized` because the API key format is incorrect.

**Your current key:** `IAXgUU6DMjVXeFaNR8K2Wz20` ❌

**Correct format should be:** `pk_test_...` or `pk_live_...` ✅

---

## ✅ Solution: Get Your Correct API Key

### Option 1: Get from Merchant Dashboard (Easiest)

1. **Go to:** `http://127.0.0.1:8000/merchant/api-keys`
   - OR: Login → Merchant Dashboard → API Keys

2. **If you see API keys:**
   - Copy the key that starts with `pk_test_...`
   - Use that in your PowerShell script

3. **If no API keys exist:**
   - Click **"Create API Key"** button
   - Name: `Test Key`
   - Mode: `test`
   - Click **Create**
   - **Copy the generated key** (it will show only once!)
   - Format: `pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Option 2: Get from Database (Advanced)

Run this in PowerShell:

```powershell
php artisan tinker
```

Then in tinker, type:
```php
$keys = \App\Models\ApiKey::where('status', 'active')->get();
foreach($keys as $k) {
    echo $k->key . " (Mode: " . $k->mode . ")\n";
}
```

### Option 3: Create New API Key via Tinker

```powershell
php artisan tinker
```

Then:
```php
// Find a merchant
$merchant = \App\Models\Merchant::first();
// Create API key
$apiKey = \App\Models\ApiKey::generate($merchant->id, 'test', 'Test Key');
echo "New API Key: " . $apiKey->key . "\n";
```

---

## 🔄 Update Your Test Script

Once you have the correct API key, update your PowerShell command:

```powershell
# ✅ CORRECT FORMAT
$apiKey = "pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp"  # Example - use YOUR actual key

# ❌ WRONG FORMAT (what you had)
# $apiKey = "IAXgUU6DMjVXeFaNR8K2Wz20"  # This won't work!
```

---

## 📝 Complete Working Example

```powershell
# Step 1: Use correct API key format
$apiKey = "pk_test_YOUR_ACTUAL_KEY_HERE"  # Must start with pk_test_ or pk_live_

# Step 2: Create payment
$body = @{
    amount = 100.00
    currency = "INR"
    description = "Razorpay Test Payment"
    customer_name = "Test Customer"
    customer_email = "test@example.com"
} | ConvertTo-Json

# Step 3: Make request
$response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/payments" `
    -Method POST `
    -Headers @{
        "X-API-Key" = $apiKey
        "Content-Type" = "application/json"
    } `
    -Body $body

# Step 4: Show result
$response | ConvertTo-Json -Depth 5
```

---

## 🎯 Quick Fix Steps

1. **Go to:** `http://127.0.0.1:8000/merchant/api-keys`
2. **Login** as merchant (or create one if needed)
3. **Create API Key** if none exists
4. **Copy the key** (starts with `pk_test_...`)
5. **Update your PowerShell script** with the correct key
6. **Run the test again**

---

## ✅ API Key Format Rules

- ✅ **Test keys:** Must start with `pk_test_` followed by 32 characters
- ✅ **Live keys:** Must start with `pk_live_` followed by 32 characters
- ❌ **Invalid:** Any other format (like `IAXgUU6DMjVXeFaNR8K2Wz20`)

---

## 🆘 Still Having Issues?

If you can't access the merchant dashboard:

1. **Check if you have a merchant account:**
   ```powershell
   php artisan tinker
   ```
   ```php
   \App\Models\Merchant::count();  // Should be > 0
   ```

2. **Create a test merchant if needed:**
   ```powershell
   php artisan db:seed --class=TestDataSeeder
   ```
   This creates test merchants and API keys.

3. **Check the seeder output** - it will show you the API keys created.

