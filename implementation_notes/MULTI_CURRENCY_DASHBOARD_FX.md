# Multi-Currency Dashboard FX Reporting

This document describes how iPay normalizes multi-currency payments for **admin**, **merchant**, and **reseller** dashboards, and why we never use “today’s rate” for accounting-style totals by default.

## Problem

Merchants accept payments in many currencies (INR, USD, KES, JPY, etc.). Dashboard totals must:

1. **Not hardcode** exchange rates (they change daily).
2. **Not rewrite history** when rates move (a January USD/INR payment must keep January’s economic meaning).
3. Let the user pick a **display currency** from a dropdown and see all metrics in that currency.

## Design principles

| Principle | Implementation |
|-----------|----------------|
| Normalize through one base currency | **USD** (`IPAY_FX_BASE_CURRENCY`, default `USD`) |
| Immutable payment-time FX | Snapshot on each successful transaction |
| Refunds follow original payment FX | Refund `amount_base` uses the parent transaction’s rate |
| Two reporting modes | **Historical** (default) vs **Live** |
| Never INR→KES directly | Always `original → USD (base) → display` |

## Data model

### `transactions` (on success)

| Column | Purpose |
|--------|---------|
| `amount` | Original charged amount |
| `currency` | Original ISO code |
| `amount_base` | Amount in base currency (USD) at payment time |
| `fx_base_currency` | Base code (default `USD`) |
| `fx_rate_from_to_base` | Units of `currency` per 1 USD (same as open.er-api `rates`) |
| `fx_rates_snapshot` | JSON map of all USD cross-rates at capture time |
| `fx_captured_at` | When the snapshot was taken |

Example: customer pays **5000 INR** when API says `INR: 83.2` (per 1 USD):

- `amount` = 5000, `currency` = INR  
- `amount_base` = 5000 / 83.2 ≈ **60.096154** USD  
- `fx_rate_from_to_base` = 83.2  
- `fx_rates_snapshot` includes `{ "USD": 1, "INR": 83.2, "KES": 129, ... }`

### `refunds` (on completed)

| Column | Purpose |
|--------|---------|
| `amount_base` | Refund amount in USD using **transaction’s** `fx_rate_from_to_base` |
| `fx_rate_from_to_base` | Copied from parent transaction when available |
| `fx_rates_snapshot` | Copied from parent transaction |
| `fx_captured_at` | Parent capture time or refund completion time |

Refunds do **not** re-fetch a new rate unless the parent transaction has no snapshot (legacy rows).

## Capture flow

```
Payment succeeds (PaymentSuccess event)
        ↓
CaptureFxSnapshotOnPaymentSuccess
        ↓
FxSnapshotService::captureTransactionSnapshot()
        ↓
Fetch USD rates (open.er-api.com, cached)
        ↓
Persist amount_base + fx_rates_snapshot

Refund completed (RefundCreated with status=completed)
        ↓
CaptureFxSnapshotOnRefundCreated
        ↓
FxSnapshotService::captureRefundSnapshot()  (uses txn rate)
```

**Rate source:** `https://open.er-api.com/v6/latest/USD`  
**Cache:** `dashboard_fx_rates_usd_v1` (TTL: `DASHBOARD_FX_CACHE_TTL`, default 3600s)  
**Fallback:** stale backup key if HTTP fails  

## Dashboard conversion modes

### Historical (default) — `fx_mode=historical`

For each row with `amount_base`:

```text
display_amount = amount_base × snapshot[display_currency]
```

If a currency is missing from the snapshot, we fall back to the **current** live rate for that code (logged). Rows without `amount_base` (pre-migration) use live bucket conversion.

**Use for:** accounting, settlements, reconciliation, “what did we earn when it happened?”

### Live — `fx_mode=live`

Group by original `currency`, sum `amount`, convert with **today’s** cached API rates:

```text
amount_usd = amount / rates[from]
amount_display = amount_usd × rates[display]
```

**Use for:** “what is portfolio worth today?” analytics only.

## UI / API

### Query parameters (all dashboards)

| Param | Values | Default |
|-------|--------|---------|
| `display_currency` | `USD`, `INR`, `KES`, `EUR`, `GBP`, `JPY`, `AUD`, `CAD` | `DASHBOARD_DISPLAY_CURRENCY` (KES) |
| `fx_mode` | `historical`, `live` | `historical` |

