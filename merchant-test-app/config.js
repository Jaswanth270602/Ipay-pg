/**
 * Badlicash Payment Gateway Configuration
 * 
 * Update these values with your actual gateway credentials
 */

const GATEWAY_CONFIG = {
    // Gateway API Base URL
    // Local development: http://localhost:8000/api
    // Production: https://your-gateway-domain.com/api
    apiUrl: 'http://localhost:8000/api',
    
    // Merchant API Key (Public Key)
    // Merchant: Acme Corp (merchant1@badlicash.test)
    apiKey: 'pk_test_88tdbeR269j8EKABtx0dA34vV4foXzBp',
    
    // Merchant Secret Key (Private Key)
    // Keep this secure and never expose in client-side code in production
    // This is only for testing purposes
    secretKey: 'sk_test_SdBNftTovx41kANBayBSWZQQFsnuvSgo',
    
    // Merchant ID
    // Your unique merchant identifier
    merchantId: '1',
    
    // Return URLs
    // Where users are redirected after payment
    returnUrl: window.location.origin + '/merchant-test-app/',
    
    // Webhook URL (for receiving payment notifications)
    // In production, this should be a server endpoint
    webhookUrl: window.location.origin + '/merchant-test-app/webhook',
    
    // Auto-refresh intervals (in milliseconds)
    statusCheckInterval: 5000,  // Check payment status every 5 seconds
    webhookRefreshInterval: 3000, // Refresh webhook logs every 3 seconds
    
    // Debug mode
    debug: true
};

// Log configuration on load (only in debug mode)
if (GATEWAY_CONFIG.debug) {
    console.log('🔧 Gateway Configuration Loaded:', {
        apiUrl: GATEWAY_CONFIG.apiUrl,
        merchantId: GATEWAY_CONFIG.merchantId,
        apiKeySet: GATEWAY_CONFIG.apiKey !== 'YOUR_API_KEY_HERE',
        secretKeySet: GATEWAY_CONFIG.secretKey !== 'YOUR_SECRET_KEY_HERE'
    });
}

// Validate configuration
function validateConfig() {
    const warnings = [];
    
    if (GATEWAY_CONFIG.apiKey === 'YOUR_API_KEY_HERE') {
        warnings.push('⚠️ API Key not configured');
    }
    
    if (GATEWAY_CONFIG.secretKey === 'YOUR_SECRET_KEY_HERE') {
        warnings.push('⚠️ Secret Key not configured');
    }
    
    if (warnings.length > 0 && GATEWAY_CONFIG.debug) {
        console.warn('Configuration warnings:', warnings);
    }
    
    return warnings.length === 0;
}

// Export configuration
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GATEWAY_CONFIG, validateConfig };
}

