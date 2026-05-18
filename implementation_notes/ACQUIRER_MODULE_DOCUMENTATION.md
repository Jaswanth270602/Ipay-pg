# Acquirer Details Module - Complete Documentation

## Overview

The Acquirer Details module is a comprehensive admin-side feature for managing acquirer accounts, bulk uploads, and rate configurations. This module consists of three sub-modules:

1. **Acquirer Accounts** - Manage individual acquirer account configurations
2. **Acquirer Account Details Upload** - Bulk upload acquirer accounts via CSV files
3. **Acquirer Rates** - Configure rates and fees for acquirer accounts

---

## Database Schema

### 1. `acquirer_accounts` Table

Stores individual acquirer account configurations.

```sql
CREATE TABLE `acquirer_accounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `account_id` VARCHAR(255) UNIQUE NOT NULL,
    `acquirer_name` VARCHAR(255) NOT NULL,
    `team` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `whitelist_url` VARCHAR(500) NULL,
    `mode` ENUM('TEST', 'LIVE') DEFAULT 'TEST',
    `sector` VARCHAR(255) NULL,
    `hdfc_me_code` VARCHAR(255) NULL,
    `settlement_account_name` VARCHAR(255) NULL,
    `refund_allowed` BOOLEAN DEFAULT TRUE,
    `settlements_to_be_created` BOOLEAN DEFAULT TRUE,
    `mask_pii` BOOLEAN DEFAULT FALSE,
    `email_ids` TEXT NULL,
    `secret_key` VARCHAR(255) NULL,
    `salt` VARCHAR(255) NULL,
    `additional_key_1` VARCHAR(255) NULL,
    `additional_key_2` VARCHAR(255) NULL,
    `additional_key_3` VARCHAR(255) NULL,
    `additional_key_data` TEXT NULL,
    `live_request_url` VARCHAR(500) NULL,
    `live_query_url` VARCHAR(500) NULL,
    `live_refund_url` VARCHAR(500) NULL,
    `test_request_url` VARCHAR(500) NULL,
    `test_query_url` VARCHAR(500) NULL,
    `test_refund_url` VARCHAR(500) NULL,
    `nodal_account` VARCHAR(255) NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    INDEX `idx_acquirer_name` (`acquirer_name`),
    INDEX `idx_mode` (`mode`),
    INDEX `idx_sector` (`sector`),
    INDEX `idx_is_active` (`is_active`)
);
```

**Relationships:**
- `acquirer_account_merchant` (pivot table) - Many-to-many relationship with `merchants`

### 2. `acquirer_account_merchant` Table (Pivot)

Links acquirer accounts to merchants.

```sql
CREATE TABLE `acquirer_account_merchant` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `acquirer_account_id` BIGINT UNSIGNED NOT NULL,
    `merchant_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`acquirer_account_id`) REFERENCES `acquirer_accounts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_acquirer_merchant` (`acquirer_account_id`, `merchant_id`)
);
```

### 3. `acquirer_account_upload_jobs` Table

Tracks bulk upload jobs for acquirer accounts.

```sql
CREATE TABLE `acquirer_account_upload_jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `payment_mode` VARCHAR(255) NULL,
    `bank_codes` JSON NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `progress` INT DEFAULT 0,
    `error` TEXT NULL,
    `status_info` TEXT NULL,
    `export_file_path` VARCHAR(500) NULL,
    `started_at` TIMESTAMP NULL,
    `finished_at` TIMESTAMP NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
);
```

### 4. `acquirer_rates` Table

Stores rate configurations for acquirer accounts.

```sql
CREATE TABLE `acquirer_rates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `acquirer_account_id` BIGINT UNSIGNED NOT NULL,
    `payment_mode` VARCHAR(255) NOT NULL,
    `bank_code` VARCHAR(50) NULL,
    `bank_description` VARCHAR(255) NULL,
    `acquirer_name` VARCHAR(255) NOT NULL,
    `account_id` VARCHAR(255) NOT NULL,
    `account_description` VARCHAR(255) NULL,
    `sector` VARCHAR(255) NULL,
    `settlement_time_frame` VARCHAR(20) DEFAULT 't+1',
    `settlement_time_of_day` VARCHAR(50) NULL,
    `fixed_fee_mdr` DECIMAL(10,4) DEFAULT 0,
    `percentage_mdr` DECIMAL(8,4) DEFAULT 0,
    `service_tax_rates` DECIMAL(8,4) DEFAULT 0,
    `min_amount` DECIMAL(15,2) NULL,
    `max_amount` DECIMAL(15,2) NULL,
    `min_transaction_charge` DECIMAL(10,2) NULL,
    `max_transaction_charge` DECIMAL(10,2) NULL,
    `is_enabled` BOOLEAN DEFAULT TRUE,
    `part_paid_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`acquirer_account_id`) REFERENCES `acquirer_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_acquirer_account_id` (`acquirer_account_id`),
    INDEX `idx_payment_mode` (`payment_mode`),
    INDEX `idx_bank_code` (`bank_code`),
    INDEX `idx_acquirer_name` (`acquirer_name`),
    INDEX `idx_is_enabled` (`is_enabled`)
);
```

---

## Database Schema Diagram

```
┌─────────────────────┐
│  acquirer_accounts  │
├─────────────────────┤
│ id (PK)             │
│ account_id (UNIQUE) │
│ acquirer_name       │
│ mode                │
│ sector              │
│ ... (other fields)  │
└──────────┬──────────┘
           │
           │ 1:N
           │
