# Grand Lion Hotel Reservation System

Laravel 12 application for customer reservations, staff operations, hotel administration, online-payment verification, refunds, room pricing, and occupancy reporting.

## Requirements

- PHP 8.2 or newer with PDO, Mbstring, OpenSSL, Fileinfo, and ZIP
- Composer 2
- MySQL 8+ for production; SQLite is supported for tests
- Node.js 20+ and npm
- A queue worker in production
- A scheduler process that runs `php artisan schedule:run` every minute

## Local setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve
```

On macOS/Linux, use `cp .env.example .env`.

Configure the database, mail transport, application URL, Google OAuth (when used), and payment settings in `.env`. Never commit `.env`.

For active development:

```bash
composer run dev
```

## Account roles

- **Customer** — browses rooms, maintains a profile, creates bookings, submits online-payment proof, requests rescheduling/transfers, downloads receipts, and requests cancellation/refunds.
- **Staff** — manages arrivals, walk-ins, confirmation, check-in/out, occupancy changes, room transfers, rescheduling, payment verification, and operational notes.
- **Admin** — manages rooms, room status, date discounts, customers, staff, bookings, sales, occupancy, and payment verification.

Accounts are stored in separate `customers`, `staff`, and `admins` tables and use separate Laravel guards. The legacy `users` model is intentionally not part of the final application schema.

## Booking and payment flow

1. A customer or staff member creates a pending booking.
2. Staff confirms it after checking availability.
3. Cash remains unpaid until staff records payment.
4. InstaPay and card submissions require a customer reference and image proof.
5. Staff/admin approval marks the payment paid and generates a transaction reference.
6. Cancelling a paid booking creates a pending refund request.
7. Booking, payment, and refund changes are recorded in `activity_logs`; relevant customer updates are also stored in `notifications`.

Supported payment methods are `cash`, `instapay`, and `credit_debit_card`.

## Room images

Admins may use a remote image URL or upload JPG, PNG, or WebP files up to 5 MB. Uploaded files are stored on the `public` disk under `room-images/`.

Run this once on each deployment:

```bash
php artisan storage:link
```

## Reports and backups

- Sales report: `/admin/sales-report`
- Occupancy report: `/admin/occupancy-report`
- Manual database backup:

```bash
php artisan hotel:backup
```

Backups are compressed JSON Lines files in `storage/app/private/backups`. The scheduler creates one daily at 02:30 and retains the latest 14. Copy backups to durable off-site storage in production; an ephemeral web-service filesystem is not sufficient.

## Testing

The test suite uses an in-memory SQLite database and rebuilds the complete schema:

```bash
php artisan test
```

Useful validation commands:

```bash
php artisan migrate:fresh --env=testing
php artisan route:list
php artisan view:cache
vendor/bin/pint --test
```

## Deployment

Repository deployment definitions are provided for Render (`render.yaml`) and Railway (`railway.json`). See [Render with Railway MySQL](docs/render-with-railway-mysql.md) for the split-host setup.

Production checklist:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and a persistent `APP_KEY`.
2. Configure MySQL, mail, queue, and public filesystem settings.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `npm ci && npm run build`.
5. Run `php artisan migrate --force` and `php artisan storage:link`.
6. Run `php artisan optimize`.
7. Keep `php artisan queue:work` running.
8. Run `php artisan schedule:run` every minute.
9. Persist uploaded images and copy database backups off-site.

## Security notes

- Logout is POST-only and CSRF protected.
- Public route identifiers use signed opaque tokens; older encrypted tokens remain readable.
- Payment proof paths and credentials are redacted from operational audit changes.
- Uploaded files are validated as images and size-limited.
- Keep application keys, database credentials, OAuth secrets, and mail credentials only in environment variables.
