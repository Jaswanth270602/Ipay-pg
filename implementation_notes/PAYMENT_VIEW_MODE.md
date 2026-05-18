# Payment view mode (TEST vs LIVE)

Live and test money are kept separate across dashboards, grids, refunds, settlements, and reports. Only rows matching the active viewing mode are returned when lists or aggregates load.

## How mode is chosen

| Role | Source | Toggle |
|------|--------|--------|
| **Merchant** | `merchants.test_mode` (database) | Sidebar Test/Live → `merchant.settings.switch-mode` |
| **Admin** | Session `admin_view_mode` (default `test`) | Sidebar Test/Live → `admin.settings.switch-mode` |
| **Reseller** | Session `reseller_view_mode` (default `test`) | Sidebar Test/Live → `reseller.settings.switch-mode` |

Central helper: `App\Support\PaymentViewMode`

- `PaymentViewMode::isTestMode()` — current scope
- `PaymentViewMode::scopeTransactions($query)` — filter `transactions.test_mode`
- `PaymentViewMode::scopeRefundsViaTransaction($query)` — refunds via related transaction
- `PaymentViewMode::scopeResellerCommissions($query)` — commissions via transaction

## Data model

- `transactions.test_mode` — set from order/payment link at capture time
- `refunds` — scoped through `transaction.test_mode` (no separate refund flag required for filtering)
- `settlement_details.test_mode`, `settlements.test_mode` — aligned with merchant environment

## UI

- Sidebar badge: **TEST MODE** (warning) / **LIVE MODE** (success)
- Switching mode reloads the page so all Angular/grid and dashboard requests use the new session
- API grids may include `payment_environment`: `TEST` or `LIVE` per row

## Dashboards and cache

Dashboard metric caches include a `test` flag in the cache key (`DashboardMetricsCacheService`) so test and live totals never mix. Cache is invalidated on successful payments and completed refunds (`InvalidateDashboardMetricsCache`).

Admin, merchant, and reseller dashboard controllers filter transactions and refunds with `where('test_mode', $isTestMode)` (or join on `transactions.test_mode` for refunds).

## Controllers already scoped (representative)

- Admin: transactions, refunds, orders, dashboard, split transactions, settlement details, sales reports, bank success rate, partner profit, bulk refund, merchant lists (by merchant `test_mode`)
- Merchant: transactions, refunds, orders, settlements, chargebacks, payment links, dashboard
- Reseller: dashboard, transactions, earnings/commissions (`ResellerCommission::netTotalsForReseller($id, $testMode)`)
- Reports: `ReportsController` uses admin session mode

## Adding new features

1. Read scope with `PaymentViewMode::isTestMode()` (admin/reseller) or `$merchant->test_mode` (merchant).
2. Add `->where('test_mode', …)` on transaction queries, or `whereHas('transaction', …)` on refunds.
3. Include `'test' => PaymentViewMode::cacheTestFlag()` in any dashboard cache key parts.
4. Expose `payment_environment` in grid JSON if operators need a per-row label.

## Related

- FX display currency: `implementation_notes/MULTI_CURRENCY_DASHBOARD_FX.md`
