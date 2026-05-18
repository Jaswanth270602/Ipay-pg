# 📊 Base Rates System - Complete Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [File Structure](#file-structure)
4. [Database Schema](#database-schema)
5. [How It Works](#how-it-works)
6. [Rate Priority System](#rate-priority-system)
7. [Fee Calculation Logic](#fee-calculation-logic)
8. [Admin Interface](#admin-interface)
9. [Integration Points](#integration-points)
10. [Usage Examples](#usage-examples)
11. [Configuration Guide](#configuration-guide)

---

## 🎯 Overview

The **Base Rates System** is a flexible fee configuration system that allows you to set different transaction fees for:
- **Merchants** (merchant-specific rates)
- **Banks** (bank-specific rates)
- **Payment Methods** (card, UPI, netbanking, wallet)
- **Service Types** (payment, refund, chargeback)
- **Transaction Types** (domestic, international)

### Key Benefits
✅ **Flexible Pricing**: Different rates for different merchants/banks  
✅ **Payment Method Specific**: Different rates for cards vs UPI vs netbanking  
✅ **Time-Based**: Effective dates for rate changes  
✅ **Automatic Calculation**: System automatically selects the best rate  
✅ **GST Support**: Built-in GST calculation on fees  

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Base Rates System                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐      ┌──────────────┐                    │
│  │   Admin UI   │─────▶│  Controller  │                    │
│  │  (Blade)     │      │ (BaseRates)  │                    │
│  └──────────────┘      └──────┬───────┘                    │
│                                │                             │
│                                ▼                             │
│                        ┌──────────────┐                      │
│                        │ BaseRate     │                      │
│                        │ Model        │                      │
│                        └──────┬───────┘                      │
│                                │                             │
│                                ▼                             │
│                        ┌──────────────┐                      │
│                        │ BaseRate     │                      │
│                        │ Service      │                      │
│                        └──────┬───────┘                      │
│                                │                             │
│                ┌───────────────┼───────────────┐             │
│                ▼               ▼               ▼             │
│        ┌──────────┐    ┌──────────┐   ┌──────────┐         │
│        │Payment   │    │Payment   │   │Transaction│         │
│        │Service   │    │Simulation│   │  Model    │         │
│        └──────────┘    └──────────┘   └──────────┘         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 File Structure

### 1. **Database Migration**
**Location:** `database/migrations/2025_12_06_072344_create_base_rates_table.php`

**Purpose:** Creates the `base_rates` table in the database

**Key Fields:**
- `rate_type`: merchant, bank, receiver, pricer
- `entity_id`: ID of merchant/bank (nullable for default rates)
- `entity_type`: merchant or bank
- `payment_method`: card, upi, netbanking, wallet
- `service_type`: payment, refund, chargeback
- `transaction_type`: domestic, international
- `percentage_fee`: Percentage fee (e.g., 2.50 for 2.5%)
- `flat_fee`: Flat fee amount
- `gst_percentage`: GST percentage on fees (default 18%)
- `is_active`: Active/inactive status
- `effective_from` / `effective_to`: Date range for rate validity

---

### 2. **BaseRate Model**
**Location:** `app/Models/BaseRate.php`

**Purpose:** Eloquent model for base rates with relationships and helper methods

**Key Methods:**
```php
// Scopes
->active()                    // Get only active rates
->ofType('merchant')          // Filter by rate type
->forPaymentMethod('card')    // Filter by payment method
->forServiceType('payment')   // Filter by service type

// Calculations
$rate->calculateFee($amount)  // Calculate fee for amount
$rate->calculateGST($fee)     // Calculate GST on fee
$rate->isEffective()          // Check if rate is currently effective
```

**Relationships:**
- `merchant()`: BelongsTo relationship with Merchant model
- `bank()`: BelongsTo relationship with Bank model

---

### 3. **BaseRateService**
**Location:** `app/Services/BaseRateService.php`

**Purpose:** Core business logic for rate lookup and fee calculation

**Key Methods:**

#### `getApplicableRate()`
Finds the best rate using priority system:
1. Merchant-specific rate
2. Bank-specific rate  
3. Default rate

```php
$rate = $baseRateService->getApplicableRate(
    $merchant,
    $bank,
    'card',              // payment method
    'payment',          // service type
    'domestic'          // transaction type
);
```

#### `calculateFee()`
Calculates complete fee breakdown:
```php
$result = $baseRateService->calculateFee(
    $merchant,
    1000.00,            // amount
    'card',             // payment method
    $bank,              // bank (optional)
    'payment',          // service type
    'domestic'          // transaction type
);

// Returns:
[
    'fee_amount' => 25.00,      // Base fee
    'gst_amount' => 4.50,       // GST on fee
    'total_fee' => 29.50,       // Total fee
    'rate_id' => 123,           // Rate used
    'rate_type' => 'merchant',  // Type of rate
    'percentage_fee' => 2.5,
    'flat_fee' => 0,
    'gst_percentage' => 18
]
```

#### Helper Methods:
- `getMerchantRates($merchant)`: Get all rates for a merchant
- `getBankRates($bank)`: Get all rates for a bank
- `getDefaultRates()`: Get all default rates
- `createOrUpdateRate($data)`: Create or update a rate

---

### 4. **BaseRatesController**
**Location:** `app/Http/Controllers/Admin/BaseRatesController.php`

**Purpose:** Admin controller for managing base rates

**Routes:**
- `GET /admin/base-rates` - List all rates
- `GET /admin/base-rates/data` - Get rates data (AJAX)
- `POST /admin/base-rates` - Create new rate
- `POST /admin/base-rates/{id}` - Update rate
- `DELETE /admin/base-rates/{id}` - Delete rate
- `GET /admin/base-rates/entities` - Get merchants/banks for dropdowns

---

### 5. **Admin View**
**Location:** `resources/views/admin/base-rates/index.blade.php`

**Purpose:** Admin interface for managing base rates

**Features:**
- Filter by rate type, payment method, service type
- Create/Edit/Delete rates
- View rate details
- Toggle active/inactive status

**Access:** Navigate to `/admin/base-rates` in admin panel

---

### 6. **Integration Points**

#### PaymentService
**Location:** `app/Services/PaymentService.php` (Line ~88)

**Before:**
```php
'fee_amount' => $order->merchant->calculateFee($order->amount),
```

**After:**
```php
$baseRateService = app(\App\Services\BaseRateService::class);
$feeCalculation = $baseRateService->calculateFee(
    $order->merchant,
    $order->amount,
    $paymentData['payment_method'],
    $bank,
    BaseRate::SERVICE_TYPE_PAYMENT,
    BaseRate::TRANSACTION_TYPE_DOMESTIC
);
'fee_amount' => $feeCalculation['fee_amount'],
'gst_amount' => $feeCalculation['gst_amount'],
```

#### PaymentSimulationService
**Location:** `app/Services/PaymentSimulationService.php` (Line ~129)

**Before:**
```php
$feeAmount = $this->calculateFee($order->amount);
$gstAmount = $this->calculateGST($feeAmount);
```

**After:**
```php
$baseRateService = app(\App\Services\BaseRateService::class);
$feeCalculation = $baseRateService->calculateFee(
    $merchant,
    $order->amount,
    $paymentData['payment_method'],
    $bank,
    BaseRate::SERVICE_TYPE_PAYMENT,
    BaseRate::TRANSACTION_TYPE_DOMESTIC
);
$feeAmount = $feeCalculation['fee_amount'];
$gstAmount = $feeCalculation['gst_amount'];
```

---

## 🗄️ Database Schema

### `base_rates` Table

```sql
CREATE TABLE base_rates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    rate_type VARCHAR(255) NOT NULL,           -- merchant, bank, receiver, pricer
    entity_type VARCHAR(255) NULL,            -- merchant, bank
    entity_id BIGINT NULL,                    -- ID of merchant/bank
    payment_method VARCHAR(255) NOT NULL,     -- card, upi, netbanking, wallet
    service_type VARCHAR(255) DEFAULT 'payment', -- payment, refund, chargeback
    transaction_type VARCHAR(255) DEFAULT 'domestic', -- domestic, international
    percentage_fee DECIMAL(5,3) DEFAULT 0,    -- e.g., 2.500 for 2.5%
    flat_fee DECIMAL(10,2) DEFAULT 0,        -- Flat fee amount
    gst_percentage DECIMAL(5,2) DEFAULT 18,  -- GST percentage
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NULL,
    effective_to DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_rate_type (rate_type, entity_type, entity_id),
    INDEX idx_payment_method (payment_method, service_type),
    INDEX idx_is_active (is_active)
);
```

---

## 🔄 How It Works

### Step-by-Step Flow

1. **Transaction Initiated**
   - Customer makes payment
   - System receives payment method, merchant, amount

2. **Rate Lookup**
   - `BaseRateService::getApplicableRate()` is called
   - System checks in priority order:
     a. Merchant-specific rate for this payment method
     b. Bank-specific rate for this payment method
     c. Default rate for this payment method

3. **Fee Calculation**
   - If rate found: Uses rate's percentage_fee and flat_fee
   - If no rate: Falls back to merchant's default fee calculation
   - Calculates GST on fee
   - Returns complete fee breakdown

4. **Transaction Created**
   - Fee amounts stored in transaction record
   - Net amount calculated (amount - total fees)

---

## 🎯 Rate Priority System

The system uses a **priority-based lookup** to find the most appropriate rate:

```
Priority 1: Merchant-Specific Rate (Highest Priority)
├── Merchant ID: 123
├── Payment Method: card
├── Service Type: payment
└── Transaction Type: domestic
    → If found, use this rate

Priority 2: Bank-Specific Rate
├── Bank ID: 5
├── Payment Method: card
├── Service Type: payment
└── Transaction Type: domestic
    → If merchant rate not found, use this

Priority 3: Default Rate (Lowest Priority)
├── No entity_id (NULL)
├── Payment Method: card
├── Service Type: payment
└── Transaction Type: domestic
    → If neither found, use this

Fallback: Merchant Default
└── If no base rate found, use merchant's fee_percentage and fee_flat
```

---

## 💰 Fee Calculation Logic

### Formula

```
Step 1: Calculate Base Fee
base_fee = (amount × percentage_fee / 100) + flat_fee

Step 2: Calculate GST
gst_amount = (base_fee × gst_percentage / 100)

Step 3: Calculate Total Fee
total_fee = base_fee + gst_amount

Step 4: Calculate Net Amount
net_amount = amount - total_fee
```

### Example Calculation

**Transaction:** ₹10,000 via Card Payment

**Rate Found:**
- Percentage Fee: 2.5%
- Flat Fee: ₹0
- GST Percentage: 18%

**Calculation:**
```
Base Fee = (10,000 × 2.5 / 100) + 0 = ₹250.00
GST = 250 × 18 / 100 = ₹45.00
Total Fee = 250 + 45 = ₹295.00
Net Amount = 10,000 - 295 = ₹9,705.00
```

---

## 🖥️ Admin Interface

### Access
**URL:** `/admin/base-rates`  
**Required Role:** Admin

### Features

1. **Filter Rates**
   - By Rate Type (merchant, bank, receiver, pricer)
   - By Payment Method (card, UPI, netbanking, wallet)
   - By Service Type (payment, refund, chargeback)
   - By Active Status

2. **Create Rate**
   - Select rate type
   - Select merchant/bank (if applicable)
   - Choose payment method
   - Set percentage fee and flat fee
   - Set GST percentage
   - Set effective dates
   - Add notes

3. **Edit Rate**
   - Update all rate parameters
   - Change active status
   - Modify effective dates

4. **Delete Rate**
   - Remove rate from system
   - Cannot delete if referenced in transactions

---

## 🔌 Integration Points

### Where Base Rates Are Used

1. **PaymentService** (`app/Services/PaymentService.php`)
   - Used when processing payments through API
   - Calculates fees for new transactions

2. **PaymentSimulationService** (`app/Services/PaymentSimulationService.php`)
   - Used when processing payment links
   - Calculates fees for simulated payments

3. **Transaction Model** (`app/Models/Transaction.php`)
   - Stores calculated fees
   - Has `calculateFee()` method (fallback)

---

## 📝 Usage Examples

### Example 1: Set Merchant-Specific Rate

**Scenario:** Merchant "ABC Store" wants 2.0% fee for card payments

**Steps:**
1. Go to `/admin/base-rates`
2. Click "Create New Rate"
3. Select:
   - Rate Type: `merchant`
   - Entity: `ABC Store`
   - Payment Method: `card`
   - Service Type: `payment`
   - Transaction Type: `domestic`
   - Percentage Fee: `2.0`
   - Flat Fee: `0`
   - GST Percentage: `18`
4. Save

**Result:** All card payments from ABC Store will use 2.0% fee

---

### Example 2: Set Bank-Specific Rate

**Scenario:** Bank "HDFC" charges 1.8% for UPI transactions

**Steps:**
1. Go to `/admin/base-rates`
2. Click "Create New Rate"
3. Select:
   - Rate Type: `bank`
   - Entity: `HDFC Bank`
   - Payment Method: `upi`
   - Service Type: `payment`
   - Transaction Type: `domestic`
   - Percentage Fee: `1.8`
   - Flat Fee: `0`
   - GST Percentage: `18`
4. Save

**Result:** All UPI payments through HDFC will use 1.8% fee

---

### Example 3: Set Default Rate

**Scenario:** Default rate for all card payments is 2.5%

**Steps:**
1. Go to `/admin/base-rates`
2. Click "Create New Rate"
3. Select:
   - Rate Type: `merchant` (or any type)
   - Entity: Leave empty (NULL)
   - Payment Method: `card`
   - Service Type: `payment`
   - Transaction Type: `domestic`
   - Percentage Fee: `2.5`
   - Flat Fee: `0`
   - GST Percentage: `18`
4. Save

**Result:** All card payments without merchant/bank-specific rates will use 2.5%

---

### Example 4: Time-Based Rate Change

**Scenario:** Increase fee from 2.5% to 3.0% starting January 1, 2026

**Steps:**
1. Create new rate with:
   - Percentage Fee: `3.0`
   - Effective From: `2026-01-01`
   - Effective To: Leave empty (no expiry)
2. Deactivate old rate or set Effective To: `2025-12-31`

**Result:** System automatically switches to new rate on January 1, 2026

---

## ⚙️ Configuration Guide

### Setting Up Base Rates

1. **Access Admin Panel**
   - Login as admin
   - Navigate to `/admin/base-rates`

2. **Create Default Rates First**
   - Set default rates for each payment method
   - These will be used as fallback

3. **Create Merchant-Specific Rates**
   - For merchants with special pricing
   - Overrides default rates

4. **Create Bank-Specific Rates**
   - For banks with different fee structures
   - Used when merchant rate not found

5. **Test Rates**
   - Create test transaction
   - Verify correct rate is applied
   - Check fee calculation

---

## 📊 Rate Types Explained

### 1. Merchant Rate (`merchant`)
- Specific to a merchant
- Highest priority
- Use case: Special pricing for VIP merchants

### 2. Bank Rate (`bank`)
- Specific to a bank
- Medium priority
- Use case: Different rates for different bank partnerships

### 3. Receiver Rate (`receiver`)
- For future use (receiver entities)
- Currently not implemented

### 4. Pricer Rate (`pricer`)
- For future use (pricing entities)
- Currently not implemented

---

## 🔍 Troubleshooting

### Rate Not Being Applied

**Check:**
1. Rate is active (`is_active = true`)
2. Effective dates are correct
3. Payment method matches
4. Service type matches
5. Transaction type matches
6. Merchant/Bank ID is correct

### Fee Calculation Wrong

**Check:**
1. Percentage fee is correct (e.g., 2.5 not 25)
2. Flat fee is correct
3. GST percentage is correct
4. Rate priority (merchant > bank > default)

### Rate Not Found

**Solution:**
- Create default rate for the payment method
- System will fallback to merchant's default fee if no base rate found

---

## 📈 Future Enhancements

Potential improvements:
- [ ] Bulk rate import/export (CSV)
- [ ] Rate history/audit trail
- [ ] Rate templates
- [ ] Automatic rate adjustments
- [ ] Rate comparison tools
- [ ] Rate analytics dashboard

---

## 📞 Support

For questions or issues:
1. Check this documentation
2. Review code comments in files
3. Check database for rate configuration
4. Test with sample transactions

---

## ✅ Summary

The Base Rates System provides:
- ✅ Flexible fee configuration
- ✅ Merchant and bank-specific rates
- ✅ Payment method differentiation
- ✅ Automatic rate selection
- ✅ GST calculation
- ✅ Time-based rate changes
- ✅ Admin interface for management

**All files are located in:**
- Models: `app/Models/BaseRate.php`
- Service: `app/Services/BaseRateService.php`
- Controller: `app/Http/Controllers/Admin/BaseRatesController.php`
- View: `resources/views/admin/base-rates/index.blade.php`
- Migration: `database/migrations/2025_12_06_072344_create_base_rates_table.php`

**Access:** `/admin/base-rates`

---

*Documentation created: December 2025*  
*Last updated: December 2025*

