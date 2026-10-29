# Borrow Box deployment

This package has two folders. Upload only the **contents** of `htdocs/` to the host's `htdocs/` directory. The `setup/` folder stays on your computer.

1. Create a PHP hosting account, subdomain, and MySQL database. The host must provide PHP 8.1+, `mysqli`, `mbstring`, `fileinfo`, and writable storage for `uploads/`.
2. Import `setup/schema.sql` into the new, empty MySQL database using the host's phpMyAdmin. Do not import the local test database.
3. Copy `setup/config.local.example.php` to `htdocs/config.local.php` and fill in the four values shown in your hosting control panel. Keep this real config out of GitHub and shared ZIPs. The `.htaccess` file blocks direct web access to it.
4. Upload the contents of `htdocs/` to the remote `htdocs/` folder. Make sure `.htaccess` and `uploads/.htaccess` are uploaded as hidden files.
5. Open the HTTPS site and test registration, login, an hourly listing with a photo, a request from a second account, approval, payment receipt, and return.

The original project report and test script are deliberately excluded from the public web folder. New registrations start with an empty database; there is no admin account or seeded user. The application records cash or UPI payment status but does not process money.
