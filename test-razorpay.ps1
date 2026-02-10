# Razorpay Integration Test Script
# This script helps you test Razorpay payment gateway integration

# ============================================
# CONFIGURATION - UPDATE THESE VALUES
# ============================================
$API_KEY = "pk_test_YOUR_API_KEY_HERE"  # Replace with your actual API key
$BASE_URL = "http://127.0.0.1:8000"
$TEST_AMOUNT = 100.00
$TEST_CURRENCY = "INR"

# Colors for output
function Write-Success { param($msg) Write-Host $msg -ForegroundColor Green }
function Write-Error { param($msg) Write-Host $msg -ForegroundColor Red }
function Write-Info { param($msg) Write-Host $msg -ForegroundColor Cyan }
function Write-Warning { param($msg) Write-Host $msg -ForegroundColor Yellow }

# ============================================
# STEP 1: Create Payment
# ============================================
function Test-CreatePayment {
    Write-Info "`n=== STEP 1: Creating Payment ==="
    
    $paymentData = @{
        amount = $TEST_AMOUNT
        currency = $TEST_CURRENCY
        description = "Razorpay Test Payment - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
        customer_name = "Test Customer"
        customer_email = "test@example.com"
        return_url = "$BASE_URL/payment/success"
        cancel_url = "$BASE_URL/payment/failed"
    } | ConvertTo-Json
    
    try {
        Write-Info "Sending request to: $BASE_URL/api/payments"
        Write-Info "Request body: $paymentData"
        
        $response = Invoke-RestMethod -Uri "$BASE_URL/api/payments" `
            -Method POST `
            -Headers @{
                "X-API-Key" = $API_KEY
                "Content-Type" = "application/json"
            } `
            -Body $paymentData `
            -ErrorAction Stop
        
        Write-Success "`n✅ Payment created successfully!"
        Write-Info "Payment URL: $($response.payment_url)"
        Write-Info "Link Token: $($response.link_token)"
        Write-Info "Amount: $($response.currency) $($response.amount)"
        
        return $response
    }
    catch {
        Write-Error "`n❌ Failed to create payment!"
        Write-Error "Error: $($_.Exception.Message)"
        
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Error "Response: $responseBody"
        }
        
        return $null
    }
}

# ============================================
# STEP 2: Check Payment Status
# ============================================
function Test-PaymentStatus {
    param($transactionId)
    
    Write-Info "`n=== STEP 2: Checking Payment Status ==="
    
    if (-not $transactionId) {
        Write-Warning "No transaction ID provided. Skipping status check."
        return
    }
    
    try {
        $response = Invoke-RestMethod -Uri "$BASE_URL/api/payments/$transactionId" `
            -Method GET `
            -Headers @{
                "X-API-Key" = $API_KEY
            } `
            -ErrorAction Stop
        
        Write-Success "`n✅ Payment status retrieved!"
        Write-Info "Status: $($response.status)"
        Write-Info "Amount: $($response.currency) $($response.amount)"
        Write-Info "Transaction ID: $($response.transaction_id)"
        
        return $response
    }
    catch {
        Write-Error "`n❌ Failed to get payment status!"
        Write-Error "Error: $($_.Exception.Message)"
        return $null
    }
}

# ============================================
# STEP 3: Test Refund
# ============================================
function Test-Refund {
    param($paymentId, $amount)
    
    Write-Info "`n=== STEP 3: Testing Refund ==="
    
    if (-not $paymentId) {
        Write-Warning "No payment ID provided. Skipping refund test."
        return
    }
    
    $refundData = @{
        payment_id = $paymentId
        amount = if ($amount) { $amount } else { $TEST_AMOUNT }
        reason = "Test refund from PowerShell script"
    } | ConvertTo-Json
    
    try {
        Write-Info "Sending refund request..."
        
        $response = Invoke-RestMethod -Uri "$BASE_URL/api/v1/refunds" `
            -Method POST `
            -Headers @{
                "X-API-Key" = $API_KEY
                "Content-Type" = "application/json"
            } `
            -Body $refundData `
            -ErrorAction Stop
        
        Write-Success "`n✅ Refund created successfully!"
        Write-Info "Refund ID: $($response.refund_id)"
        Write-Info "Amount: $($response.currency) $($response.amount)"
        Write-Info "Status: $($response.status)"
        
        return $response
    }
    catch {
        Write-Error "`n❌ Failed to create refund!"
        Write-Error "Error: $($_.Exception.Message)"
        
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Error "Response: $responseBody"
        }
        
        return $null
    }
}

# ============================================
# STEP 4: List Recent Transactions
# ============================================
function Test-ListTransactions {
    Write-Info "`n=== STEP 4: Listing Recent Transactions ==="
    
    try {
        $response = Invoke-RestMethod -Uri "$BASE_URL/api/v1/transactions" `
            -Method GET `
            -Headers @{
                "X-API-Key" = $API_KEY
            } `
            -ErrorAction Stop
        
        Write-Success "`n✅ Transactions retrieved!"
        
        if ($response.data -and $response.data.Count -gt 0) {
            Write-Info "`nRecent Transactions:"
            $response.data | Select-Object -First 5 | ForEach-Object {
                Write-Info "  - ID: $($_.transaction_id) | Status: $($_.status) | Amount: $($_.currency) $($_.amount)"
            }
        } else {
            Write-Warning "No transactions found."
        }
        
        return $response
    }
    catch {
        Write-Error "`n❌ Failed to list transactions!"
        Write-Error "Error: $($_.Exception.Message)"
        return $null
    }
}

