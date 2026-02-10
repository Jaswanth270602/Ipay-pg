# 💰 Commission/Fee Structure - BadliCash Payment Gateway

## 📍 Where Commission is Calculated

### **Main File:** `app/Models/Merchant.php`

**Line 184-188:**

```php
public function calculateFee(float $amount): float
{
    $percentageFee = ($amount * $this->fee_percentage) / 100;
    return round($percentageFee + $this->fee_flat, 2);
}
```

**This is the MAIN commission calculation function!**

---

## 💵 Current Commission Structure

### Default Fee Settings:

**Percentage Fee:** `2.5%` (fee_percentage = 2.5)  
**Flat Fee:** `0` (fee_flat = 0)

### Example Calculation:

**Transaction Amount:** INR 1000.00

```
Step 1: Calculate percentage fee
  = (1000 * 2.5) / 100
  = 25.00

Step 2: Add flat fee
  = 25.00 + 0
  = 25.00

Commission = INR 25.00
Net to Merchant = 1000 - 25 = INR 975.00
```

---

## 🔍 Where Fee is Applied

### 1. **PaymentService.php** (Line 88)

```php
'fee_amount' => $order->merchant->calculateFee($order->amount),
```

**When:** During transaction creation  
**Purpose:** Calculate fee for each transaction

---

### 2. **PaymentSimulationService.php** (Line 121)

```php
$feeAmount = $this->calculateFee($order->amount);
```

**When:** During payment link processing  
**Purpose:** Calculate fee for payment link transactions

---

### 3. **Transaction Model**

Stores the calculated fee in database:
```php
$transaction->fee_amount = 25.00; // Calculated fee
$transaction->net_amount = 975.00; // Amount - Fee
```

---

## ⚙️ How to Change Commission

### Option 1: Change Per Merchant (Recommended)

**Where:** Database table `merchants`

**Fields to update:**
```sql
UPDATE merchants 
SET 
  fee_percentage = 3.0,  -- Change to 3%
  fee_flat = 5.00        -- Add flat fee of 5
WHERE id = 1;
```

**Example:**
- Merchant A: 2.5% + INR 0
- Merchant B: 3.0% + INR 5
- Merchant C: 1.5% + INR 0

---

### Option 2: Change Default for New Merchants

**Where:** `database/seeders/MerchantsTableSeeder.php`

**Line to change:**
```php
'fee_percentage' => 2.5,  // ← Change this
'fee_flat' => 0,          // ← And this
```

---

### Option 3: Change via Admin Panel (Future Enhancement)

**Where to add:** Admin → Merchants → Edit Merchant

**Fields to add:**
- Fee Percentage (%)
- Flat Fee (amount)

**Currently:** Not implemented in UI (only in database)

---

## 🎯 Complete Fee Flow

```
1. Customer pays: INR 1000
        ↓
2. Transaction created
        ↓
3. Fee calculated:
   merchant->calculateFee(1000)
   = (1000 * 2.5%) + 0
   = INR 25.00
        ↓
4. Amounts saved:
   - amount: INR 1000 (gross)
   - fee_amount: INR 25 (commission)
   - net_amount: INR 975 (merchant gets)
        ↓
5. Settlement created:
   - Includes net_amount only
   - Merchant receives: INR 975
        ↓
6. Gateway keeps: INR 25 (commission)
```

---

## 📊 Commission Breakdown in Database

### Merchants Table:
```sql
Column: fee_percentage
Type: decimal(5,2)
Default: 2.50
Example: 2.50 (means 2.5%)

Column: fee_flat
Type: decimal(10,2)  
Default: 0.00
Example: 5.00 (means INR 5.00 flat)
```

### Transactions Table:
```sql
Column: fee_amount
Type: decimal(10,2)
Calculated: merchant->calculateFee(amount)
Example: 25.00 (for INR 1000 transaction)

Column: net_amount
Type: decimal(15,2)
Calculated: amount - fee_amount
Example: 975.00 (merchant receives this)
```

---

## 🔧 To REMOVE Commission (0%)

### For All Future Transactions:

**Method 1: Update All Merchants**
```sql
UPDATE merchants SET fee_percentage = 0, fee_flat = 0;
```

**Method 2: Update Specific Merchant**
```sql
UPDATE merchants 
SET fee_percentage = 0, fee_flat = 0 
WHERE id = 1;  -- Test Merchant A
```

**Method 3: Via Laravel Tinker**
```bash
php artisan tinker
```

```php
$merchant = \App\Models\Merchant::find(1);
$merchant->fee_percentage = 0;
$merchant->fee_flat = 0;
$merchant->save();
echo "Commission removed for {$merchant->name}";
exit;
```

---

## 💡 Different Fee Structures

### Structure 1: Percentage Only (Current)
```
Fee = Amount × 2.5%
Example: INR 1000 → Fee INR 25
```

### Structure 2: Percentage + Flat
```
Fee = (Amount × 2%) + INR 5
Example: INR 1000 → Fee INR 25 (20 + 5)
```

### Structure 3: Flat Only
```
Fee = INR 10 (fixed)
Example: INR 1000 → Fee INR 10
```

### Structure 4: Tiered (Requires Code Change)
```
< INR 500: 3%
INR 500-1000: 2.5%
> INR 1000: 2%
```

---

## 📝 Summary for Manager

### Current Setup:

| Item | Value |
|------|-------|
| **Commission Type** | Percentage-based |
| **Rate** | 2.5% per transaction |
| **Flat Fee** | INR 0 |
| **Calculation** | Automatic on each transaction |
| **Applied When** | Transaction is created |
| **Stored In** | transactions.fee_amount |
| **Merchant Receives** | transaction.net_amount (amount - fee) |

### Example Transaction:

```
Customer Pays: INR 1,000.00
Commission (2.5%): INR 25.00
Merchant Receives: INR 975.00

Commission Goes To: Payment Gateway
```

### To Change Commission:

**Quick Change:**
```bash
php artisan tinker
Merchant::find(1)->update(['fee_percentage' => 0]);
# Commission now 0% for Merchant ID 1
```

**Permanent Change:**
- Update `merchants` table
- Or add Admin UI to change per merchant

---

## 🎯 Key Files Reference

| File | Line | Purpose |
|------|------|---------|
| `app/Models/Merchant.php` | 184-188 | **Main fee calculation** |
| `app/Services/PaymentService.php` | 88 | Applies fee during payment |
| `app/Services/PaymentSimulationService.php` | 121 | Applies fee for payment links |
| `database/migrations/*merchants*.php` | - | Fee column definitions |

---

**This is everything your manager needs to know about commission structure!** 💼


