<?php
require_once __DIR__ . '/config.php';
function page_header(string $title): void { ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="A simple student-run campus borrowing board.">
  <title><?= e($title) ?> · Borrow Box</title>
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header"><div class="container header-inner">
  <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">↗</span> Borrow Box</a>
  <nav aria-label="Main navigation">
    <a href="index.php">Explore</a>
    <?php if (user_id()): ?><a href="add.php">List an item</a><a href="dashboard.php">My dashboard</a>
    <form class="inline" action="action.php" method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="nav-button" name="action" value="logout">Log out</button></form>
    <?php else: ?><a href="account.php">Log in / sign up</a><?php endif; ?>
  </nav>
</div></header>
<main id="main" class="container">
<?php if (isset($_SESSION['flash'])): [$message, $type] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="notice <?= e($type) ?>" role="status"><?= e($message) ?></div>
<?php endif; ?>
<?php }
function page_footer(): void { ?>
</main><footer class="site-footer"><div class="container footer-inner"><span>Borrow Box · made for students, by students</span><span>Share more. Buy less.</span></div></footer>
</body></html>
<?php }