# ============================================
# MAIN MENU
# ============================================
function Show-Menu {
    Write-Host "`n" -NoNewline
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "   Razorpay Integration Test Script    " -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Current Configuration:" -ForegroundColor Yellow
    Write-Host "  API Key: $API_KEY" -ForegroundColor White
    Write-Host "  Base URL: $BASE_URL" -ForegroundColor White
    Write-Host "  Test Amount: $TEST_CURRENCY $TEST_AMOUNT" -ForegroundColor White
    Write-Host ""
    Write-Host "Available Tests:" -ForegroundColor Yellow
    Write-Host "  1. Create Payment (Full Flow)" -ForegroundColor White
    Write-Host "  2. Create Payment Only" -ForegroundColor White
    Write-Host "  3. Check Payment Status" -ForegroundColor White
    Write-Host "  4. Test Refund" -ForegroundColor White
    Write-Host "  5. List Recent Transactions" -ForegroundColor White
    Write-Host "  6. Run All Tests" -ForegroundColor White
    Write-Host "  0. Exit" -ForegroundColor White
    Write-Host ""
}

# ============================================
# MAIN EXECUTION
# ============================================
function Main {
    # Check if API key is set
    if ($API_KEY -eq "pk_test_YOUR_API_KEY_HERE") {
        Write-Error "`n❌ ERROR: Please update the API_KEY variable in this script!"
        Write-Info "Edit the script and replace 'pk_test_YOUR_API_KEY_HERE' with your actual API key."
        Write-Info "You can get your API key from: $BASE_URL/admin/api-keys"
        return
    }
    
    # Check if server is running
    try {
        $testResponse = Invoke-WebRequest -Uri "$BASE_URL" -Method GET -TimeoutSec 3 -ErrorAction Stop
        Write-Success "✅ Server is running at $BASE_URL"
    }
    catch {
        Write-Error "`n❌ ERROR: Cannot connect to server at $BASE_URL"
        Write-Info "Please make sure Laravel server is running:"
        Write-Info "  php artisan serve"
        return
    }
    
    $continue = $true
    $lastPaymentResponse = $null
    
    while ($continue) {
        Show-Menu
        $choice = Read-Host "Select an option (0-6)"
        
        switch ($choice) {
            "1" {
                # Create Payment (Full Flow)
                $paymentResponse = Test-CreatePayment
                if ($paymentResponse) {
                    $lastPaymentResponse = $paymentResponse
                    Write-Info "`nOpening payment page in browser..."
                    Start-Process $paymentResponse.payment_url
                    Write-Info "`n💡 TIP: Use Razorpay test card: 4111 1111 1111 1111"
                }
            }
            "2" {
                # Create Payment Only
                $paymentResponse = Test-CreatePayment
                if ($paymentResponse) {
                    $lastPaymentResponse = $paymentResponse
                }
            }
            "3" {
                # Check Payment Status
                if ($lastPaymentResponse -and $lastPaymentResponse.link_token) {
                    $transactionId = Read-Host "Enter Transaction ID (or press Enter to use last payment)"
                    if ([string]::IsNullOrWhiteSpace($transactionId)) {
                        # Try to extract transaction ID from last payment
                        $transactionId = $lastPaymentResponse.transaction_id
                    }
                } else {
                    $transactionId = Read-Host "Enter Transaction ID"
                }
                Test-PaymentStatus -transactionId $transactionId
            }
            "4" {
                # Test Refund
                $paymentId = Read-Host "Enter Payment ID (Razorpay payment ID)"
                $refundAmount = Read-Host "Enter Refund Amount (or press Enter for full refund)"
                if ([string]::IsNullOrWhiteSpace($refundAmount)) {
                    Test-Refund -paymentId $paymentId
                } else {
                    Test-Refund -paymentId $paymentId -amount [decimal]$refundAmount
                }
            }
            "5" {
                # List Transactions
                Test-ListTransactions
            }
            "6" {
                # Run All Tests
                Write-Info "`n=== Running All Tests ==="
                $paymentResponse = Test-CreatePayment
                if ($paymentResponse) {
                    $lastPaymentResponse = $paymentResponse
                    Start-Sleep -Seconds 2
                    Test-ListTransactions
                }
            }
            "0" {
                Write-Success "`n👋 Goodbye!"
                $continue = $false
            }
            default {
                Write-Warning "`nInvalid option. Please select 0-6."
            }
        }
        
        if ($continue) {
            Write-Host "`n" -NoNewline
            Read-Host "Press Enter to continue"
        }
    }
}

# Run the script
Main

