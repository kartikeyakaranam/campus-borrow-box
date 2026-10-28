<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'borrow_box';
const DB_USER = 'root';
const DB_PASS = '';

function db(): mysqli {
    static $connection = null;
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($connection->connect_error) {
            http_response_code(500);
            exit('Database connection failed. Check config.php and import database/schema.sql.');
        }
        $connection->set_charset('utf8mb4');
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
