# 💸 Refund Testing Guide - BadliCash Payment Gateway

## 📋 Overview

This guide will walk you through testing the refund functionality in both TEST and LIVE modes.

---

## ✅ Prerequisites

Before testing refunds, you need:
1. ✅ At least ONE **successful transaction** in TEST mode
2. ✅ The transaction must have status: **"SUCCESS"**
3. ✅ The transaction should NOT be fully refunded already

---

## 🎯 Step-by-Step Refund Testing

### Step 1: Create a Successful Test Transaction

If you don't have one already:

1. **Go to Payment Links** page
2. **Create a new payment link**:
   - Title: "Refund Test"
   - Amount: 100
   - Currency: EUR (or INR)

3. **Copy the payment link** and open it
4. **Complete the payment** using test card:
   - Card: `4111 1111 1111 1111`
   - Name: Test User
   - Expiry: 12/25
   - CVV: 123

5. **Verify success** - You should see "Payment Successful"

---

### Step 2: Navigate to Refunds Page

1. **Login to dashboard** (test@merchant.com / password123)
2. **Ensure you're in TEST MODE** (yellow badge in top-right)
3. **Click on "Refunds"** in the left sidebar

---

### Step 3: Create a Refund

#### Method A: From Refunds Page (Manual Entry)

1. **Click "Create Refund"** button (top-right)
2. **Modal will open** with a form
3. **Find your transaction ID**:
   - Go to **Transactions** page
   - Copy the **Transaction ID** (e.g., `TXN_TEST_1234...`)
   - Note the transaction **Amount** (e.g., EUR 100.00)

4. **Fill the refund form**:
   ```
   Transaction ID: TXN_TEST_... (paste from step 3)
   Amount: 50.00 (for partial) or 100.00 (for full refund)
   Reason: Customer requested refund (or any reason)
   ```

5. **Click "Create Refund"**

---

### Step 4: What Happens Next

#### Refund Processing:

1. **System validates**:
   - ✅ Transaction exists
   - ✅ Transaction was successful
   - ✅ Refund amount ≤ refundable amount
   - ✅ In correct mode (test keys for test mode)

2. **Refund is created** with status:
   - In **TEST mode**: Usually "completed" immediately (simulated)
   - In **LIVE mode**: "pending" → "processing" → "completed"

3. **You'll see**:
   - Success message
   - New refund appears in the refunds table

---

### Step 5: Verify the Refund

#### Check Refunds Page:

1. **Refunds Table** should show:
   - ✅ Refund ID (e.g., `REF_TEST_...`)
   - ✅ Transaction ID
   - ✅ Amount refunded
   - ✅ Currency
   - ✅ Status: `COMPLETED` (green badge)
   - ✅ Reason you entered
   - ✅ Created date/time

#### Check Transactions Page:

1. Go to **Transactions**
2. Find the original transaction
3. Click the **eye icon** (view details)
4. **Look for**:
   - Payment Details section
   - Should show refund information
   - Refundable amount reduced

---

## 🧪 Different Refund Scenarios

### Scenario 1: Full Refund ✅

**Purpose:** Refund the entire transaction amount

**Steps:**
1. Transaction Amount: `EUR 100.00`
2. Refund Amount: `100.00` (full amount)
3. Result: Transaction fully refunded

**Expected:**
- ✅ Status: Completed
- ✅ Is Partial: false
- ✅ Refundable Amount: 0.00

---

### Scenario 2: Partial Refund ✅

**Purpose:** Refund only part of the amount

**Steps:**
1. Transaction Amount: `EUR 100.00`
2. Refund Amount: `50.00` (half)
3. Result: Transaction partially refunded

**Expected:**
- ✅ Status: Completed
- ✅ Is Partial: true
- ✅ Refundable Amount: 50.00

**Can create another refund?** YES! Up to remaining 50.00

---

### Scenario 3: Multiple Partial Refunds ✅

**Purpose:** Test multiple refunds on same transaction

**Example:**
1. **Original Transaction:** EUR 100.00
2. **First Refund:** EUR 30.00 → Remaining: 70.00
3. **Second Refund:** EUR 20.00 → Remaining: 50.00
4. **Third Refund:** EUR 50.00 → Remaining: 0.00 (Fully refunded)

**Expected:**
- ✅ All 3 refunds show in refunds table
- ✅ Each with "completed" status
- ✅ Transaction shows all refunds in details

---

### Scenario 4: Refund Validation Errors ❌

#### Test A: Amount Too High
```
Transaction: EUR 100.00
Already Refunded: EUR 30.00
Attempt to Refund: EUR 80.00
```
**Expected Error:** "Refund amount exceeds refundable amount. Maximum: 70.00"

#### Test B: Invalid Transaction ID
```
Transaction ID: TXN_INVALID_123
```
**Expected Error:** "Transaction not found or does not match your API key mode"

