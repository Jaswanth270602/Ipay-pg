# 🏦 Settlement Testing Guide - BadliCash

## ✅ Settlement Test Data Created!

I just created **3 test settlements** for you:

### Settlement #1: Pending
- **Settlement ID:** STL_XCOVOCC3ACHHG3JB
- **Transactions:** 5
- **Net Amount:** INR 1,192.03
- **Status:** PENDING
- **Action:** Ready to be marked as settled

### Settlement #2: Processing  
- **Settlement ID:** STL_GKPODX8JHXOUCMVY
- **Transactions:** 3
- **Net Amount:** INR 589.56
- **Status:** PROCESSING
- **Action:** Being processed

### Settlement #3: Completed
- **Settlement ID:** STL_4DLMFLAK6N4KPMEA
- **Transactions:** 2
- **Net Amount:** INR 254.76
- **Status:** COMPLETED
- **UTR:** UTR186842859 (sample)
- **Action:** Already settled (reference)

---

## 🎯 How Settlements Work

### What is a Settlement?

**Settlement** = Transferring money from the payment gateway to the merchant's bank account.

**Process:**
```
Day 1: Customer pays → Transaction successful
       ↓
Day 2-3: Settlement period (T+2, T+7, etc.)
       ↓
Day 3: Gateway transfers net amount to merchant
       ↓
Settlement Status: PENDING → PROCESSING → COMPLETED
```

---

## 📊 Settlement Calculation

### Example Settlement:

**Transactions Included:**
```
Transaction 1: INR 500.00
  - Fee (2.5%): INR 12.50
  - Net: INR 487.50

Transaction 2: INR 300.00
  - Fee (2.5%): INR 7.50
  - Net: INR 292.50

Transaction 3: INR 200.00
  - Fee (2.5%): INR 5.00
  - Net: INR 195.00
```

**Settlement Totals:**
```
Gross Amount: INR 1,000.00
Total Fees: INR 25.00
Net Settlement Amount: INR 975.00 ← Paid to merchant
```

---

## 🧪 How to Test Settlements

### Step 1: Login as Admin (TEST MODE)

1. **Login:** admin@badlicash.com / password
2. **Switch to:** TEST MODE (yellow button)
3. **Navigate:** Admin → **Settlements** → **Settlement Summary**

---

### Step 2: View Pending Settlements

You'll see a table with:

**Columns:**
- Settlement ID
- Merchant Name
- Transaction Count
- Amount
- Net Amount  
- Settlement Date
- Status
- Actions

**Your Test Data:**
```
Settlement ID: STL_XCOVOCC3ACHHG3JB
Merchant: Test Merchant A
Transactions: 5
Amount: INR 1,192.03
Status: PENDING 🟡
Settlement Date: Dec 5, 2025 (T+2)
```

---

### Step 3: Mark Settlement as Settled

1. **Find pending settlement** in the table
2. **Click "Mark as Settled"** button/action
3. **Enter UTR Number:** (Bank reference)
   - Example: `UTR123456789`
4. **Confirm**

**Result:**
- ✅ Status changes to: COMPLETED 🟢
- ✅ UTR number saved
- ✅ Processed timestamp recorded
- ✅ Merchant can see settlement in their dashboard

---

### Step 4: View Settlement Details

1. **Navigate:** Admin → **Settlements** → **Settlement Details**
2. **View individual transaction details** within settlements
3. **See breakdown** of fees, amounts, etc.

---

### Step 5: View in Merchant Dashboard

1. **Logout from admin**
2. **Login as merchant:** test@merchant.com / password123
3. **Switch to:** TEST MODE
4. **Go to:** Merchant → **Settlements**
5. **You should see:**
   - Your completed settlements
   - Net amounts payable
   - Settlement dates
   - UTR numbers

---

## 📋 Settlement Statuses

| Status | Badge | Meaning | Action |
|--------|-------|---------|--------|
| **PENDING** | 🟡 Yellow | Waiting for settlement date | Can mark as settled |
| **PROCESSING** | 🔵 Blue | Being transferred to bank | Wait for completion |
| **COMPLETED** | 🟢 Green | Money transferred | View UTR |
| **FAILED** | 🔴 Red | Transfer failed | Retry or investigate |
| **ON_HOLD** | ⚪ Gray | Held for review | Admin action needed |