### Surfaces

| Role | Where |
|------|--------|
| Admin | `/admin/dashboard` + `GET /admin/dashboard/data` |
| Merchant | `/dashboard` (GET form: currency + mode) |
| Reseller | `/reseller/dashboard` + `GET /reseller/merchant-summary` |

JSON responses include `fx_options` and stats fields `display_currency`, `fx_mode`.

## Configuration (`config/ipay.php`)

```env
IPAY_FX_BASE_CURRENCY=USD
DASHBOARD_DISPLAY_CURRENCY=KES
DASHBOARD_FX_MODE=historical
DASHBOARD_FX_CACHE_TTL=3600
DASHBOARD_FX_HTTP_TIMEOUT=10
```

`dashboard_display.manual_rates_to_usd` can override API rates per currency in code when needed.

## Backfill legacy data

After deploying migrations, run once:

```bash
php artisan ipay:backfill-fx-snapshots
```

This applies **current** API rates to old successful transactions/refunds that lack snapshots. For strict accounting on old data, prefer importing true historical rates into `fx_rates_snapshot` manually.

## Key classes

| Class | Role |
|-------|------|
| `App\Services\FxSnapshotService` | Writes snapshots on payment/refund |
| `App\Services\DashboardDisplayCurrencyService` | Aggregates totals + charts |
| `App\Support\DashboardFxContext` | Parses `display_currency` + `fx_mode` from request |
| `App\Listeners\CaptureFxSnapshotOnPaymentSuccess` | Payment hook |
| `App\Listeners\CaptureFxSnapshotOnRefundCreated` | Refund hook |
| `App\Console\Commands\BackfillFxSnapshotsCommand` | `ipay:backfill-fx-snapshots` |

## Dashboard metrics cache

Aggregated dashboard payloads are cached in Laravel cache (default TTL **5 minutes**, `DASHBOARD_METRICS_CACHE_TTL`).

| Scope | Cache key includes |
|-------|-------------------|
| Admin API | test/live mode, date range, display currency, FX mode |
| Merchant page | merchant id, test mode, currency, FX mode |
| Reseller summary | reseller id, filters, pagination, currency, FX mode |

**Invalidation:** cache version bumps automatically on:

- `PaymentSuccess` (new successful transaction)
- `RefundCreated` when status is `completed`

No manual flush needed after normal payments. Service: `App\Services\DashboardMetricsCacheService`.

## Troubleshooting

### Historical mode shows 0 / UI does not update (fixed May 2026)

**Cause:** Historical aggregation used `chunkById()` with a limited `SELECT` that omitted the primary key alias Laravel needs for pagination. That threw:

```text
RuntimeException: The chunkById operation was aborted because the [id] column is not present in the query result.
```

The admin API caught the error and returned failure or empty stats, while **live** mode (SQL `GROUP BY` path) still worked.

**Fix:** Historical paths now use `lazyById()` with `transactions.id` / `refunds.id` included in the select list. `amountBaseToDisplay()` also normalizes `fx_rates_snapshot` when it is still a JSON string.

If historical totals are still empty after deploy:

1. Run `php artisan ipay:backfill-fx-snapshots` so legacy rows get `amount_base`.
2. Confirm `fx_rates_snapshot` is populated on successful transactions.
3. Check `storage/logs/laravel.log` for `Dashboard FX missing historical/display rate`.

## What we intentionally do not do

- Hardcode FX in Blade or controllers  
- Recalculate stored `amount_base` on a schedule  
- Convert directly between two non-USD currencies without normalization  
- Use live rates for default dashboard totals (historical is default)  

## Commission / chargebacks

- **Reseller commission** amounts remain in their stored currency; volume/refund tiles use FX aggregation.  
- **Disputes (chargebacks)** still aggregate via live currency buckets until dispute-level snapshots are added.  

## Architecture diagram

```text
Customer pays (amount + currency)
           │
           ▼
    PaymentSuccess event
           │
           ▼
   FX snapshot → amount_base (USD) + fx_rates_snapshot
           │
           ├──────────────────────────────────────┐
           ▼                                      ▼
  Dashboard (historical)              Dashboard (live)
  Σ amount_base × snapshot[display]   Σ convert(amount, currency, display)
           │                                      │
           └──────────────┬───────────────────────┘
                          ▼
              display_currency dropdown (INR / USD / KES / …)
```
