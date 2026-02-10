# 🚀 Badlicash Merchant Test App - Setup Guide

## ✅ Congratulations! Your Test App is Ready

Your beautiful merchant test application has been created and pre-configured with test API keys.

---

## 📋 Quick Start

### Step 1: Start Your Laravel Backend

Make sure your Badlicash Payment Gateway is running:

```bash
# In your main project directory
php artisan serve
```

This should start on `http://localhost:8000`

### Step 2: Serve the Merchant Test App

Navigate to the merchant-test-app directory and start a local server:

```bash
cd merchant-test-app

# Option 1: Using PHP
php -S localhost:8080

# Option 2: Using Python
python -m http.server 8080

# Option 3: Using Node.js http-server
npx http-server -p 8080

# Option 4: Use VS Code Live Server extension
# Right-click index.html → "Open with Live Server"
```

### Step 3: Open in Browser

Open your browser and go to:
```
http://localhost:8080
```

---

## 🔑 Pre-Configured Credentials

Your test app is already configured with these credentials:

### Test Merchant Details
- **Merchant ID:** 1
- **Company Name:** Acme Corp
- **Email:** merchant1@badlicash.test
- **Password:** Password123!
- **Status:** Active
- **Test Mode:** Enabled

### API Keys (Already in config.js)
- **API Key:** `pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp`
- **Secret Key:** `sk_test_SdBNftTovx41kANBayBSWZQQFsnuvSgo`
- **Mode:** Test
- **Status:** Active

---

## 🎯 How to Test the Complete Flow

### 1. **Make a Test Payment**

1. Open `http://localhost:8080` in your browser
2. You'll see 3 beautiful product cards
3. Click "Pay Now" on any product
4. Enter customer details (or use pre-filled test data)
5. Click "Confirm & Pay"

### 2. **Payment Processing**

The app will:
- Create a payment request to your gateway API
- Redirect to the payment gateway checkout page
- Process the payment (you'll simulate this in your gateway)
- Redirect back with payment status

### 3. **View Results**

Based on the payment status, you'll be redirected to:

- ✅ **Success Page** (`success.html`)
  - Shows transaction details
  - Displays all webhook events in real-time
  - Auto-refreshes every 5 seconds
  - Download receipt option

- ❌ **Failure Page** (`failure.html`)
  - Shows error details
  - Displays webhook logs
  - Option to try again

- ⏳ **Pending Page** (`pending.html`)
  - Shows "processing" status
  - Auto-checks status every 5 seconds
  - Auto-redirects when status changes

- 💰 **Refund Page** (`refund.html`)
  - Shows refund details
  - Displays refund webhook logs
  - Download refund receipt option

---

## 🔧 Configuration Files

### config.js
Main configuration file with API keys and settings. Already configured!

```javascript
GATEWAY_CONFIG = {
    apiUrl: 'http://localhost:8000/api',
    apiKey: 'pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp',
    secretKey: 'sk_test_SdBNftTovx41kANBayBSWZQQFsnuvSgo',
    merchantId: '1',
    // ... other settings
}
```

---

## 📂 Project Structure

```
merchant-test-app/
├── index.html           # Main store page (3 products)
├── success.html         # Success page with webhook logs
├── failure.html         # Failure page with error details
├── pending.html         # Pending status page
├── refund.html          # Refund confirmation page
├── config.js            # Configuration (API keys) ✅ Pre-configured
├── README.md            # Detailed documentation
├── SETUP.md             # This file
├── css/
│   └── style.css        # Modern, beautiful styles
└── js/
    ├── app.js           # Main application logic
    └── payment.js       # Payment gateway integration
```

---

## 🧪 Testing Scenarios

### Test Success Payment
1. Select a product
2. Complete payment normally
3. Verify success page shows correct details
4. Check webhook logs appear

### Test Failed Payment
1. Use test card that triggers failure (if configured in your gateway)
2. Verify failure page shows error message
3. Check failure webhook logs

### Test Pending Payment
1. Use test scenario that creates pending status
2. Verify pending page shows loading state
3. Wait for auto-redirect when status changes

### Test Refund
1. Complete a successful payment
2. Login to merchant dashboard
3. Issue a refund
4. Visit refund page to see webhook logs

---

## 🌐 Available Merchants

Your gateway has multiple test merchants. Here are the available ones:

### Merchant 1 (Currently Configured) ✅
- **Email:** merchant1@badlicash.test
- **Company:** Acme Corp
- **API Key:** pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp

### Merchant 2 (Alternative)
- **Email:** merchant2@badlicash.test  
- **Company:** Beta Services Inc
- **API Key:** pk_test_0ZgYUOQru48kS38zPGyRWw5y8PRtlsiK
- **Secret:** sk_test_xdmpqToDqvEq6i871TzPcXA5MrtHNTg4

### Merchant 3 (Alternative - Has Live Keys)
- **Company:** Live Commerce Ltd
- **Test API Key:** pk_test_kmaONZf92cETUMRPlyHc4ho8DqQ5jEfm
- **Live API Key:** pk_live_zhED3PuyGx9vAuOhaxfxXbfx8PKMtTKj

To switch merchants, update `config.js` with the desired merchant's API keys.

---

## 🔍 Debugging

### Check API Connection
Open browser console (F12) and look for:
```
🔧 Gateway Configuration Loaded: {
    apiUrl: "http://localhost:8000/api",
    merchantId: "1",
    apiKeySet: true,
    secretKeySet: true
}
```

### Check API Endpoints

Your test app calls these endpoints:

1. **Create Payment**
   ```
   POST http://localhost:8000/api/payments
   Headers: Authorization: Bearer pk_test_...
   ```

2. **Get Payment Status**
   ```
   GET http://localhost:8000/api/payments/{transaction_id}
   ```

3. **Get Webhook Logs**
   ```
   GET http://localhost:8000/api/webhooks/logs/{transaction_id}
   ```

4. **Request Refund**
   ```
   POST http://localhost:8000/api/refunds
   ```

### Common Issues

**CORS Errors:**
- Ensure your Laravel API has CORS enabled
- Check `config/cors.php` in main project
- Verify API URL in `config.js` is correct

**API Key Not Found:**
- Verify merchant has active API keys
- Run: `php get-api-keys.php` in main project to check

**Webhooks Not Showing:**
- Verify webhook events are being created
- Check webhook table in database
- Ensure transaction_id is valid

---

## 🎨 Customization

### Change Product Details
Edit `index.html` - look for the `.products-grid` section.

### Modify Colors/Theme
Edit `css/style.css` - change CSS variables at the top:
```css
:root {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    /* ... more colors */
}
```

### Add More Products
Copy a `.product-card` div and update the details.

---

## 📞 Need Help?

1. **Check the README.md** for detailed documentation
2. **View console logs** in browser (F12)
3. **Check Laravel logs** in `storage/logs/laravel.log`
4. **Verify database** - check `api_keys`, `transactions`, `webhook_events` tables

---

## 🎉 You're All Set!

Your merchant test app is ready to use. Start testing your payment gateway integration!

### Quick Commands:
```bash
# In main project directory
php artisan serve                  # Start Laravel backend

# In merchant-test-app directory  
php -S localhost:8080              # Start test app

# Visit
http://localhost:8080              # Test merchant app
http://localhost:8000              # Gateway dashboard
```

**Happy Testing! 🚀**