---

## 🔍 Admin Settlement Pages

### 1. Settlement Summary
**Location:** Admin → Settlements → Settlement Summary

**Shows:**
- List of all settlements
- Grouped by status
- Summary totals
- Quick actions (mark as settled)

**Use:** Overview of all settlements

---

### 2. Settlement Details
**Location:** Admin → Settlements → Settlement Details

**Shows:**
- Individual transactions within settlements
- Detailed breakdown
- Fee calculations
- Refund adjustments

**Use:** Deep dive into settlement composition

---

### 3. Fund Transfer
**Location:** Admin → Settlements → Fund Transfer

**Shows:**
- Settlements ready for fund transfer
- Bank account details
- Transfer records

**Use:** Initiate actual bank transfers

---

### 4. Pending Settlement
**Location:** Admin → Manage Settlements → Pending Settlement

**Shows:**
- Transactions waiting to be included in settlements
- Group by merchant
- Create new settlements

**Use:** Manage unsettled transactions

---

### 5. MIS Report
**Location:** Admin → Manage Settlements → Download MIS Report

**Shows:**
- Management Information System reports
- Settlement statistics
- Download options

**Use:** Generate reports for management

---

## 🎯 Test Scenarios

### Scenario 1: Basic Settlement Flow

1. ✅ View pending settlement
2. ✅ Click "Mark as Settled"
3. ✅ Enter UTR: `UTR123456789`
4. ✅ Status changes to COMPLETED
5. ✅ Merchant sees settlement in dashboard

---

### Scenario 2: Settlement with Refunds

1. Create transaction: INR 1000
2. Process refund: INR 200
3. Settlement calculates:
   ```
   Gross: INR 1000
   Refund: INR 200
   Fees: INR 25 (on 1000)
   Net: INR 775 (1000 - 200 - 25)
   ```

---

### Scenario 3: Multiple Settlements

1. Settlement 1: 5 transactions (Dec 1-3)
2. Settlement 2: 3 transactions (Dec 4-6)
3. Settlement 3: 2 transactions (Dec 7-8)
4. Each settled separately with different UTRs

---

## 💡 Settlement Best Practices

### For Testing:

1. **Use TEST mode** - No real money
2. **Create successful transactions first** - Can't settle failed ones
3. **Wait for settlement date** (or bypass in test)
4. **Mark as settled** with valid UTR
5. **Verify in merchant dashboard**

### For Production (LIVE mode):

1. **Set settlement schedule** (T+2, T+7, etc.)
2. **Verify bank details** before settling
3. **Use real UTR numbers** from bank
4. **Double-check amounts** before marking settled
5. **Keep records** of all settlements

---

## 📝 Quick Test Checklist

- [ ] Login as admin (TEST MODE)
- [ ] Go to Settlements → Settlement Summary
- [ ] See 3 test settlements
- [ ] Click on pending settlement
- [ ] Mark as settled with UTR
- [ ] Verify status changes to COMPLETED
- [ ] Login as merchant
- [ ] Switch to TEST MODE
- [ ] Go to Settlements
- [ ] See completed settlement

---

## 🚀 Start Testing Now:

1. **Refresh admin dashboard** (F5)
2. **Ensure TEST MODE** (yellow badge)
3. **Navigate:** Settlements → Settlement Summary
4. **You'll see:** 3 test settlements
5. **Start:** Mark the pending one as settled!

---

## 📊 Settlement Data Structure

```
Settlement {
  settlement_id: "STL_...",
  merchant_id: 1,
  amount: 1192.03,          // Gross
  fee_amount: 30.56,        // Total fees
  refund_amount: 0,         // Total refunds
  net_amount: 1161.47,      // What merchant gets
  transaction_count: 5,
  status: "pending",
  settlement_date: "2025-12-05",
  utr_number: null,         // Added when marked settled
  processed_at: null        // Timestamp when completed
}
```

---

## ✨ Summary

✅ **Created:** 3 test settlements  
✅ **Pending:** 1 (ready to mark as settled)  
✅ **Processing:** 1 (being processed)  
✅ **Completed:** 1 (reference example)  
✅ **Total Amount:** INR 2,036.35  
✅ **TEST MODE:** Safe to experiment  

**Go to Admin → Settlements → Settlement Summary to start testing!** 🏦