┌──────────▼──────────┐      ┌──────────────────────┐
│  acquirer_rates     │      │ acquirer_account_    │
├─────────────────────┤      │   _merchant (pivot)   │
│ id (PK)             │      ├──────────────────────┤
│ acquirer_account_id │      │ acquirer_account_id   │
│ payment_mode         │      │ merchant_id          │
│ bank_code            │      └──────────────────────┘
│ ... (rate fields)   │
└─────────────────────┘

┌─────────────────────────────┐
│ acquirer_account_upload_    │
│         jobs                │
├─────────────────────────────┤
│ id (PK)                     │
│ job_name                    │
│ file_path                   │
│ payment_mode                │
│ bank_codes (JSON)           │
│ status                      │
│ progress                    │
│ ... (job tracking fields)   │
└─────────────────────────────┘
```

---

## Models

### 1. `AcquirerAccount` Model

**Location:** `app/Models/AcquirerAccount.php`

**Relationships:**
- `merchants()` - BelongsToMany relationship with `Merchant` model
- `rates()` - HasMany relationship with `AcquirerRate` model

**Key Methods:**
- `getMerchantsListAttribute()` - Returns comma-separated merchant names

**Fillable Fields:**
- All fields from the `acquirer_accounts` table

### 2. `AcquirerRate` Model

**Location:** `app/Models/AcquirerRate.php`

**Relationships:**
- `acquirerAccount()` - BelongsTo relationship with `AcquirerAccount` model

**Fillable Fields:**
- All fields from the `acquirer_rates` table

---

## Controllers

### 1. `AcquirerAccountsController`

**Location:** `app/Http/Controllers/Admin/AcquirerAccountsController.php`

**Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| `index()` | GET `/admin/acquirer-accounts` | Display acquirer accounts page |
| `getData()` | GET `/admin/acquirer-accounts/data` | Get paginated acquirer accounts data |
| `getAcquirerNames()` | GET `/admin/acquirer-accounts/acquirer-names` | Get list of acquirer names for dropdown |
| `getMerchants()` | GET `/admin/acquirer-accounts/merchants` | Get list of merchants for dropdown |
| `store()` | POST `/admin/acquirer-accounts` | Create new acquirer account |
| `update()` | PUT `/admin/acquirer-accounts/{id}` | Update acquirer account |
| `destroy()` | DELETE `/admin/acquirer-accounts/{id}` | Delete acquirer account |

**Key Features:**
- Filtering by acquirer name, mode, sector, team
- Search functionality
- Sorting capabilities
- Pagination support
- Merchant association management

### 2. `AcquirerAccountUploadController`

**Location:** `app/Http/Controllers/Admin/AcquirerAccountUploadController.php`

**Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| `index()` | GET `/admin/acquirer-account-upload` | Display upload page |
| `getPaymentModes()` | GET `/admin/acquirer-account-upload/payment-modes` | Get list of payment modes |
| `getBanksByPaymentMode()` | GET `/admin/acquirer-account-upload/banks` | Get banks filtered by payment mode |
| `upload()` | POST `/admin/acquirer-account-upload/upload` | Upload CSV file and create job |
| `getJobs()` | GET `/admin/acquirer-account-upload/jobs` | Get paginated upload jobs |
| `downloadStatusFile()` | GET `/admin/acquirer-account-upload/download-status/{id}` | Download job status file |
| `downloadTemplate()` | GET `/admin/acquirer-account-upload/download-template` | Download CSV template |

**Key Features:**
- Payment mode selection (27 modes)
- Bank code multi-select with auto-selection
- CSV file upload (CSV, XLSX, XLS)
- Background job processing
- Job status tracking with progress
- Comprehensive bank list (30+ banks)

**CSV Template Columns:**
```
acquirer name, account id, secret key, salt, additional key1, additional key2, 
additional key3, additional keys, description, white list url, mode, sector, 
nodal account, live request url, live query url, live refund url, test request url, 
test query url, test refund url, hdfc me code, is refund allowed, is settlement allowed, 
email ids, reference account id for duplicating account detail, merchant ids, rate, type, 
owner team name
```

### 3. `AcquirerRatesController`

**Location:** `app/Http/Controllers/Admin/AcquirerRatesController.php`

**Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| `index()` | GET `/admin/acquirer-rates` | Display acquirer rates page |
| `getData()` | GET `/admin/acquirer-rates/data` | Get paginated acquirer rates data |
| `getAcquirerAccounts()` | GET `/admin/acquirer-rates/acquirer-accounts` | Get acquirer accounts for dropdown |
| `getAcquirerNames()` | GET `/admin/acquirer-rates/acquirer-names` | Get acquirer names for dropdown |
| `getPaymentModes()` | GET `/admin/acquirer-rates/payment-modes` | Get payment modes for dropdown |
| `getBanks()` | GET `/admin/acquirer-rates/banks` | Get banks for dropdown |
| `store()` | POST `/admin/acquirer-rates` | Create new acquirer rate |
| `update()` | PUT `/admin/acquirer-rates/{id}` | Update acquirer rate |
| `destroy()` | DELETE `/admin/acquirer-rates/{id}` | Delete acquirer rate |
| `duplicate()` | POST `/admin/acquirer-rates/{id}/duplicate` | Duplicate acquirer rate |

**Key Features:**
- Filtering by payment mode, bank code, acquirer name, sector
- Column visibility toggle
- Rate configuration with TDR, MDR, and transaction limits
- Settlement time frame configuration
- Duplicate functionality

---

## Jobs

### `ProcessAcquirerAccountUploadJob`

**Location:** `app/Jobs/ProcessAcquirerAccountUploadJob.php`

**Purpose:** Process CSV files asynchronously to create/update acquirer accounts

**Features:**
- Reads CSV file row by row
- Maps CSV columns to database fields
- Validates required fields
- Creates new accounts or updates existing ones
- Tracks progress (0-100%)
- Handles errors gracefully
- Updates job status and error messages

**CSV Processing:**
- Supports flexible column naming (handles variations like `additional_key1` vs `additional_key_1`)
- Boolean parsing for `is_refund_allowed` and `is_settlement_allowed`
- Progress tracking for large files
- Error collection and reporting

---

## Routes

All routes are under the `/admin` prefix and require admin authentication.

### Acquirer Accounts Routes

```php
GET    /admin/acquirer-accounts                    - Display accounts page
GET    /admin/acquirer-accounts/data              - Get accounts data (JSON)
GET    /admin/acquirer-accounts/acquirer-names    - Get acquirer names (JSON)
GET    /admin/acquirer-accounts/merchants         - Get merchants (JSON)
POST   /admin/acquirer-accounts                   - Create account
PUT    /admin/acquirer-accounts/{id}              - Update account
DELETE /admin/acquirer-accounts/{id}              - Delete account
```

### Acquirer Account Upload Routes

```php
GET    /admin/acquirer-account-upload                    - Display upload page
GET    /admin/acquirer-account-upload/payment-modes      - Get payment modes (JSON)
GET    /admin/acquirer-account-upload/banks               - Get banks by payment mode (JSON)
POST   /admin/acquirer-account-upload/upload              - Upload CSV file
GET    /admin/acquirer-account-upload/jobs                - Get upload jobs (JSON)
GET    /admin/acquirer-account-upload/download-status/{id} - Download status file
GET    /admin/acquirer-account-upload/download-template   - Download CSV template
```

### Acquirer Rates Routes

```php
GET    /admin/acquirer-rates                    - Display rates page
GET    /admin/acquirer-rates/data               - Get rates data (JSON)
GET    /admin/acquirer-rates/acquirer-accounts  - Get acquirer accounts (JSON)
GET    /admin/acquirer-rates/acquirer-names     - Get acquirer names (JSON)
GET    /admin/acquirer-rates/payment-modes      - Get payment modes (JSON)
GET    /admin/acquirer-rates/banks              - Get banks (JSON)
POST   /admin/acquirer-rates                    - Create rate
PUT    /admin/acquirer-rates/{id}               - Update rate
DELETE /admin/acquirer-rates/{id}              - Delete rate
POST   /admin/acquirer-rates/{id}/duplicate    - Duplicate rate
```

---

## Views

### 1. Acquirer Accounts View

**Location:** `resources/views/admin/acquirer/accounts.blade.php`

**Features:**
- Comprehensive table with 20+ columns
- Column visibility toggle
- Filtering by multiple criteria
- Create/Edit/Delete functionality
- Responsive table with horizontal scrolling
- AngularJS integration for dynamic interactions

**Angular Controller:** `resources/views/admin/acquirer/angular/accounts_controller.blade.php`

### 2. Acquirer Account Upload View

**Location:** `resources/views/admin/acquirer/detail-upload.blade.php`

**Features:**
- Payment mode dropdown (27 options)
- Bank code multi-select with:
  - Auto-selection when payment mode is selected
  - Search functionality
  - Select All / Deselect All buttons
  - Visual badges with remove buttons
- File upload field
- Download template button
- Jobs listing table with:
  - Progress bars
  - Status badges
  - Download status file button
  - Filters and pagination
  - Auto-refresh for processing jobs

**Angular Controller:** `resources/views/admin/acquirer/angular/upload_controller.blade.php`

### 3. Acquirer Rates View

**Location:** `resources/views/admin/acquirer/rates.blade.php`

**Features:**
- Comprehensive rates table with 20 columns
- Column visibility toggle
- Filtering by payment mode, bank code, acquirer name, sector
- Create/Edit/Delete/Duplicate functionality
- Modal form with all rate configuration fields
- Responsive table with horizontal scrolling

**Angular Controller:** `resources/views/admin/acquirer/angular/rates_controller.blade.php`

**Modal Partial:** `resources/views/admin/acquirer/partials/rate-modal.blade.php`

---

## Payment Modes

The system supports 27 payment modes:

1. ATM Card
2. Bank Transfer
3. BBPS
4. Bharat QR
5. Bharat QR(Static)
6. Cardless EMI
7. Cash Card
8. Commercial Credit Card
9. Credit Card
10. Debit Card
11. Debit Pin
12. Direct EMI
13. E-Collect
14. EazyPay
15. EMI
16. Enach
17. International Credit Card
18. International Debit Card
19. Netbanking
20. PayLater
21. Peer to Peer
22. Pharmarack Credit Card
23. POS
24. Prepaid Card
25. UPI
26. Wallet
27. WhatsApp

---

## Bank List

The system includes 30+ banks with payment method associations:

**Major Banks:**
- HDFC Bank
- ICICI Bank
- State Bank of India
- Axis Bank
- Kotak Mahindra Bank
- Yes Bank
- Punjab National Bank
- Bank of Baroda
- Bank of India
- Union Bank of India
- Canara Bank
- And many more...

**Bank Features:**
- Each bank is associated with multiple payment modes
- Banks are filtered based on selected payment mode
- Bank names are formatted based on payment mode (e.g., "Bank Name ATM Card" when ATM Card is selected)

---

## Usage Examples

### Creating an Acquirer Account

1. Navigate to **Admin > Acquirer Details > Acquirer Accounts**
2. Click **"New"** button
3. Fill in the form:
   - Select Team
   - Select Acquirer (e.g., A2Pay, Paytm, Switch)
   - Enter Account Id
   - Configure API URLs (Live and Test)
   - Set Mode (TEST/LIVE)
   - Configure settings (Refund Allowed, Settlements, etc.)
   - Select associated merchants
4. Click **"Save"**

### Bulk Uploading Acquirer Accounts

1. Navigate to **Admin > Acquirer Details > Acquirer Accounts Detail Upload**
2. Select Payment Mode (e.g., "ATM Card")
3. Banks are auto-selected - remove unwanted banks using cross buttons
4. Click **"Download Template"** to get CSV template
5. Fill the CSV with acquirer account data
6. Click **"Select Acquirer Account File"** and choose your CSV
7. Click **"Upload File"**
8. Monitor progress in the jobs table below

### Creating an Acquirer Rate

1. Navigate to **Admin > Acquirer Details > Acquirer Rates**
2. Click **"New"** button
3. Fill in the form:
   - Select Acquirer (Account Id auto-fills)
   - Select Payment Mode
   - Select Payment Bank (optional)
   - Configure Settlement Time Frame (t+0 to t+7)
   - Set Settlement Time Of Day
   - Configure Fixed Fee TDR and Percentage TDR
   - Set Service Tax Rates
   - Configure Min/Max Amounts
   - Set Transaction Charge limits
   - Enable/Disable the rate
4. Click **"Save"**

### Duplicating a Rate

1. Select a rate from the table (click on row)
2. Click **"Duplicate"** button
3. The rate will be duplicated with a modified account_id

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Admin/
│           ├── AcquirerAccountsController.php
│           ├── AcquirerAccountUploadController.php
│           └── AcquirerRatesController.php
├── Models/
│   ├── AcquirerAccount.php
│   └── AcquirerRate.php
└── Jobs/
    └── ProcessAcquirerAccountUploadJob.php

database/
└── migrations/
    ├── 2025_12_09_051033_create_acquirer_accounts_table.php
    ├── 2025_12_09_051033_create_acquirer_account_merchant_table.php
    ├── 2025_12_09_055430_create_acquirer_account_upload_jobs_table.php
    └── 2025_12_09_070425_create_acquirer_rates_table.php

resources/
└── views/
    └── admin/
        └── acquirer/
            ├── accounts.blade.php
            ├── detail-upload.blade.php
            ├── rates.blade.php
            ├── angular/
            │   ├── accounts_controller.blade.php
            │   ├── upload_controller.blade.php
            │   └── rates_controller.blade.php
            └── partials/
                ├── account-modal.blade.php
                └── rate-modal.blade.php
```

