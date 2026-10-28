# Borrow Box

A student-scale full-stack web project for ICT3131: a campus item borrowing board. Built with HTML5, one shared CSS file, plain JavaScript, PHP, and MySQL. No framework or external API is required.

## Features

1. Create an account and log in securely.
2. List an item with category, description and pickup location.
3. Search listings by keyword and filter by category.
4. Request an item with a message to the owner.
5. Approve or decline incoming requests.
6. Mark a loan returned so the item becomes available again.
7. View all owned items and incoming/outgoing requests in one dashboard.
8. Remove an available listing or cancel a pending request.

The novelty is a **reuse loop**: one item can help multiple students over time, with the return action reopening it for the next person. The UI tells that story without needing payments, maps, or AI.

## Run locally

1. Install/start MAMP or XAMPP with PHP 8.1+ and MySQL. Enable the PHP `mysqli` and `mbstring` extensions.
2. Import `database/schema.sql` using phpMyAdmin or `mysql -u root -p < database/schema.sql`.
3. Edit the four database constants in `config.php` to match your local MySQL settings.
4. Put this folder inside your server's document root, then open `http://localhost/campus-borrow-box/`.
5. Register two test accounts. With the first, list an item. With the second, request it. Switch back to approve, then mark it returned.

For a repeatable local check, start the site at `http://127.0.0.1:8765/` and run `python3 tests/smoke.py` against a disposable test database. The script creates its own test accounts and items. It verifies the main two-account flow and leaves those test records in the database.

Do not open `index.php` as a local file; PHP must run through a web server.

## Suggested two-person demo and report split

| Student | Two modules/pages to explain | Demo features |
| --- | --- | --- |
| Student 1 | Explore (`index.php`) and Item detail (`item.php`) | Search/filter; send a request |
| Student 2 | List item (`add.php`) and Dashboard (`dashboard.php`) | Publish a listing; approve and return it |

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

The project report draft and actual running-site screenshots are in `report/`. The draft follows the supplied template, but its student identities, faculty, program, signatures, confirmed work split, and GitHub URL still need real information before submission.
