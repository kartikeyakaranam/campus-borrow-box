# Borrow Box deployment

`Borrow_Box_Deployment_Ready.zip` has `htdocs/` and `setup/` folders. Keep `setup/` on your computer. `Borrow_Box_Web_Files.zip` contains only the web files and can be uploaded and extracted directly inside the host's `htdocs/` directory.

Current hosted URL: **https://campus-borrow-box.infinityfree.me/**. The site's MySQL database is `if0_43301864_borrow_box` on `sql105.infinityfree.com`. Its password is in the host's protected `htdocs/config.local.php` and must not be added to a ZIP or GitHub. Do not overwrite that file when redeploying.

1. Create a PHP hosting account, subdomain, and MySQL database. The host must provide PHP 8.1+, `mysqli`, `mbstring`, `fileinfo`, and writable storage for `uploads/`.
2. Select the new, empty MySQL database in the host's phpMyAdmin and import `setup/schema.sql`. This hosting schema creates tables inside the selected database; it does not attempt to create another database. Do not import the local test database.
3. Copy `setup/config.local.example.php` to `htdocs/config.local.php` and fill in the four values shown in your hosting control panel. Keep this real config out of GitHub and shared ZIPs. The `.htaccess` file blocks direct web access to it.
4. Upload the contents of `htdocs/` to the remote `htdocs/` folder, or upload and extract `Borrow_Box_Web_Files.zip` there. Make sure `.htaccess` and `uploads/.htaccess` are present.
5. Open the HTTPS site and test registration, login, an hourly listing with a photo, a request from a second account, approval, payment receipt, and return.

The original project report and test script are deliberately excluded from the public web folder. New registrations start with an empty database; there is no admin account or seeded user. The application records cash or UPI payment status but does not process money.
