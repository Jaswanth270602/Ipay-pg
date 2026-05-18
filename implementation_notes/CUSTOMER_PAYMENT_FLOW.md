# Customer Payment Flow – Successful Test Payment

This guide explains how to run a **customer payment** end-to-end and get a **successful payment** using the existing Laravel payment orchestration (Razorpay or simulation).

---

## Prerequisites

1. **Laravel running** (e.g. `php artisan serve` → `http://127.0.0.1:8000`).
2. **Database seeded** so a test merchant and API key exist:
   ```bash
   php artisan db:seed
   ```
3. **For real Razorpay test payment**: The merchant must have an **active acquirer account** (Razorpay, TEST mode) attached.  
   - Admin → Acquirer Accounts → create/link Razorpay TEST account to the merchant.  
   - Without this, the checkout uses **simulation mode** (success/failure buttons on the page).

---

## Option A: Artisan command (quickest)

1. **Create a test payment and get the checkout URL:**
   ```bash
   php artisan payment:create-test
   ```
   Optional: use a specific API key or amount:
   ```bash
   php artisan payment:create-test --amount=50 --api-key=pk_test_xxxx
   ```
   Or set `TEST_MERCHANT_API_KEY=pk_test_xxxx` in `.env` and run:
   ```bash
   php artisan payment:create-test
   ```

2. **Open the printed “Checkout URL” in your browser.**

3. On the payment page:
   - Select **Card**.
   - Click **Pay**.
   - If **Razorpay** modal opens: use test card **4111 1111 1111 1111**, any CVV, any future expiry.
   - If **simulation mode**: click **Simulate success** to get a successful payment.

4. You should see the success message and can be redirected to the success page.

---

## Option B: Merchant test app (full flow)

1. **Start Laravel:**
   ```bash
   php artisan serve
   ```
   Use the same base URL in `.env`: `APP_URL=http://127.0.0.1:8000` (or `http://localhost:8000` if you use that).

2. **Start the test store:**
   ```bash
   cd merchant-test-app
   php -S localhost:8080
   ```

3. **Configure the test app** (`merchant-test-app/config.js`):
   - `apiUrl`: Laravel API, e.g. `http://127.0.0.1:8000/api` (must match where Laravel runs).
   - `apiKey`: A valid **test** API key for a merchant that has (optionally) Razorpay TEST attached.  
   Get the key from the database after seeding:
   ```sql
   SELECT `key` FROM api_keys WHERE mode = 'test' AND status = 'active' LIMIT 1;
   ```
   Or from Merchant dashboard → API Keys.

4. **In the browser:** open `http://localhost:8080`.

5. **Create payment as “customer”:**
   - Click **Pay Now** on any product.
   - Fill customer name and email → **Confirm & Pay**.
   - You are redirected to the Laravel checkout URL (`/pay/{token}`).

6. **Complete payment:**
   - Select **Card** → **Pay**.
   - **Razorpay**: use card **4111 1111 1111 1111**, any CVV, any future expiry.
   - **Simulation**: use **Simulate success**.

7. On success you’ll see the success message; the test app can show success/failure pages if you return to it with `?status=success&transaction_id=...`.

---

## Razorpay test card (successful payment)

| Field   | Value                |
|--------|----------------------|
| Card   | **4111 1111 1111 1111** |
| CVV    | Any 3 digits (e.g. 123) |
| Expiry | Any future date (e.g. 12/30) |
| Name   | Any name             |

---

## Checklist for successful payment

- [ ] Laravel is running (`php artisan serve` or Apache with correct `APP_URL`).
- [ ] At least one test merchant and test API key exist (seed or create).
- [ ] For Razorpay: merchant has an active Razorpay **TEST** acquirer account.
- [ ] You open the **checkout URL** (from command or test app) and pay with the test card or simulation.

---

## If payment fails or gateway is “simulation”

- **No acquirer account** → Checkout uses simulation; use **Simulate success** on the page for a successful payment.
- **Razorpay 401 / invalid key** → Check Razorpay TEST key/secret in the acquirer account (Admin → Acquirer Accounts).
- **404 on checkout URL** → Ensure `APP_URL` in `.env` matches the URL you use to open the site (e.g. `http://127.0.0.1:8000`).

This flow uses the **existing** payment gateways and routes; it does not change production behaviour.
