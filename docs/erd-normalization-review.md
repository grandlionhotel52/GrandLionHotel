# Reservation database normalization changes

The implementation separates current customer profiles, reservation details, agreed bills, and actual collections. This document describes the local changes; deployment is a separate step.

## Current structure

- `customers` is the source of current names, contact information, and addresses, including walk-in customer records.
- `booking_details` stores occupancy, meal plan, and payment preference. Its booking reference is unique. Legacy differing personal details are retained in migration audit records.
- `booking_bills` uses `booking_bill_id` as its primary key and `booking_id` as a unique foreign key: one bill per booking. It stores the agreed total and historical discount amounts/rate. An agreed total is not recomputed from today's room or promotion prices.
- `payments.booking_bill_id` references `booking_bills.booking_bill_id`. Payments reach their booking through the bill; no direct booking ID is stored on payments.
- Each completed payment has its own received amount, date, method, and unique transaction reference. Provider payment/session identifiers remain unique. Successful collections survive rescheduling and additional charges.
- Outstanding balance is calculated as bill total minus paid transactions minus any immutable opening credit from the legacy migration. It is not another editable payment column.
- Fixed room capacity is a system-wide rule of two standard guests, exposed by the model. It is no longer repeated in every room row.

## Payment behavior

Partial collections are individually recorded as paid transactions while the booking remains unpaid until its bill is settled. Rescheduling raises the agreed bill when required and creates a new pending attempt without changing previous collections. Replayed provider payment IDs do not create another collection. Capturing an older valid checkout supersedes other pending attempts when the bill becomes fully settled.

Receipts identify the particular collection and show the current bill total/balance separately. A customer or staff member can download a receipt for a collected payment even if another amount remains due. Sales reports sum successful collections, count distinct bookings, and allocate the bill's discount/food component across collections instead of repeating the full component on every payment.

## Existing database migration

Run the customer normalization migration before the billing migration, in timestamp order. The configured deployment startup already runs `php artisan migrate --force`.

Previously settled rows retain their IDs, amounts, dates, and receipt/provider references. A reopened reschedule row with an original payment date is separated into the prior collection and a new balance attempt. The original mutable row is archived in the activity log.

Legacy partial-payment rows without a collection date no longer contain a complete payment history. The migration preserves their current outstanding balance, records a cutover credit, and archives the original values. It cannot recover overwritten individual collections or prove the original agreed total after multiple old partial updates. Such records need reconciliation against external receipts if exact historical billing is required. Opening credits are not reported as newly received sales.

The billing migration does not support rollback into the former one-payment-per-booking structure.

## ERD

The updated Draw.io diagram includes 15 entities: the original reservation entities plus booking bills, room images, and room reviews. It shows the explicit bill identifier, payment cardinality, unique booking-detail/discount/bedding references, room-status and promo-code keys, and required review booking references. Fixed capacity is omitted from ROOMS.

The diagram covers the customer-facing reservation entities, not every operational Laravel/authentication/audit table. Fields and dependencies follow the application's current business rules. Repeated room-type labels, free-text locality names, or common fields on separate admin/staff accounts do not alone establish a 3NF violation; no extra lookup tables were introduced for appearance alone. Financial snapshot values represent agreed historical facts. The implementation does not claim a formal 3NF proof for unrelated tables or all possible future business rules.

## Validation

Feature coverage includes customer profile propagation, populated data migration, walk-in profiles, separate collections and references, computed balances, provider replay, superseded checkouts, receipts, rescheduling, and revenue allocation. The local MySQL service is unavailable; migration and feature validation run against SQLite. Production MySQL migration execution remains to be verified during deployment.

Latest validation: **198 tests passed, 1,432 assertions**. PHP syntax checks and Draw.io XML/relationship/non-overlap checks passed.

## Derived relationships and current business rules

- `BOOKINGS -> BOOKING_BILLS -> PAYMENTS`: a payment reaches the reservation through booking_bill_id, then the bill's unique booking_id.
- `ROOM_REVIEWS -> BOOKINGS -> ROOMS/CUSTOMERS`: reviews now require a completed stay. Room/customer IDs are not duplicated on reviews. Each booking has at most one review; repeated stays may have separate reviews.
- `BOOKING_EXTRA_BEDDING_REQUESTS -> BOOKINGS -> STAFF`: only the booking's assigned staff member may respond. An unassigned booking is claimed atomically on its first accepted response. No separate responded_by_staff_id is stored. The UI identifies the assigned staff member rather than claiming an immutable historical author.
- Administrator creation, reschedule approval, and room-status updates are separate actions. Their direct references remain because the actors are not required to be the same administrator. Their paths are not redundant references to the same fact.

Migration 000003 gives bills independent IDs and moves payment references to those IDs. Existing bill audit identifiers are preserved. Migration 000004 links legacy unbooked reviews to an eligible matching completed booking if available. Otherwise, it preserves the original review in the activity log and removes it from active reviews; it does not fabricate a completed booking. Differing legacy bedding responders are archived before the redundant column is removed, and existing booking assignments are retained.

The Draw.io relationships use explicit one/optional-one/zero-to-many cardinalities. All 16 connector paths were checked against the 15 entity rectangles; none passes through another table. `New ERD.drawio` preserves the user's two-page document and edits Page-2. The repository ERD and the older erdprefinal-updated file contain the same updated diagram as a single page.

`BOOKINGS.admin_id` is the nullable foreign key to ADMINS for the reschedule approver. Migration 000005 renames the previous long column without changing its values or its meaning. `reschedule_approved_at` still records the approval time.
