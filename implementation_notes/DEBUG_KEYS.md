# 🔍 Debug: Keys Not Showing in Edit Form

## Steps to Debug

1. **Open Browser Console** (F12)
2. **Go to:** `http://127.0.0.1:8000/admin/acquirer-accounts`
3. **Click Edit** on your Razorpay account
4. **Check Console** for these messages:
   - "Razorpay account in response:" - Shows if keys are in API response
   - "Selected Account Data:" - Shows what data is being used to populate form

## What to Look For

### If you see "EMPTY" in console:
- Keys are not in the database
- OR keys are not being returned from API

### If you see "SET" in console but form is empty:
- Data is in API but not binding to form
- Angular binding issue

## Quick Fix: Re-enter Keys

If keys are missing, you need to re-enter them:

1. **Edit** your Razorpay account
2. **Fill in:**
   - Additional Key 1: Your Razorpay Key ID
   - Secret Key: Your Razorpay Secret Key
3. **Click Save**
4. **Verify** they're saved by editing again

## Verify Keys Are Saved

Run this SQL query or use tinker:

```php
php artisan tinker
```

```php
$acc = \App\Models\AcquirerAccount::where('acquirer_name', 'LIKE', '%razorpay%')->first();
echo "Additional Key 1: " . ($acc->additional_key_1 ?: 'EMPTY') . "\n";
echo "Secret Key: " . ($acc->secret_key ?: 'EMPTY') . "\n";
```

If both show "EMPTY", the keys were never saved. You need to re-enter them.

