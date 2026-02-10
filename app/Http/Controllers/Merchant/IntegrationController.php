<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        $merchant = auth()->user()->merchant;
        $apiKeys = $merchant->apiKeys()->where('status', 'active')->latest()->get();
        
        return view('merchant.integration.index', compact('merchant', 'apiKeys'));
    }

    public function getIntegrationCode(Request $request)
    {
        $request->validate([
            'type' => 'required|in:widget,iframe,redirect,webhook',
            'api_key_id' => 'required|exists:api_keys,id'
        ]);

        $merchant = auth()->user()->merchant;
        $apiKey = $merchant->apiKeys()->where('id', $request->api_key_id)->firstOrFail();

        $baseUrl = config('app.url');
        $code = '';

        switch ($request->type) {
            case 'widget':
                $code = $this->getWidgetCode($apiKey, $baseUrl);
                break;
            case 'iframe':
                $code = $this->getIframeCode($apiKey, $baseUrl);
                break;
            case 'redirect':
                $code = $this->getRedirectCode($apiKey, $baseUrl);
                break;
            case 'webhook':
                $code = $this->getWebhookCode($baseUrl);
                break;
        }

        return response()->json([
            'success' => true,
            'code' => $code
        ]);
    }

    private function getWidgetCode($apiKey, $baseUrl)
    {
        $companyName = config('app.name');
        return <<<HTML
<!-- {$companyName} Payment Widget -->
<script src="{$baseUrl}/sdk/badlicash.js"></script>
<script>
    const badlicash = new BadliCash({
        key: '{$apiKey->key}',
        mode: '{$apiKey->mode}'
    });
    
    // Initialize payment
    badlicash.open({
        amount: 1000, // Amount in paise/cents
        currency: 'INR',
        order_id: 'order_123',
        name: 'Product Name',
        description: 'Product Description',
        prefill: {
            name: 'Customer Name',
            email: 'customer@example.com',
            phone: '9876543210'
        },
        handler: function(response) {
            console.log('Payment successful:', response);
            // Handle success
        },
        onClose: function() {
            console.log('Payment closed');
        }
    });
</script>
HTML;
    }

    private function getIframeCode($apiKey, $baseUrl)
    {
        return <<<HTML
<iframe 
    src="{$baseUrl}/payment/iframe?key={$apiKey->key}&amount=1000&order_id=order_123"
    width="100%" 
    height="600" 
    frameborder="0">
</iframe>
HTML;
    }

    private function getRedirectCode($apiKey, $baseUrl)
    {
        return <<<PHP
// Redirect to payment page
\$paymentUrl = '{$baseUrl}/payment/checkout?key={$apiKey->key}&amount=1000&order_id=order_123';
return redirect(\$paymentUrl);
PHP;
    }

    private function getWebhookCode($baseUrl)
    {
        $companyName = config('app.name');
        $headerName = 'X-' . str_replace(' ', '-', $companyName) . '-Signature';
        return <<<PHP
// Webhook endpoint
Route::post('/webhook/badlicash', function (Request \$request) {
    \$payload = \$request->all();
    \$signature = \$request->header('{$headerName}');
    
    // Verify signature
    \$expectedSignature = hash_hmac('sha256', json_encode(\$payload), config('badlicash.webhook_secret'));
    
    if (!hash_equals(\$expectedSignature, \$signature)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }
    
    // Process webhook
    switch (\$payload['event']) {
        case 'payment.success':
            // Handle successful payment
            break;
        case 'payment.failed':
            // Handle failed payment
            break;
    }
    
    return response()->json(['status' => 'ok']);
});
PHP;
    }
}

