# Complete Test Script - Create Payment and Show Where to View Transaction
# This script creates a payment and tells you exactly where to see the transaction

Write-Host "`n" -NoNewline
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   Razorpay Complete Test Script       " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# CONFIGURATION
# ============================================
$API_KEY = "pk_test_YOUR_API_KEY_HERE"  # ⚠️ UPDATE THIS!
$BASE_URL = "http://127.0.0.1:8000"

# ============================================
# VALIDATION
# ============================================
if ($API_KEY -eq "pk_test_YOUR_API_KEY_HERE") {
    Write-Host "❌ ERROR: Please update API_KEY in this script!" -ForegroundColor Red
    Write-Host "Edit this file and replace 'pk_test_YOUR_API_KEY_HERE' with your actual API key." -ForegroundColor Yellow
    Write-Host "`nYou can get your API key from:" -ForegroundColor Yellow
    Write-Host "  $BASE_URL/admin/api-keys" -ForegroundColor White
    exit 1
}

# Check if server is running
try {
    $testResponse = Invoke-WebRequest -Uri "$BASE_URL" -Method GET -TimeoutSec 3 -ErrorAction Stop
    Write-Host "✅ Server is running at $BASE_URL" -ForegroundColor Green
} catch {
    Write-Host "❌ ERROR: Cannot connect to server at $BASE_URL" -ForegroundColor Red
    Write-Host "`nPlease start Laravel server:" -ForegroundColor Yellow
    Write-Host "  php artisan serve" -ForegroundColor White
    exit 1
}

# ============================================
# STEP 1: CREATE PAYMENT
# ============================================
Write-Host "`n📝 STEP 1: Creating Payment..." -ForegroundColor Yellow

$paymentData = @{
    amount = 100.00
    currency = "INR"
    description = "Razorpay Test Payment - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
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
        -Body $paymentData `
        -ErrorAction Stop
    
    Write-Host "✅ Payment created successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Payment Details:" -ForegroundColor Cyan
    Write-Host "  Amount: $($response.currency) $($response.amount)" -ForegroundColor White
    Write-Host "  Link Token: $($response.link_token)" -ForegroundColor White
    Write-Host "  Payment URL: $($response.payment_url)" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host "❌ Failed to create payment!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Response: $responseBody" -ForegroundColor Red
    }
    
    Write-Host "`n💡 Troubleshooting:" -ForegroundColor Yellow
    Write-Host "  1. Check if API key is correct" -ForegroundColor White
    Write-Host "  2. Verify merchant is linked to Razorpay account" -ForegroundColor White
    Write-Host "  3. Check Laravel logs: storage/logs/laravel.log" -ForegroundColor White
    exit 1
}

# ============================================
# STEP 2: OPEN PAYMENT PAGE
# ============================================
Write-Host "🌐 STEP 2: Opening payment page in browser..." -ForegroundColor Yellow
Start-Sleep -Seconds 1
Start-Process $response.payment_url

# ============================================
# STEP 3: INSTRUCTIONS
# ============================================
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   NEXT STEPS - Follow These Exactly   " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "📋 On the Payment Page:" -ForegroundColor Yellow
Write-Host "  1. Fill customer details:" -ForegroundColor White
Write-Host "     - Name: Test Customer" -ForegroundColor Gray
Write-Host "     - Email: test@example.com" -ForegroundColor Gray
Write-Host "     - Phone: 9876543210" -ForegroundColor Gray
Write-Host ""
Write-Host "  2. Select Payment Method: Card" -ForegroundColor White
Write-Host ""
Write-Host "  3. Enter card details:" -ForegroundColor White
Write-Host "     - Card Number: 4111 1111 1111 1111" -ForegroundColor Gray
Write-Host "     - Card Holder: Test User" -ForegroundColor Gray
Write-Host "     - Expiry Month: 12" -ForegroundColor Gray
Write-Host "     - Expiry Year: 2025" -ForegroundColor Gray
Write-Host "     - CVV: 123" -ForegroundColor Gray
Write-Host ""
Write-Host "  4. Click 'Pay' or 'Submit'" -ForegroundColor White
Write-Host ""

Write-Host "⏳ After Payment:" -ForegroundColor Yellow
Write-Host "  - You'll be redirected to success/failure page" -ForegroundColor White
Write-Host "  - A transaction will be created in the database" -ForegroundColor White
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   WHERE TO VIEW TRANSACTION           " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "📍 Option 1: Admin Dashboard" -ForegroundColor Yellow
Write-Host "  URL: $BASE_URL/admin/payments/transactions" -ForegroundColor Cyan
Write-Host "  OR: $BASE_URL/admin/dashboard → Payments → Transactions" -ForegroundColor Cyan
Write-Host ""

Write-Host "📍 Option 2: Merchant Dashboard" -ForegroundColor Yellow
Write-Host "  URL: $BASE_URL/merchant/transactions" -ForegroundColor Cyan
Write-Host "  OR: $BASE_URL/merchant/dashboard → Transactions" -ForegroundColor Cyan
Write-Host ""

Write-Host "📍 Option 3: Direct Database Check" -ForegroundColor Yellow
Write-Host "  Run: php artisan tinker" -ForegroundColor Cyan
Write-Host "  Then: \App\Models\Transaction::latest()->first()" -ForegroundColor Cyan
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   WHAT YOU SHOULD SEE                 " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "In the Transactions table, you should see:" -ForegroundColor White
Write-Host "  ✅ Transaction ID (txn_...)" -ForegroundColor Green
Write-Host "  ✅ Amount: ₹100.00" -ForegroundColor Green
Write-Host "  ✅ Status: success/failed" -ForegroundColor Green
Write-Host "  ✅ Payment Method: Card" -ForegroundColor Green
Write-Host "  ✅ Merchant Name" -ForegroundColor Green
Write-Host "  ✅ Date/Time" -ForegroundColor Green
Write-Host ""

Write-Host "💡 TIP: If transaction doesn't appear:" -ForegroundColor Yellow
Write-Host "  1. Make sure you completed the payment form" -ForegroundColor White
Write-Host "  2. Check you're viewing TEST mode (if using test API key)" -ForegroundColor White
Write-Host "  3. Clear filters in the transactions page" -ForegroundColor White
Write-Host "  4. Check Laravel logs: storage/logs/laravel.log" -ForegroundColor White
Write-Host ""

Write-Host "✅ Test script completed!" -ForegroundColor Green
Write-Host "   Payment page should be open in your browser." -ForegroundColor White
Write-Host "   Complete the payment to see transaction in dashboard." -ForegroundColor White
Write-Host ""

