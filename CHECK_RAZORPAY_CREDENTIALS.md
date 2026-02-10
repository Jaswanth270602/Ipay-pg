# 🔍 Check Razorpay Credentials Configuration

## ❌ Problem

The error in logs shows:
```
Failed to resolve acquirer adapter {"merchant_id":1,"error":"Razorpay credentials not configured in AcquirerAccount"}
```

This means the Razorpay adapter cannot find the credentials in your AcquirerAccount.

---

## ✅ Solution: Verify Credentials

### Step 1: Check Your Acquirer Account

1. Go to: `http://127.0.0.1:8000/admin/acquirer-accounts`
2. Find your Razorpay account (`razorpay_test_001`)
3. Click **Edit**
4. Verify these fields are filled:

   **Required Fields:**
   - ✅ **Additional Key 1**: Your Razorpay Key ID (e.g., `rzp_test_xxxxxxxxxxxxx`)
   - ✅ **Secret Key**: Your Razorpay Key Secret (e.g., `xxxxxxxxxxxxxxxxxxxxxxxx`)

   **Optional (not needed):**
   - Additional Key 2: Leave empty
   - Salt: Leave empty

### Step 2: Verify Credentials Are Saved

After saving, check the database or refresh the edit page to confirm:
- Additional Key 1 has your Razorpay Key ID
- Secret Key has your Razorpay Key Secret

### Step 3: Check Laravel Logs

After making a payment, check logs:
```powershell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "Razorpay credential"
```

You should see:
```
Razorpay credential extraction {"additional_key_1":"SET","secret_key":"SET",...}
```

If you see "EMPTY" for either field, the credentials are not saved correctly.

---

## 🔧 How Credentials Are Extracted

The system looks for credentials in this order:

**Key ID (Razorpay Key ID):**
1. `additional_key_1` ← **Primary location**
2. `secret_key` (fallback)

**Key Secret (Razorpay Secret Key):**
1. `additional_key_2` (if set)
2. `secret_key` ← **Primary location** (if Key ID is in additional_key_1)
3. `salt` (fallback)

---

## ✅ Correct Configuration

```
Acquirer Name: razorpay_test
Account ID: razorpay_test_001
Mode: TEST
Additional Key 1: rzp_test_xxxxxxxxxxxxx  ← Razorpay Key ID
Secret Key: xxxxxxxxxxxxxxxxxxxxxxxx       ← Razorpay Secret Key
Is Active: Yes
```

---

## 🧪 Test After Fixing

1. **Update credentials** in the acquirer account
2. **Save** the account
3. **Create a new payment** via your test script
4. **Check Razorpay Dashboard** - payment should appear
5. **Check Laravel logs** - should see "Razorpay order created" and "Razorpay payment created"

---

## 🆘 Still Not Working?

If credentials are set but still not working:

1. **Check database directly:**
   ```powershell
   php artisan tinker
   ```
   ```php
   $account = \App\Models\AcquirerAccount::where('acquirer_name', 'razorpay_test')->first();
   echo "Additional Key 1: " . ($account->additional_key_1 ? 'SET' : 'EMPTY') . "\n";
   echo "Secret Key: " . ($account->secret_key ? 'SET' : 'EMPTY') . "\n";
   ```

2. **Verify Razorpay keys are correct:**
   - Key ID should start with `rzp_test_` (for test mode)
   - Secret Key should be a long string (no prefix)

3. **Check if merchant is linked:**
   - Edit acquirer account
   - Verify merchant is selected in "Merchants" dropdown

---

## 📝 Quick Fix Checklist

- [ ] Additional Key 1 contains Razorpay Key ID
- [ ] Secret Key contains Razorpay Secret Key  
- [ ] Acquirer account is Active
- [ ] Merchant is linked to acquirer account
- [ ] Mode matches (TEST for test keys)
- [ ] Saved the acquirer account after making changes

