# Razorpay-Style Disputes Module Documentation

## Overview
This module implements a comprehensive disputes (chargeback) management system matching Razorpay's design and functionality.

## Database Schema

### Tables Created/Modified

1. **disputes** (updated)
   - `dispute_id` (string, unique) - Format: `dp_XXXXXXXX`
   - `merchant_id` (foreign key)
   - `payment_id` (nullable)
   - `transaction_id` (nullable)
   - `order_id` (nullable)
   - `card_network` (enum: VISA, MASTERCARD, RUPAY)
   - `reason` (string) - Dispute reason code
   - `status` (enum: action_required, under_review, insufficient_evidence, won, lost, closed)
   - `amount` (decimal)
   - `currency` (string, default: INR)
   - `due_by` (timestamp, nullable)
   - `evidence_submitted` (boolean)
   - `dispute_fee` (decimal)
   - `frozen_amount` (decimal)
   - `internal_notes` (text, nullable)

2. **dispute_evidence** (new)
   - `dispute_id` (foreign key)
   - `document_type` (enum: invoice, delivery_proof, communication, refund_proof, other)
   - `file_name`, `file_path`, `file_url`
   - `file_size`, `mime_type`
   - `uploaded_at`

3. **dispute_timeline** (new)
   - `dispute_id` (foreign key)
   - `event` (string) - Event type
   - `notes` (text)
   - `changed_by_type` (string: merchant, admin, system)
   - `changed_by_id` (nullable)
   - `metadata` (json)
   - `created_at`

## API Endpoints

### Admin Routes (prefix: `/admin/disputes`)

1. `GET /admin/disputes` - Index page
2. `GET /admin/disputes/data` - Get disputes with filters
   - Query params: `status`, `from_date`, `to_date`, `payment_id`, `order_id`, `search`, `merchant_id`, `page`, `per_page`
3. `GET /admin/disputes/summary` - Get summary statistics
   - Returns: `due_today_count`, `due_today_amount`, `due_tomorrow_count`, `due_tomorrow_amount`, `insufficient_evidence_count`, `insufficient_evidence_amount`
4. `GET /admin/disputes/{id}` - Dispute detail page
5. `GET /admin/disputes/{id}/data` - Get dispute details (JSON)
6. `POST /admin/disputes/{id}/evidence` - Upload evidence document
   - Body: `file` (file), `document_type` (enum)
7. `DELETE /admin/disputes/{id}/evidence/{evidenceId}` - Delete evidence
8. `POST /admin/disputes/{id}/submit` - Submit evidence (locks and submits to bank)
9. `PATCH /admin/disputes/{id}/status` - Update status (admin/system only)
   - Body: `status`, `notes` (optional)
10. `GET /admin/disputes/export/csv` - Export disputes to CSV

## Dispute Statuses

- **action_required**: Merchant must respond with evidence
- **under_review**: Bank is reviewing submitted evidence
- **insufficient_evidence**: Merchant missed deadline or evidence was insufficient
- **won**: Merchant wins the dispute
- **lost**: Merchant loses the dispute
- **closed**: Final state (resolved)

## Dispute Reasons

- `fraud`
- `product_not_received`
- `product_not_as_described`
- `duplicate_charge`
- `refund_not_processed`
- `subscription_canceled`
- `no_authorization`

## Business Rules

1. **Evidence Upload**: Only allowed when status is `action_required` and not yet submitted
2. **Auto Status Update**: If `current_date > due_by` and status is `action_required`, auto-change to `insufficient_evidence`
3. **Balance Freeze**: On dispute creation, freeze merchant balance by dispute amount
4. **Lost Dispute**: Permanently debit merchant (amount + dispute fee)
5. **Won Dispute**: Release frozen amount back to merchant
6. **Dispute Fee**: Charged on lost disputes (typically 2% of dispute amount)

## Files Created/Modified

### Migrations
- `database/migrations/2025_12_30_100000_update_disputes_table_razorpay_style.php`
- `database/migrations/2025_12_30_100001_create_dispute_evidence_table.php`
- `database/migrations/2025_12_30_100002_create_dispute_timeline_table.php`

### Models
- `app/Models/Dispute.php` (updated)
- `app/Models/DisputeEvidence.php` (new)
- `app/Models/DisputeTimeline.php` (new)

### Controllers
- `app/Http/Controllers/Admin/DisputesController.php` (new, comprehensive)

### Views
- `resources/views/admin/disputes/index.blade.php` (updated - Razorpay-style UI)
- `resources/views/admin/disputes/angular/main_controller.blade.php` (updated)

### Routes
- Updated `routes/web.php` with all dispute routes

## Frontend Features

### Index Page
- **Tabs**: Action Required, Under Review, Closed, All Disputes
- **Summary Cards** (Action Required tab only):
  - Due Today (Urgent - red badge)
  - Due Tomorrow (Critical - orange badge)
  - Insufficient Evidence (Info badge with refresh)
- **Filters**:
  - Date range (Last 7/30/180 days)
  - Search (Dispute ID, Payment ID, Order ID)
  - Merchant ID
- **Table Columns**:
  - Updated On, Created On, Dispute ID, Order ID, Payment ID, Reason, Amount, Status, Due By, Action
- **Actions**: View, Upload Evidence (if applicable)
- **Export**: CSV download

### Detail Page (TODO: Create view)
- Status banner
- Payment details
- Dispute reason
- Evidence upload section (with drag & drop)
- Timeline (vertical timeline)
- Submit Evidence button (disabled after submission)

## Installation Steps

1. Run migrations:
   ```bash
   php artisan migrate
   ```

2. The routes are already added to `routes/web.php`

3. Access the disputes page at: `/admin/disputes`

## Next Steps / TODOs

1. **Create Dispute Detail View** (`resources/views/admin/disputes/show.blade.php`)
   - Status banner with color coding
   - Payment details section
   - Evidence upload with drag & drop
   - Timeline component
   - Submit button

2. **Implement Balance Management**
   - Freeze balance on dispute creation
   - Debit on lost dispute
   - Release on won dispute

3. **Auto Status Updates**
   - Scheduled job to check `due_by` dates and auto-update status to `insufficient_evidence`

4. **Webhook Integration**
   - Create endpoint for bank webhooks to update dispute status
   - Verify webhook signatures

5. **Merchant View**
   - Create merchant-facing disputes page (similar UI)
   - Filter by merchant's disputes only

6. **File Storage**
   - Configure S3 or cloud storage for evidence files (currently using local storage)
   - Add file validation (size, type)

7. **Notifications**
   - Email/SMS notifications for:
     - New dispute created
     - Due date reminders
     - Status changes

## Testing

Create sample disputes with:
```php
Dispute::create([
    'merchant_id' => 1,
    'payment_id' => 123,
    'order_id' => 'order_123',
    'card_network' => 'VISA',
    'reason' => 'fraud',
    'status' => 'action_required',
    'amount' => 1000.00,
    'currency' => 'INR',
    'due_by' => now()->addDays(7),
    'evidence_submitted' => false,
]);
```

## Notes

- All timestamps stored in UTC
- Evidence files stored in `storage/app/dispute_evidence/`
- Timeline events automatically created on status changes
- Status changes are logged in `dispute_timeline` table for audit

