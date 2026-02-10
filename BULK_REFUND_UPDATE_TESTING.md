# Bulk Refund Update - Testing Guide

## Overview
The Bulk Refund Update feature allows administrators to update multiple refunds at once by uploading a CSV file. The system processes the file asynchronously and generates a status report.

## Features
- ✅ Download CSV Template with all columns
- ✅ Upload CSV file with refund updates
- ✅ Process all columns: Refund ID, Status, Notes, Reason, Amount, Currency
- ✅ Real-time job progress tracking
- ✅ Status report generation after processing
- ✅ Error handling and validation

## CSV Template Columns

The CSV template includes the following columns:

| Column | Required | Description | Valid Values |
|--------|----------|-------------|--------------|
| **Refund ID** | ✅ Yes | Unique refund identifier (e.g., RFD_XXXXXXXXXXXXXX) | Any existing refund ID |
| **Status** | Optional | Refund status | `pending`, `processing`, `completed`, `failed`, `cancelled` |
| **Notes** | Optional | Additional notes about the refund | Any text |
| **Reason** | Optional | Reason for the refund | Any text |
| **Amount** | Optional | Refund amount | Positive decimal number (e.g., 100.50) |
| **Currency** | Optional | Currency code | 3-character code (e.g., USD, EUR) |

## Testing Steps

### Step 1: Access the Bulk Refund Update Page
1. Log in as an administrator
2. Navigate to: **Payments** → **Bulk Update Refund Status**
3. URL: `/admin/payments/bulk-refund-update`

### Step 2: Download CSV Template
1. Click the **"Download CSV Template"** button (with download icon)
2. A CSV file named `bulk_refund_template.csv` will be downloaded
3. Open the file in Excel or any text editor
4. Verify the columns are: `Refund ID, Status, Notes, Reason, Amount, Currency`

### Step 3: Prepare Test Data

#### Option A: Use Sample Test Data
A sample CSV file is available at: `storage/app/bulk_refunds/test_bulk_refund_sample.csv`

#### Option B: Create Your Own Test Data
1. Open the downloaded template
2. Fill in the data rows (keep the header row)
3. **Important**: Use existing refund IDs from your database
4. Example row:
   ```
   RFD_TEST123456789,completed,Refund processed,Customer request,100.50,USD
   ```

### Step 4: Get Real Refund IDs (Required for Testing)

To get real refund IDs from your database, run this SQL query:

```sql
SELECT refund_id, status, amount, currency, notes, reason 
FROM refunds 
LIMIT 10;
```

Or use Laravel Tinker:
```bash
php artisan tinker
```
```php
\App\Models\Refund::select('refund_id', 'status', 'amount', 'currency')->limit(10)->get();
```

### Step 5: Upload CSV File
1. Click **"Choose File"** button
2. Select your CSV file (max 10MB, formats: .csv, .xlsx, .xls)
3. The file name will appear in the "No Files Selected" field
4. Click **"Upload"** button
5. Wait for the success message: "File uploaded successfully. Processing started."

### Step 6: Monitor Job Progress
1. The job will appear in the **"List of PgRefunds"** table below
2. Watch the **Progress** column (updates in real-time)
3. Check the **Status** column:
   - `pending` - Job is queued
   - `processing` - Job is running
   - `completed` - Job finished successfully
   - `failed` - Job encountered an error

### Step 7: Download Status Report
1. Once the job status is `completed` or `failed`
2. Click the **"Download"** button in the **"Download Status File"** column
3. A CSV file will be downloaded with processing results
4. The report contains:
   - Row number
   - Refund ID
   - Status (success/error/skipped)
   - Message (details about the update)

### Step 8: Verify Refund Updates
1. Go to **Payments** → **Refunds** page
2. Search for the refund IDs you updated
3. Verify that the changes were applied:
   - Status updated
   - Notes updated
   - Reason updated
   - Amount updated (if changed)
   - Currency updated (if changed)

