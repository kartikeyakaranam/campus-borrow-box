# Borrow Box

Borrow Box helps students lend useful items to each other on campus. Owners list an item, set its hourly price and minimum borrowing time, and review requests. Borrowers choose pickup and return times, see the total before requesting, and return the item for the next person to use.

**Live site:** [campus-borrow-box.infinityfree.me](https://campus-borrow-box.infinityfree.me/)
**Source:** [github.com/kartikeyakaranam/campus-borrow-box](https://github.com/kartikeyakaranam/campus-borrow-box)

## What it does

- **Accounts and listings:** Register, log in, and publish an item with a category, description, pickup location, and optional photo. Owners can edit or remove available listings.
- **Discovery:** Search by keyword and filter by category.
- **Upfront pricing:** Owners set a cost per hour and minimum duration. Borrowers select pickup and return times and see the calculated total immediately. The server validates and recalculates the amount when the request is sent.
- **Borrowing workflow:** Borrowers send or cancel requests. Owners approve or decline them, then mark returned items available again. Both sides can track requests in their dashboards.
- **Payment record:** A request can specify cash or UPI at pickup. The owner can mark payment as received; the app records the status but does not transfer money.

The return step closes the borrowing cycle and makes the same item available for another request.

## Stack and implementation

| Layer | Technology | Role |
| --- | --- | --- |
| Frontend | Semantic HTML, shared CSS, plain JavaScript | Responsive pages, accessible forms, and immediate price feedback |
| Backend | PHP 8.1+ | Accounts, sessions, validation, request workflow, and server-rendered pages |
| Database | MySQL | Users, items, requests, pricing terms, and loan status |
| Hosting | InfinityFree | Public PHP/MySQL deployment |

The `users`, `items`, and `requests` tables are linked by foreign keys. Request changes use database transactions and row locks so item availability and request status stay in sync. PHP uses prepared statements for database operations, password hashes for accounts, CSRF tokens for changing forms, and output escaping when rendering user content. Uploaded photos are limited to JPEG, PNG, or WebP files under 2 MB; an Apache rule blocks PHP execution in the hosted uploads directory.

## Run locally

1. Install PHP 8.1+ and MySQL, such as through MAMP or XAMPP. Enable PHP's `mysqli`, `mbstring`, and `fileinfo` extensions.
2. Import [`database/schema.sql`](database/schema.sql) into MySQL. For an existing Borrow Box database, apply only the migrations you have not already run, in this order: `database/migrate_2026_10_28.sql`, `database/migrate_2026_10_28_fees.sql`, `database/migrate_2026_10_28_hourly.sql`.
3. Copy [`deployment/config.local.example.php`](deployment/config.local.example.php) to `config.local.php` and set the database host, name, username, and password. The real file is ignored by Git.
4. Serve this directory through PHP, then open it in a browser. For example, from this folder run `php -S 127.0.0.1:8765` and visit `http://127.0.0.1:8765/`.

The application needs a running PHP server and MySQL database; opening `index.php` directly as a file will not run it.

## Verification

Run `python3 tests/smoke.py` against a disposable local database while the PHP site is available at `http://127.0.0.1:8765/`. The script creates test accounts and records and does not clean them up.

The hosted site was checked with two accounts for registration and login, a photographed hourly listing, combined search and filtering, a six-hour ₹150 cost preview and request, approval, and return to available status.

## Deployment

Run `python3 deployment/package.py` to build a setup ZIP and a web-files ZIP in the parent directory. The package excludes local data and database credentials. Follow the [deployment guide](deployment/README.md) to create a fresh database, import the hosting schema, and upload the web files. Keep `config.local.php` outside Git and shared archives.

## Scope

Pickup and return times use India Standard Time. Each started hour is billed as a full hour, subject to the owner's minimum duration. Requests can start within 90 days and last up to 30 days. Each request stores its agreed rate, total, and schedule so later listing edits do not change an existing agreement.

Borrow Box coordinates borrowing and records payment status; users settle cash or UPI directly. It does not verify email addresses, process online payments, or handle disputes automatically.
