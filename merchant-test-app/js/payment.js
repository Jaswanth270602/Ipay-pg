// Payment Gateway Configuration is loaded from config.js
// If config.js is not loaded, use fallback configuration
if (typeof GATEWAY_CONFIG === 'undefined') {
    console.warn('⚠️ config.js not loaded, using fallback configuration');
    var GATEWAY_CONFIG = {
        apiUrl: 'http://localhost:8000/api',
        apiKey: 'YOUR_API_KEY',
        secretKey: 'YOUR_SECRET_KEY',
        merchantId: 'merchant_test_001'
    };
}

// Create Payment
async function createPayment(paymentData) {
    try {
        const response = await fetch(`${GATEWAY_CONFIG.apiUrl}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GATEWAY_CONFIG.apiKey}`,
                'X-Merchant-ID': GATEWAY_CONFIG.merchantId
            },
            body: JSON.stringify({
                amount: paymentData.amount,
                currency: 'USD',
                description: `Payment for ${paymentData.product_name}`,
                customer_email: paymentData.customer_email,
                customer_name: paymentData.customer_name,
                metadata: {
                    product_id: paymentData.product_id,
                    product_name: paymentData.product_name
                },
                return_url: `${window.location.origin}/`,
                webhook_url: `${window.location.origin}/merchant-test-app/webhook.php`
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Payment creation failed');
        }

        return {
            success: true,
            payment_url: data.payment_url || data.checkout_url,
            transaction_id: data.transaction_id || data.id,
            data: data
        };
    } catch (error) {
        console.error('Payment creation error:', error);
        return {
            success: false,
            message: error.message
        };
    }
}

// Get Payment Status
async function getPaymentStatus(transactionId) {
    try {
        const response = await fetch(`${GATEWAY_CONFIG.apiUrl}/payments/${transactionId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${GATEWAY_CONFIG.apiKey}`,
                'X-Merchant-ID': GATEWAY_CONFIG.merchantId
            }
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch payment status');
        }

        return {
            success: true,
            data: data
        };
    } catch (error) {
        console.error('Status fetch error:', error);
        return {
            success: false,
            message: error.message
        };
    }
}

// Get Webhook Logs
async function getWebhookLogs(transactionId) {
    try {
        const response = await fetch(`${GATEWAY_CONFIG.apiUrl}/webhooks/logs/${transactionId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${GATEWAY_CONFIG.apiKey}`,
                'X-Merchant-ID': GATEWAY_CONFIG.merchantId
            }
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch webhook logs');
        }

        return {
            success: true,
            logs: data.logs || data
        };
    } catch (error) {
        console.error('Webhook logs fetch error:', error);
        return {
            success: false,
            message: error.message,
            logs: []
        };
    }
}

// Request Refund
async function requestRefund(transactionId, amount, reason) {
    try {
        const response = await fetch(`${GATEWAY_CONFIG.apiUrl}/refunds`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GATEWAY_CONFIG.apiKey}`,
                'X-Merchant-ID': GATEWAY_CONFIG.merchantId
            },
            body: JSON.stringify({
                transaction_id: transactionId,
                amount: amount,
                reason: reason
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Refund request failed');
        }

        return {
            success: true,
            data: data
        };
    } catch (error) {
        console.error('Refund request error:', error);
        return {
            success: false,
            message: error.message
        };
    }
}

// Verify Webhook Signature (for security)
function verifyWebhookSignature(payload, signature) {
    // Implementation will depend on your gateway's signature method
    // Typically uses HMAC with secret key
    return true; // Placeholder
}