## Test Data Examples

### Example 1: Update Status Only
```csv
Refund ID,Status,Notes,Reason,Amount,Currency
RFD_ABC123XYZ,completed,,,,
```

### Example 2: Update Status and Notes
```csv
Refund ID,Status,Notes,Reason,Amount,Currency
RFD_ABC123XYZ,completed,Refund processed successfully,,,
```

### Example 3: Update All Fields
```csv
Refund ID,Status,Notes,Reason,Amount,Currency
RFD_ABC123XYZ,completed,Refund processed,Customer request,100.50,USD
```

### Example 4: Multiple Refunds
```csv
Refund ID,Status,Notes,Reason,Amount,Currency
RFD_ABC123XYZ,completed,Processed,Customer request,100.50,USD
RFD_DEF456UVW,pending,Waiting,Product defect,250.00,USD
RFD_GHI789RST,processing,In progress,Duplicate payment,75.25,USD
```

## Validation Rules

### Refund ID
- ✅ **Required** - Cannot be empty
- ✅ Must exist in the database
- ❌ If not found: Error message "Refund not found: {refund_id}"

### Status
- ✅ Must be one of: `pending`, `processing`, `completed`, `failed`, `cancelled`
- ✅ Case-insensitive
- ❌ Invalid status: Error message with valid options

### Amount
- ✅ Must be a positive decimal number
- ✅ Examples: `100.50`, `250.00`, `0.99`
- ❌ Invalid: Negative numbers, text, empty (if provided)

### Currency
- ✅ Must be exactly 3 characters
- ✅ Examples: `USD`, `EUR`, `GBP`
- ❌ Invalid: `US`, `USDOLLAR`, empty (if provided)

## Error Handling

### Common Errors

1. **"Refund ID is required"**
   - Cause: Empty Refund ID column
   - Fix: Ensure all rows have a Refund ID

2. **"Refund not found: {refund_id}"**
   - Cause: Refund ID doesn't exist in database
   - Fix: Use valid refund IDs from your database

3. **"Invalid status: {status}"**
   - Cause: Status value not in allowed list
   - Fix: Use one of: pending, processing, completed, failed, cancelled

4. **"Invalid amount: {amount}"**
   - Cause: Amount is not a valid positive number
   - Fix: Use decimal format like 100.50

5. **"Invalid currency: {currency}"**
   - Cause: Currency is not 3 characters
   - Fix: Use 3-character codes like USD, EUR

## Queue Worker

**IMPORTANT**: The bulk refund update uses Laravel queues. Make sure the queue worker is running:

```bash
php artisan queue:work --tries=3 --timeout=30
```

Or use a supervisor/process manager for production.

## Testing Checklist

- [ ] Download CSV template works
- [ ] Template has all 6 columns
- [ ] Upload CSV file works
- [ ] Job appears in the list
- [ ] Progress updates in real-time
- [ ] Status changes from pending → processing → completed
- [ ] Status report is generated
- [ ] Download status report works
- [ ] Refund updates are applied correctly
- [ ] Error handling works for invalid data
- [ ] Validation messages are clear

## Troubleshooting

### Job Stuck in "Pending" Status
- Check if queue worker is running: `php artisan queue:work`
- Check Laravel logs: `storage/logs/laravel.log`

### Job Status is "Failed"
- Check the **Error** column in the job list
- Check Laravel logs for detailed error messages
- Verify CSV file format is correct

### Refunds Not Updating
- Verify refund IDs exist in database
- Check status report for error messages
- Verify queue worker processed the job

### Status Report Not Generated
- Check if job completed successfully
- Verify file permissions on `storage/app/bulk_refunds/export/`
- Check Laravel logs for errors

## Notes

- Maximum 1000 rows per file upload
- File size limit: 10MB
- Supported formats: CSV, XLSX, XLS
- Processing is asynchronous (uses queues)
- Status updates in real-time during processing
- All updates are logged for audit purposes

