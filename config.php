<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set('Asia/Kolkata');

$settingsFile = __DIR__ . '/config.local.php';
$dbSettings = is_file($settingsFile) ? require $settingsFile : [];
if (!is_array($dbSettings)) {
    http_response_code(500);
    exit('Database settings are invalid.');
}
define('DB_HOST', (string) ($dbSettings['host'] ?? (getenv('BORROW_BOX_DB_HOST') ?: 'localhost')));
define('DB_NAME', (string) ($dbSettings['name'] ?? (getenv('BORROW_BOX_DB_NAME') ?: 'borrow_box')));
define('DB_USER', (string) ($dbSettings['user'] ?? (getenv('BORROW_BOX_DB_USER') ?: 'root')));
define('DB_PASS', (string) ($dbSettings['password'] ?? (getenv('BORROW_BOX_DB_PASS') ?: '')));

function db(): mysqli {
    static $connection = null;
    if ($connection === null) {
        try {
            $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $connection->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $error) {
            error_log('Borrow Box database connection failed: ' . $error->getMessage());
            http_response_code(500);
            exit('Database unavailable. Check the site setup.');
        }
    }
    return $connection;
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function user_id(): int { return (int) ($_SESSION['user_id'] ?? 0); }
function require_login(): void {
    if (!user_id()) { header('Location: account.php'); exit; }
}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function check_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Invalid request token. Refresh and try again.');
    }
}
function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = [$message, $type];
}
function redirect(string $path): void { header('Location: ' . $path); exit; }
function save_item_image(): ?string {
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Choose an image smaller than 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    if (!isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) throw new RuntimeException('Use a JPEG, PNG or WebP image.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $name)) throw new RuntimeException('Image could not be saved.');
    return $name;
}