---

## Key Features Summary

### Acquirer Accounts Module
✅ Full CRUD operations
✅ Comprehensive filtering and search
✅ Column visibility management
✅ Merchant association
✅ API URL configuration (Live/Test)
✅ Settings management (Refund, Settlements, PII masking)

### Acquirer Account Upload Module
✅ Payment mode selection (27 modes)
✅ Bank code multi-select with auto-selection
✅ CSV file upload (CSV, XLSX, XLS)
✅ Background job processing
✅ Progress tracking
✅ Error handling and reporting
✅ Template download
✅ Status file download

### Acquirer Rates Module
✅ Rate configuration per acquirer account
✅ Payment mode and bank-specific rates
✅ TDR and MDR configuration
✅ Transaction amount limits
✅ Settlement time frame configuration
✅ Rate duplication
✅ Enable/Disable functionality

---

## Testing Checklist

### Acquirer Accounts
- [ ] Create new acquirer account
- [ ] Edit existing acquirer account
- [ ] Delete acquirer account
- [ ] Filter by acquirer name, mode, sector
- [ ] Search functionality
- [ ] Column visibility toggle
- [ ] Merchant association

### Acquirer Account Upload
- [ ] Select payment mode
- [ ] Auto-selection of banks
- [ ] Remove banks using cross buttons
- [ ] Upload CSV file
- [ ] Monitor job progress
- [ ] Download status file
- [ ] Download template

