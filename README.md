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
10. Set a return date when approving a request; both students see it in their dashboards.
11. Set a flat borrowing fee; the borrower chooses cash or UPI at pickup, and the owner records receipt.

The novelty is a **reuse loop**: one item can help multiple students over time, with the return action reopening it for the next person. A simple fee agreement makes that exchange practical without requiring an online payment gateway.

## Run locally

1. Install/start MAMP or XAMPP with PHP 8.1+ and MySQL. Enable the PHP `mysqli` and `mbstring` extensions.
2. Import `database/schema.sql` using phpMyAdmin or `mysql -u root -p < database/schema.sql`. For an existing database, run `database/migrate_2026_10_28.sql` and then `database/migrate_2026_10_28_fees.sql` once each. If you already ran the first migration, run only the fees migration.
3. Edit the four database constants in `config.php` to match your local MySQL settings.
4. Put this folder inside your server's document root, then open `http://localhost/campus-borrow-box/`.
5. Register two test accounts. With the first, list an item. With the second, request it. Switch back to approve, then mark it returned.

For a repeatable local check, start the site at `http://127.0.0.1:8765/` and run `python3 tests/smoke.py` against a disposable test database. The script creates its own test accounts and items. It verifies the main two-account flow and leaves those test records in the database.

Do not open `index.php` as a local file; PHP must run through a web server.

## Suggested two-person demo and report split

| Student | Two modules/pages to explain | Demo features |
| --- | --- | --- |
| Student 1 | Explore (`index.php`) and Item detail (`item.php`) | Search/filter; send a request with a payment method; explain item photos |
| Student 2 | List/edit item (`add.php`, `edit.php`) and Dashboard (`dashboard.php`) | Set a fee; approve with a return date; record payment and return |

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

Borrow Box does not collect money or store payment credentials. The displayed amount is a flat fee for one borrowing request. Cash or UPI is settled directly between the students at pickup, and the owner records whether they received it. Existing requests retain the fee shown when requested, even if the listing price changes later.

The project report draft and earlier running-site screenshots are in `report/`. The draft follows the supplied template but has not yet been updated for item photos, listing edits, return dates, or borrowing fees. It also needs real student identities, faculty, program, signatures, confirmed work split, and GitHub URL before submission.

## Photos

Photos are optional and limited to one JPEG, PNG, or WebP under 2 MB per item. The web server must be able to write to `uploads/`. The folder is excluded from version control except for its setup files. The Apache rule in that folder blocks PHP execution; on another web server, configure the equivalent rule before accepting public uploads. Capture fresh screenshots when updating the report.
