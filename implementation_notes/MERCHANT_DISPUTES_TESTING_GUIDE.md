# Merchant Disputes Testing Guide

## Overview
The merchant disputes module allows merchants to create and manage disputes from their dashboard. The form has been updated to match Razorpay-style fields and validation.

## How to Test

### Step 1: Login as Merchant

1. Go to the login page: `/login`
2. Enter your merchant credentials
3. You'll be redirected to the merchant dashboard

### Step 2: Navigate to Disputes

1. In the sidebar, click on **"Disputes"** under the appropriate section
2. Or navigate directly to: `/merchant/disputes`

### Step 3: Create a New Dispute

1. Click the **"New Dispute"** button (top right)
2. Fill in the form:

   **Required Fields:**
   - **Reason** (Dropdown - Select one):
     - Fraud
     - Product Not Received
     - Product Not As Described
     - Duplicate Charge
     - Refund Not Processed
     - Subscription Canceled
     - No Authorization
   
   - **Amount** (Required): Enter a positive number (e.g., 1000.00)

   **Optional Fields:**
   - **Transaction ID**: Enter an integer transaction ID
   - **Order ID**: Enter order ID (e.g., order_123456)
   - **Currency**: Default is INR (can select USD, EUR)
   - **Card Network**: Select VISA, MASTERCARD, or RUPAY
   - **Internal Notes**: Add any additional notes

3. Click **"Create"** button

### Step 4: Verify Dispute Creation

After clicking "Create", you should see:
- ✅ Success message: "Dispute created successfully!"
- Modal closes automatically
- The disputes table refreshes showing your new dispute
- New dispute appears with status: **"ACTION_REQUIRED"** (blue badge)
- Amount is frozen (frozen_amount = dispute amount)

### Step 5: View Disputes

The disputes table shows:
- **#**: Row number
- **Transaction**: Transaction ID or Order ID
- **Reason**: Dispute reason (uppercase)
- **Amount**: Currency and amount
- **Status**: Badge with color coding:
  - 🔵 Action Required (blue/info)
  - 🔵 Under Review (blue/primary)
  - 🟡 Insufficient Evidence (yellow/warning)
  - 🟢 Won (green/success)
  - 🔴 Lost (red/danger)
  - ⚫ Closed (gray/secondary)
- **Created**: Date and time

### Step 6: Filter Disputes

Use the status dropdown to filter disputes by:
- All (default)
- Action Required
- Under Review
- Insufficient Evidence
- Won
- Lost
- Closed

## Sample Test Data

Here are some sample values you can use for testing:

```
Transaction ID: 1 (or any existing transaction ID)
Order ID: order_test123
Reason: Product Not Received
Amount: 2500.00
Currency: INR
Card Network: VISA
Internal Notes: Customer claims order was not delivered
```

## Expected Behavior

1. **Form Validation:**
   - Reason is required - button disabled until selected
   - Amount is required and must be > 0
   - All other fields are optional

2. **On Successful Creation:**
   - Dispute is created with status: `action_required`
   - `due_by` is automatically set to 7 days from now
   - `evidence_submitted` is set to `false`
   - `frozen_amount` equals the dispute amount
   - `dispute_fee` is set to 0
   - `currency` defaults to INR if not specified

3. **Error Handling:**
   - If validation fails, error messages are displayed
   - If server error occurs, error message shows details

## Testing Different Scenarios

### Test 1: Basic Dispute
- Reason: Fraud
- Amount: 1000.00
- Leave other fields empty

### Test 2: Complete Information
- Fill all fields including optional ones
- Verify all data is saved correctly

### Test 3: Validation
- Try to submit without reason → Should show error
- Try to submit without amount → Should show error
- Try to submit with amount = 0 → Should show error
- Try to submit with negative amount → Should be blocked by HTML5 validation

### Test 4: Different Currencies
- Create disputes with INR, USD, EUR
- Verify currency is displayed correctly in table

## Troubleshooting

If you encounter issues:

1. **Form doesn't submit:**
   - Check browser console for JavaScript errors
   - Verify CSRF token is present in page
   - Check network tab for API response

2. **Validation errors:**
   - Ensure reason is selected from dropdown
   - Ensure amount is a positive number
   - Check server logs for validation details

3. **Dispute not appearing:**
   - Refresh the page
   - Check if status filter is set correctly
   - Verify you're logged in as the correct merchant

## Next Steps

After creating disputes:
- View disputes in admin panel: `/admin/disputes`
- Upload evidence (when viewing dispute details)
- Submit evidence for review
- Track dispute timeline

