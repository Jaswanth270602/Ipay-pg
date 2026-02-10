# Quick Razorpay Test - Single Payment Creation
# Usage: .\test-razorpay-quick.ps1

# ============================================
# CONFIGURATION
# ============================================
$API_KEY = "pk_test_YOUR_API_KEY_HERE"  # ⚠️ UPDATE THIS!
$BASE_URL = "http://127.0.0.1:8000"

# ============================================
# SCRIPT
# ============================================

Write-Host "`n🚀 Razorpay Quick Test" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan

# Validate API Key
if ($API_KEY -eq "pk_test_YOUR_API_KEY_HERE") {
    Write-Host "`n❌ ERROR: Please update API_KEY in the script!" -ForegroundColor Red
    Write-Host "Edit this file and replace 'pk_test_YOUR_API_KEY_HERE' with your actual API key." -ForegroundColor Yellow
    exit 1
}

# Create payment
Write-Host "`n📝 Creating payment..." -ForegroundColor Yellow

$paymentData = @{
    amount = 100.00
    currency = "INR"
    description = "Quick Razorpay Test - $(Get-Date -Format 'HH:mm:ss')"
    customer_name = "Test Customer"
    customer_email = "test@example.com"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/api/payments" `
        -Method POST `
        -Headers @{
            "X-API-Key" = $API_KEY
            "Content-Type" = "application/json"
        } `
        -Body $paymentData
    
    Write-Host "`n✅ SUCCESS! Payment created!" -ForegroundColor Green
    Write-Host "`nPayment Details:" -ForegroundColor Cyan
    Write-Host "  Amount: $($response.currency) $($response.amount)" -ForegroundColor White
    Write-Host "  Link Token: $($response.link_token)" -ForegroundColor White
    Write-Host "  Payment URL: $($response.payment_url)" -ForegroundColor White
    
    # Open in browser
    Write-Host "`n🌐 Opening payment page..." -ForegroundColor Yellow
    Start-Process $response.payment_url
    
    Write-Host "`n💡 TIP: Use Razorpay test card:" -ForegroundColor Cyan
    Write-Host "   Card: 4111 1111 1111 1111" -ForegroundColor White
    Write-Host "   CVV: 123" -ForegroundColor White
    Write-Host "   Expiry: 12/25" -ForegroundColor White
    Write-Host ""
    Write-Host "📊 After completing payment, view transaction at:" -ForegroundColor Yellow
    Write-Host "   Admin: $BASE_URL/admin/payments/transactions" -ForegroundColor Cyan
    Write-Host "   Merchant: $BASE_URL/merchant/transactions" -ForegroundColor Cyan
    
} catch {
    Write-Host "`n❌ ERROR: Failed to create payment!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Response: $responseBody" -ForegroundColor Red
    }
    
    Write-Host "`n💡 Troubleshooting:" -ForegroundColor Yellow
    Write-Host "  1. Check if Laravel server is running: php artisan serve" -ForegroundColor White
    Write-Host "  2. Verify API key is correct" -ForegroundColor White
    Write-Host "  3. Check if merchant is linked to Razorpay account" -ForegroundColor White
}

Write-Host "`n"

