<?php
require_once __DIR__ . '/partials.php';
$q = trim((string) ($_GET['q'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$allowed = ['Books','Electronics','Lab','Other'];
if (!in_array($category, $allowed, true)) $category = '';
$sql = "SELECT items.*, users.name AS owner_name FROM items JOIN users ON users.id=items.owner_id WHERE items.status='available'";
$types = ''; $values = [];
if ($q !== '') { $sql .= ' AND (items.title LIKE ? OR items.description LIKE ?)'; $types .= 'ss'; $values[] = "%$q%"; $values[] = "%$q%"; }
if ($category !== '') { $sql .= ' AND items.category=?'; $types .= 's'; $values[] = $category; }
$sql .= ' ORDER BY items.created_at DESC';
$stmt = db()->prepare($sql);
if ($values) $stmt->bind_param($types, ...$values);
$stmt->execute(); $items = $stmt->get_result();
page_header('Explore');
?>
<section class="hero"><div class="hero-copy"><p class="eyebrow">A small idea for a more useful campus</p><h1>Borrow what you need.<br><em>Share what you have.</em></h1><p>Textbooks, calculators, lab supplies and the little things you only need for a while. Find them nearby, ask to borrow, and return them when done.</p><div class="hero-actions"><a class="button" href="<?= user_id() ? 'add.php' : 'account.php' ?>">Share an item <span aria-hidden="true">→</span></a><a class="text-link" href="#items">Explore items ↓</a></div></div><div class="hero-art" aria-hidden="true"><div class="art-circle"><span class="art-book">BOOK</span><span class="art-calc">123<br>+ 45</span><span class="art-note">borrow<br>box ✳</span></div></div></section>
<section id="items" class="section"><div class="section-heading"><div><p class="eyebrow">Available on campus</p><h2>Find your next borrow</h2></div><p>Everything here is shared by another student.</p></div>
<form class="filters" method="get" action="index.php" role="search"><div class="field grow"><label for="q">Search items</label><input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Try calculator or textbook"></div><div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($allowed as $choice): ?><option value="<?= e($choice) ?>" <?= $choice === $category ? 'selected' : '' ?>><?= e($choice) ?></option><?php endforeach; ?></select></div><button class="button filter-button" type="submit">Search</button></form>
<div class="result-count" aria-live="polite"><?= $items->num_rows ?> item<?= $items->num_rows === 1 ? '' : 's' ?> found</div>
<div class="card-grid"><?php while ($item = $items->fetch_assoc()): ?><article class="item-card"><?php if ($item['image_filename']): ?><img class="item-photo" src="uploads/<?= e($item['image_filename']) ?>" alt="<?= e($item['title']) ?>"><!-- Item photo uploaded by its owner --><?php else: ?><div class="item-icon <?= strtolower(e($item['category'])) ?>" aria-hidden="true"><?= ['Books'=>'▤','Electronics'=>'⌁','Lab'=>'⚗','Other'=>'✳'][$item['category']] ?></div><?php endif; ?><div class="card-body"><span class="tag"><?= e($item['category']) ?></span><h3><?= e($item['title']) ?></h3><p><?= e($item['description']) ?></p><div class="card-meta"><span>📍 <?= e($item['pickup_location']) ?></span><span>By <?= e($item['owner_name']) ?></span></div><a class="card-link" href="item.php?id=<?= (int) $item['id'] ?>">View item <span aria-hidden="true">→</span></a></div></article><?php endwhile; ?></div>
<?php if ($items->num_rows === 0): ?><div class="empty"><h3>No items match yet</h3><p>Try a different search or be the first to share something.</p></div><?php endif; ?></section>
<section class="how"><p class="eyebrow">How it works</p><h2>Three easy steps</h2><div class="steps"><div><strong>01</strong><h3>List it</h3><p>Add an item you can spare for a short time.</p></div><div><strong>02</strong><h3>Request it</h3><p>Send a short note to the owner.</p></div><div><strong>03</strong><h3>Return it</h3><p>The owner marks it returned so someone else can use it.</p></div></div></section>
<?php page_footer(); ?>
