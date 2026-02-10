# Badlicash Payment Gateway - Merchant Test App

A beautiful, modern test application to demonstrate and test the Badlicash Payment Gateway integration.

## 🚀 Features

- **Modern UI/UX**: Beautiful, responsive design with smooth animations
- **Multiple Products**: Test payments with different product types and prices
- **Complete Payment Flow**: From product selection to payment completion
- **Real-time Webhook Logs**: View all webhook events in real-time
- **Status Pages**: Dedicated pages for Success, Failure, Pending, and Refund states
- **Auto-refresh**: Automatically updates payment status and webhook logs

## 📁 Project Structure

```
merchant-test-app/
├── index.html          # Main store page with products
├── success.html        # Payment success page with webhook logs
├── failure.html        # Payment failure page with error details
├── pending.html        # Payment pending page with status checker
├── refund.html         # Payment refund page
├── config.js           # Configuration file (API keys, URLs)
├── css/
│   └── style.css       # Main stylesheet with modern design
├── js/
│   ├── app.js          # Main application logic
│   └── payment.js      # Payment gateway integration
└── README.md           # This file
```

## 🔧 Setup Instructions

### 1. Configure API Keys

Open `config.js` and update the following:

```javascript
const GATEWAY_CONFIG = {
    apiUrl: 'http://localhost:8000/api',     // Your gateway API URL
    apiKey: 'YOUR_API_KEY',                   // Your merchant API key
    secretKey: 'YOUR_SECRET_KEY',             // Your merchant secret key
    merchantId: 'merchant_test_001'           // Your merchant ID
};
```

### 2. Serve the Application

You can use any local server. Here are some options:

**Option 1: Python**
```bash
# Python 3
python -m http.server 8080

# Python 2
python -m SimpleHTTPServer 8080
```

**Option 2: PHP**
```bash
php -S localhost:8080
```

**Option 3: Node.js (with http-server)**
```bash
npx http-server -p 8080
```

**Option 4: VS Code Live Server**
- Install "Live Server" extension
- Right-click on `index.html`
- Select "Open with Live Server"

### 3. Access the App

Open your browser and navigate to:
```
http://localhost:8080
```

## 🎯 How to Test

### Basic Payment Flow

1. **Select a Product**: Click on any product card on the home page
2. **Enter Details**: Fill in customer email and name in the modal
3. **Confirm Payment**: Click "Confirm & Pay"
4. **Process Payment**: You'll be redirected to the payment gateway
5. **View Results**: After payment, you'll be redirected to the appropriate status page

### Testing Different Scenarios

#### ✅ Success Payment
- Complete a payment normally
- View success page with webhook logs
- Check transaction details

#### ❌ Failed Payment
- Use test card details that trigger failure
- View failure page with error messages
- Check webhook logs for failure events

#### ⏳ Pending Payment
- Use test scenarios that create pending status
- Page auto-refreshes and checks status
- Automatically redirects when status changes

#### 💰 Refund
- After successful payment, request a refund through the gateway
- View refund page with refund details
- Check webhook logs for refund events

## 🔗 API Integration Points

The app integrates with your payment gateway through these endpoints:

1. **Create Payment**
   - `POST /api/payments`
   - Creates a new payment transaction

2. **Get Payment Status**
   - `GET /api/payments/{transaction_id}`
   - Retrieves payment details and status

3. **Get Webhook Logs**
   - `GET /api/webhooks/logs/{transaction_id}`
   - Fetches webhook events for a transaction

4. **Request Refund**
   - `POST /api/refunds`
   - Initiates a refund for a transaction

## 🎨 Customization

### Changing Colors

Edit `css/style.css` and modify the CSS variables:

```css
:root {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
}
```

### Adding Products

Edit `index.html` and add a new product card in the `.products-grid` section.

### Modifying Payment Flow

Edit `js/app.js` to customize the payment initiation and handling logic.

## 🔐 Security Notes

- Never commit your actual API keys to version control
- Use environment-specific configuration files
- Implement proper CORS settings on your gateway API
- Validate webhook signatures for production use

## 📊 Webhook Event Types

The app displays these webhook events:

- `payment.created` - Payment initiated
- `payment.processing` - Payment being processed
- `payment.success` - Payment completed successfully
- `payment.failed` - Payment failed
- `payment.pending` - Payment awaiting confirmation
- `payment.refunded` - Payment refunded

## 🐛 Troubleshooting

### CORS Errors
- Ensure your gateway API has proper CORS headers
- Check that the API URL in `config.js` is correct

### Payment Not Processing
- Verify API keys are correct
- Check browser console for errors
- Ensure the gateway API is running

### Webhook Logs Not Showing
- Check that webhook endpoint is configured
- Verify the transaction ID is valid
- Check gateway logs for webhook delivery status

## 📝 License

This is a test application for the Badlicash Payment Gateway.

## 🤝 Support

For issues or questions, please contact your Badlicash support team.