#### Test C: Failed Transaction
```
Transaction Status: FAILED
```
**Expected Error:** "Cannot refund unsuccessful transaction"

---

## 🔄 Refund Status Flow

### Test Mode (Simulated):
```
pending → completed (instant)
```

### Live Mode (Real):
```
pending → processing → completed
                   ↓
                 failed (if gateway rejects)
```

---

## 📊 Refund Status Meanings

| Status | Badge Color | Description |
|--------|-------------|-------------|
| **PENDING** | Yellow | Refund created, waiting to process |
| **PROCESSING** | Blue | Being processed by payment gateway |
| **COMPLETED** | Green | Refund successful, money returned |
| **FAILED** | Red | Refund failed, check error details |

---

## 🎨 Visual Guide

### Refunds Page Layout:

```
┌─────────────────────────────────────────┐
│  [+ Create Refund] Button               │
├─────────────────────────────────────────┤
│  Filters:                               │
│  [ Status ▼ ] [ From Date ] [ To Date ] │
│  [ Search... ]                          │
├─────────────────────────────────────────┤
│  Refunds Table:                         │
│  ┌────┬──────────┬──────────┬────────┐ │
│  │ #  │ Ref ID   │ Txn ID   │ Amount │ │
│  ├────┼──────────┼──────────┼────────┤ │
│  │ 1  │ REF_...  │ TXN_...  │ 50.00  │ │
│  │ 2  │ REF_...  │ TXN_...  │ 30.00  │ │
│  └────┴──────────┴──────────┴────────┘ │
└─────────────────────────────────────────┘
```

---

## 🚨 Common Issues & Solutions

### Issue 1: "Transaction not found"
**Cause:** Wrong transaction ID or mode mismatch  
**Solution:** 
- Copy exact Transaction ID from Transactions page
- Ensure you're in TEST mode for test transactions

### Issue 2: "Cannot refund unsuccessful transaction"
**Cause:** Transaction status is not "success"  
**Solution:** Only successful transactions can be refunded

### Issue 3: "Refund amount exceeds refundable amount"
**Cause:** Trying to refund more than available  
**Solution:** Check how much has already been refunded

### Issue 4: No refunds showing
**Cause:** Mode mismatch or filters applied  
**Solution:** 
- Switch to TEST mode
- Click "Clear Filters"
- Check test transactions exist

---

## 🔐 Test Mode vs Live Mode

### TEST Mode (Current):
- ✅ Uses test transaction data
- ✅ Refunds process instantly
- ✅ No real money involved
- ✅ Safe to experiment
- ✅ Shows only test mode refunds

### LIVE Mode:
- ⚠️ Requires live credentials configured
- ⚠️ Refunds process via real gateway
- ⚠️ Real money is refunded
- ⚠️ Cannot be undone
- ✅ Shows only live mode refunds

---

## 📝 Quick Test Checklist

- [ ] Create successful test transaction
- [ ] Navigate to Refunds page
- [ ] Click "Create Refund"
- [ ] Enter transaction ID
- [ ] Enter amount (try 50% of original)
- [ ] Add reason
- [ ] Submit refund
- [ ] Verify refund appears with "COMPLETED" status
- [ ] Check transaction details modal
- [ ] Try creating another partial refund
- [ ] Verify total refunded = sum of all refunds

---

## 💡 Pro Tips

1. **Keep Transaction IDs handy** - Copy them to notepad
2. **Test partial refunds first** - Leaves room for multiple tests
3. **Check transaction details** - View refund history there
4. **Use descriptive reasons** - Helps with tracking
5. **Test in TEST mode first** - Always!

---

## 📞 API Testing (Optional)

You can also test refunds via API:

```bash
curl -X POST http://localhost/api/v1/refunds \
  -H "X-API-Key: pk_test_YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "TXN_TEST_1234567890",
    "amount": 50.00,
    "reason": "Customer requested refund"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "refund_id": "REF_TEST_...",
    "transaction_id": "TXN_TEST_...",
    "amount": 50.00,
    "status": "pending",
    "is_partial": true,
    "created_at": "2025-12-03T12:00:00Z"
  }
}
```

---

## 🎯 Summary

**To test a refund:**
1. Make a successful payment (EUR 100)
2. Go to **Refunds** → Click **Create Refund**
3. Enter **Transaction ID** from Transactions page
4. Enter **Amount** (e.g., 50.00 for partial)
5. Enter **Reason** (e.g., "Testing refund")
6. Click **Create**
7. ✅ Refund should show as **COMPLETED** instantly

**That's it!** You've successfully tested refunds! 🎉

---

## 📚 Next Steps

After testing refunds:
- ✅ Test settlements
- ✅ Test webhooks
- ✅ Test reports
- ✅ Configure LIVE mode credentials
- ✅ Test in LIVE mode (with caution!)

---

**Need help? Check the logs:**
```
storage/logs/laravel.log
```


