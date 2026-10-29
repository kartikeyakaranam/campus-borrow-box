# Borrow Box

A student-scale full-stack web project for ICT3131: a campus item borrowing board. Built with HTML5, one shared CSS file, plain JavaScript, PHP, and MySQL. No framework or external API is required.

## Features

1. Create an account and log in securely.
2. List an item with category, description, pickup location, and an optional photo.
3. Search listings by keyword and filter by category.
4. Request an item with a message to the owner.
5. Approve or decline incoming requests.
6. Mark a loan returned so the item becomes available again.
7. View all owned items and incoming/outgoing requests in one dashboard.
8. Remove an available listing or cancel a pending request.
9. Edit an available listing when its details change.
10. Agree on a pickup and return schedule; both students see it in their dashboards.
11. Set a cost per hour and minimum borrowing time on each new listing.
12. Show the borrower an immediate total from the selected pickup and return times; record cash or UPI payment at pickup.

The novelty is a **reuse loop**: one item can help multiple students over time, with the return action reopening it for the next person. Lenders set an hourly rate and minimum time. Borrowers select pickup and return times and see the total immediately before requesting.

## Run locally

1. Install/start MAMP or XAMPP with PHP 8.1+ and MySQL. Enable the PHP `mysqli` and `mbstring` extensions.
2. Import `database/schema.sql` using phpMyAdmin or `mysql -u root -p < database/schema.sql`. For an existing database, run the migration files in order: `database/migrate_2026_10_28.sql`, `database/migrate_2026_10_28_fees.sql`, then `database/migrate_2026_10_28_hourly.sql`. Run only the migrations you have not already applied.
3. For MAMP/XAMPP database settings, copy `deployment/config.local.example.php` to `config.local.php` and fill in the four values. `config.local.php` is ignored by Git. If your local MySQL uses `root` with no password and a database named `borrow_box`, the defaults work without this file.
4. Put this folder inside your server's document root, then open `http://localhost/campus-borrow-box/`.
5. Register two test accounts. With the first, list an item at ₹25 per hour with a 2-hour minimum. With the second, select tomorrow 9:00 AM to 3:00 PM: the page should show 6 hours and ₹150 total. Send the request, then switch back to approve, record payment, and mark the item returned.

For a repeatable local check, start the site at `http://127.0.0.1:8765/` and run `python3 tests/smoke.py` against a disposable test database. The script creates its own test accounts and items. It verifies the main two-account flow and leaves those test records in the database.

Do not open `index.php` as a local file; PHP must run through a web server.

## Deploy

Run `python3 deployment/package.py` to create `Borrow_Box_Deployment_Ready.zip` in the parent folder. Its `htdocs/` contains only the public app files; its `setup/` contains the fresh database schema, a database settings example, and [deployment steps](deployment/README.md). Do not upload the report, tests, or a local database export. A hosting account and a new MySQL database are required before the site can go live.

## Suggested two-person demo and report split

| Student | Two modules/pages to explain | Demo features |
| --- | --- | --- |
| Student 1 | Explore (`index.php`) and Item detail (`item.php`) | Search/filter; choose borrowing times; explain the cost preview and item photos |
| Student 2 | List/edit item (`add.php`, `edit.php`) and Dashboard (`dashboard.php`) | Set an hourly rate and minimum time; approve; record payment and return |

Both should understand `account.php`, `action.php`, `config.php`, the database tables, and the shared layout. Add your actual names, registration numbers, screenshots, code excerpts, and GitHub URL to the supplied report template. Do not claim testing that was not performed.

## Course concepts used

| Course topic | Where it appears |
| --- | --- |
| Semantic HTML and forms | Header, nav, main, sections, labels, inputs |
| Shared CSS, selectors, box model, responsive layout | `assets/style.css` |
| JavaScript events, DOM and validation | `assets/app.js` |
| PHP forms, sessions, server-side validation | `action.php` |
| MySQL, relationships, insert/update/delete, prepared statements | `database/schema.sql`, `action.php` |
| HTTP GET and POST | Search filters use GET; changing data uses POST |

XML and AJAX were covered in the lectures but are deliberately outside this small project's core flow. They can be discussed as future additions without pretending they were implemented.

## Security and limitations

Passwords are hashed, database inputs use prepared statements, output is escaped, and changing forms include a CSRF token. This is suitable for a local classroom demo. There is no email verification or automated dispute handling, and pickup is coordinated manually through the request message.

Borrow Box does not collect money or store payment credentials. Cash or UPI is settled directly between students at pickup, and the owner records whether they received it. The requested time must meet the lender's minimum; each started hour is billed as a full hour. For example, tomorrow 9:00 AM–3:00 PM at ₹25 per hour costs ₹150.00. A 3-hour-15-minute request at ₹12.50 per hour costs ₹50.00 because 4 hours are billed. Requests can start within the next 90 days and last at most 30 days. The browser shows the cost immediately, and the server calculates it again when the request is submitted. Each request retains its original rate, total and schedule if the listing changes later. Older flat-fee listings continue to display their earlier terms; editing one converts it to hourly pricing. Earlier requests retain their agreed fees.

Pickup and return times are interpreted in India Standard Time (IST) for this campus project.

The project report draft and earlier running-site screenshots are in `report/`. The draft follows the supplied template but has not yet been updated for item photos, listing edits, scheduled borrowing, hourly pricing, or payments. It also needs real student identities, faculty, program, signatures, confirmed work split, and GitHub URL before submission.

## Photos

Photos are optional and limited to one JPEG, PNG, or WebP under 2 MB per item. The web server must be able to write to `uploads/`. The folder is excluded from version control except for its setup files. The Apache rule in that folder blocks PHP execution; on another web server, configure the equivalent rule before accepting public uploads. Capture fresh screenshots when updating the report.
