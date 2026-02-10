# Script to get or create API key for testing
# This will help you find your API key or create a new one

Write-Host "`n🔑 API Key Helper" -ForegroundColor Cyan
Write-Host "=================" -ForegroundColor Cyan
Write-Host ""

# Check if we can access the database via tinker
Write-Host "Checking for existing API keys..." -ForegroundColor Yellow

# Create a temporary PHP script to get API keys
$phpScript = @"
<?php
require __DIR__.'/vendor/autoload.php';
\$app = require_once __DIR__.'/bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all API keys
\$apiKeys = \App\Models\ApiKey::where('status', 'active')
    ->with('merchant')
    ->latest()
    ->take(10)
    ->get();

if (\$apiKeys->count() > 0) {
    echo "Found " . \$apiKeys->count() . " active API key(s):\n\n";
    foreach (\$apiKeys as \$key) {
        echo "ID: " . \$key->id . "\n";
        echo "Key: " . \$key->key . "\n";
        echo "Mode: " . \$key->mode . "\n";
        echo "Merchant: " . (\$key->merchant ? \$key->merchant->name : 'N/A') . "\n";
        echo "Status: " . \$key->status . "\n";
        echo "---\n";
    }
} else {
    echo "No API keys found. You need to create one.\n";
    echo "\nTo create an API key:\n";
    echo "1. Go to: http://127.0.0.1:8000/merchant/api-keys\n";
    echo "2. Login as a merchant\n";
    echo "3. Click 'Create API Key'\n";
    echo "4. Select mode: test\n";
    echo "5. Copy the generated key (starts with pk_test_...)\n";
}
"@

$phpScript | Out-File -FilePath "temp_get_keys.php" -Encoding UTF8

try {
    $output = php temp_get_keys.php 2>&1
    Write-Host $output
} catch {
    Write-Host "Could not run PHP script. Please check manually:" -ForegroundColor Yellow
    Write-Host "1. Go to: http://127.0.0.1:8000/merchant/api-keys" -ForegroundColor White
    Write-Host "2. Login as a merchant" -ForegroundColor White
    Write-Host "3. Create a new API key" -ForegroundColor White
}

# Clean up
Remove-Item "temp_get_keys.php" -ErrorAction SilentlyContinue

Write-Host "`n💡 To create a new API key:" -ForegroundColor Yellow
Write-Host "   1. Go to: http://127.0.0.1:8000/merchant/api-keys" -ForegroundColor White
Write-Host "   2. Login as a merchant (or admin)" -ForegroundColor White
Write-Host "   3. Click 'Create API Key'" -ForegroundColor White
Write-Host "   4. Name: 'Test Key'" -ForegroundColor White
Write-Host "   5. Mode: 'test'" -ForegroundColor White
Write-Host "   6. Copy the key (it starts with pk_test_...)" -ForegroundColor White
Write-Host ""