### Acquirer Rates
- [ ] Create new rate
- [ ] Edit existing rate
- [ ] Delete rate
- [ ] Duplicate rate
- [ ] Filter by payment mode, acquirer name, sector
- [ ] Column visibility toggle

---

## Notes for Developers

1. **AngularJS Integration:** All three modules use AngularJS for dynamic interactions. Ensure Angular is loaded before the controllers.

2. **Modal Compilation:** Modals use `shown.bs.modal` event to ensure Angular compiles content properly.

3. **File Upload:** Uses FormData for file uploads. Ensure proper CSRF token handling.

4. **Background Jobs:** Upload jobs are processed asynchronously. Ensure queue worker is running: `php artisan queue:work`

5. **Bank Filtering:** Banks are filtered based on payment mode. The `getBanksByPaymentMode()` method handles this logic.

6. **Rate Duplication:** When duplicating a rate, the account_id is modified to avoid conflicts.

7. **Table Width:** Tables have minimum widths set for better readability. Horizontal scrolling is enabled.

8. **Validation:** Server-side validation is implemented in controllers. Client-side validation is handled by AngularJS.

---

## Future Enhancements

- [ ] Export acquirer accounts to CSV
- [ ] Bulk edit functionality
- [ ] Rate comparison view
- [ ] Historical rate tracking
- [ ] Rate approval workflow
- [ ] Integration with payment processing engine
- [ ] Rate calculation API endpoint

---

## Support

For issues or questions regarding the Acquirer Details module, refer to:
- Controller files for business logic
- Model files for data relationships
- Migration files for database structure
- View files for UI components

