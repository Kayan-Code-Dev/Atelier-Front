# Dress Rental Performance Report — Data Sources

Feature: `GET /api/tenant/dresses/{dress}/rental-report`  
Permission: `dresses.report.view`  
Branch: `feature/dress-rental-report`

## Operational metrics

| Metric | Source |
| --- | --- |
| Dress identity / category / branch / status / created_at | `dresses` (+ `dress_categories`, `branches`, primary image if present) |
| days_in_business | `diffInDays(dress.created_at, today)` |
| total_rentals | Count of rent invoice lines for this dress where invoice `status != cancelled` |
| cancelled_rentals | Count where invoice `status = cancelled` |
| completed_rentals | Valid rentals where invoice `status = returned` |
| active_and_upcoming_rentals | Valid rentals in `confirmed`, `partially_paid`, `paid`, `delivered` (not yet returned) |
| total_rental_days | Sum of rental day spans (`rent_start_date`..`rent_end_date`, inclusive) for valid rentals; fall back to `days_of_rent` |
| average_rental_days | `total_rental_days / total_rentals` |
| late_return_count | Settlements with `late_days > 0` or `late_fee > 0`, else returned invoices with `return_date > rent_end_date` |
| damage_count | Settlements with `damage_fee > 0` or `condition = damaged` |
| last_rental_at | Max `rent_start_date` (fallback invoice `created_at`) among valid rentals |

## Financial metrics (decimal-safe `round(..., 2)`)

Payments exist only at invoice level. Reporting uses a **deterministic pro-rata allocation**:

`share = item.total / sum(all invoice item totals)`  
(if invoice items subtotal is 0 and the invoice has a single matching line, share = 1)

| Metric | Rule |
| --- | --- |
| base_rental_revenue | For non-cancelled rent invoices: `round(invoice.total * share, 2)`. Deposits excluded. |
| late_fees / damage_fees / cleaning_fees / other_fees | From `rental_return_settlements`, each `round(fee * share, 2)` |
| additional_fees | Sum of allocated late + damage + cleaning + other |
| total_collected | Sum of `invoice_payments` with status `paid` (or null legacy), allocated by share. Cancelled payments excluded. Deposits never included. |
| total_outstanding | `max(0, base_rental_revenue + additional_fees - total_collected)` for valid rentals in filter (aggregated). Refunds/reversals via cancelled payments reduce collected. |
| deposits_received | Allocated `security_deposit_transactions` type `collected`, else `deposit_paid_amount * share` |
| deposits_returned | Allocated type `refunded`, else settlement `deposit_refund_amount * share` |
| average_revenue_per_rental | `base_rental_revenue / total_rentals` |

**Not shown:** purchase-cost ROI / net profit (purchase_price may appear on dress header only; no fabricated profitability).

Cancelled invoices appear in history when not filtered out, but contribute **0** to valid financial/operational totals.

## Chart

Built server-side from the same allocated rental rows. Granularity: day (≤45 days range), week (≤180), otherwise month. Series: rental_count, rental_revenue, additional_fees.

## Journey timeline

Real recorded events only:

- `inventory_movements` for the dress (created, status_changed, maintenance, rented, returned, sold, branch_transfer, manual_adjustment)
- Rent invoice created / cancelled
- Paid invoice payments (allocated amount)
- Delivery (`delivery_date` / delivery records if present)
- Settlements and fee lines
- Deposit collect / refund transactions

Sorted newest-first by default (`journey_order=desc|asc`).

## Transfers

`inventory_movements` where `type = branch_transfer`.

## Customer insights

Aggregated from valid rentals only. Customer PII omitted from response when requester lacks `customers.view`.

## Filters

`date_from`, `date_to` (default: dress.created_at → today), `status` (rental mapped status), `branch_id`, `customer_id`, `search` (invoice number / customer name / phone), pagination/sort for rental history.

Date matching includes:

1. `rent_start_date` inside the selected range.
2. Rentals that overlap the range (`rent_start <= date_to` and `rent_end >= date_from`).
3. Upcoming bookings (`rent_start > date_to`) whose `created_at` falls inside the range — so reserved future rentals still appear in “كل الفترة / حتى اليوم”.
4. Invoices without rental dates filtered by `created_at`.
