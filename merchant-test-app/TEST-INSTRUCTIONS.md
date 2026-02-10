# 🎯 Quick Test Instructions

## Start Testing in 3 Simple Steps

### Step 1: Start Laravel Backend
```bash
# Open terminal in main project directory
php artisan serve
```
✅ Should run on: `http://localhost:8000`

---

### Step 2: Start Test App
```bash
# Open new terminal
cd merchant-test-app
php -S localhost:8080
```
✅ Should run on: `http://localhost:8080`

---

### Step 3: Open Browser
```
http://localhost:8080
```

---

## 🧪 What to Test

### ✅ Success Flow
1. Click "Pay Now" on any product
2. Fill customer details (or use pre-filled test data)
3. Click "Confirm & Pay"
4. Complete payment in gateway
5. See success page with webhook logs

### ❌ Failure Flow
1. Use test card that triggers failure
2. See failure page with error details
3. Check webhook logs for failure events

### ⏳ Pending Flow
1. Create payment that stays in pending
2. Watch auto-refresh checking status
3. Auto-redirects when status changes

### 💰 Refund Flow
1. Complete successful payment first
2. Login to merchant dashboard
3. Issue refund for the transaction
4. Visit refund page to see logs

---

## 🔑 Pre-Configured Test Credentials

### Merchant Login
- **Email:** merchant1@badlicash.test
- **Password:** Password123!

### API Keys (Already in config.js)
- **API Key:** `pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp`
- **Secret:** `sk_test_SdBNftTovx41kANBayBSWZQQFsnuvSgo`

---

## 📊 What You'll See

### Main Store Page
- 3 beautiful product cards
- Modern gradient design
- Smooth animations
- Payment modal on click

### Success Page
- ✅ Green success icon with animation
- Transaction details
- Real-time webhook logs
- Auto-refresh every 5 seconds
- Download receipt button

### Failure Page
- ❌ Red error icon
- Error message details
- Failed webhook logs
- Try again button

### Pending Page
- ⏳ Animated pending icon (pulsing)
- Status checking indicator
- Auto-refresh every 3 seconds
- Auto-redirect on status change

### Refund Page
- 💳 Blue refund icon
- Refund details
- Refund webhook logs
- Download receipt button

---

## 🔍 How to Check Webhook Logs

Webhook logs will appear automatically on status pages. They show:
- Event name (e.g., `payment.success`, `payment.failed`)
- Timestamp
- Status badge
- Full payload in JSON format

The logs auto-refresh so you can watch them in real-time!

---

## 🚨 Troubleshooting

### Can't Access App?
- ✅ Check Laravel is running: `http://localhost:8000`
- ✅ Check test app is running: `http://localhost:8080`
- ✅ Check no port conflicts

### CORS Errors?
- ✅ Verify `config/cors.php` in main project
- ✅ Check API URL in `config.js`

### Payment Not Creating?
- ✅ Check browser console for errors (F12)
- ✅ Verify API keys are active
- ✅ Check Laravel logs: `storage/logs/laravel.log`

### Webhooks Not Showing?
- ✅ Verify webhooks are enabled in gateway
- ✅ Check `webhook_events` table in database
- ✅ Ensure transaction_id is valid

---

## 📸 Expected Results

When everything works:

1. **Product Selection** → Modal opens instantly
2. **Confirm Payment** → Redirects to gateway
3. **Complete Payment** → Returns to success page
4. **Webhook Logs** → Appear within seconds
5. **Auto-refresh** → New logs appear automatically

---

## 💡 Pro Tips

1. **Keep browser console open** (F12) to see debug logs
2. **Watch the network tab** to see API calls
3. **Check database** to verify data is being saved
4. **Test all statuses** to ensure complete coverage
5. **Try different products** to test various amounts

---

## 🎉 All Set!

Your test environment is ready. Happy testing! 🚀

For detailed documentation, see:
- `README.md` - Complete documentation
- `SETUP.md` - Detailed setup guide

