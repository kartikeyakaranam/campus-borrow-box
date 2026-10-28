<?php
require_once __DIR__ . '/partials.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT items.*, users.name AS owner_name FROM items JOIN users ON users.id=items.owner_id WHERE items.id=?');
$stmt->bind_param('i', $id); $stmt->execute(); $item = $stmt->get_result()->fetch_assoc();
if (!$item) { http_response_code(404); page_header('Item not found'); echo '<section class="empty"><h1>Item not found</h1><a href="index.php">Back to items</a></section>'; page_footer(); exit; }
page_header($item['title']);
?>
<p class="breadcrumb"><a href="index.php">Explore</a> / <?= e($item['category']) ?></p>
<div class="detail-layout"><?php if ($item['image_filename']): ?><img class="detail-art item-photo" src="uploads/<?= e($item['image_filename']) ?>" alt="<?= e($item['title']) ?>"><?php else: ?><div class="detail-art item-icon <?= strtolower(e($item['category'])) ?>" aria-hidden="true"><?= ['Books'=>'▤','Electronics'=>'⌁','Lab'=>'⚗','Other'=>'✳'][$item['category']] ?></div><?php endif; ?><section class="detail-content"><span class="tag"><?= e($item['category']) ?></span><h1><?= e($item['title']) ?></h1><p class="lead"><?= nl2br(e($item['description'])) ?></p><div class="facts"><div><span>Pickup spot</span><strong><?= e($item['pickup_location']) ?></strong></div><div><span>Shared by</span><strong><?= e($item['owner_name']) ?></strong></div><div><span>Status</span><strong><?= $item['status'] === 'available' ? 'Available' : 'Currently borrowed' ?></strong></div></div>
<?php if (!user_id()): ?><p><a class="button" href="account.php">Log in to request</a></p>
<?php elseif (user_id() === (int) $item['owner_id']): ?><p class="hint">This is your listing. Manage it from your dashboard.</p>
<?php elseif ($item['status'] === 'available'): ?><form action="action.php" method="post" class="request-form" data-validate><h2>Ask to borrow</h2><p>Tell the owner when you need it and how you will return it.</p><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><div class="field"><label for="message">Message to owner</label><textarea id="message" name="message" required maxlength="250" rows="3" placeholder="Hi! Could I borrow this for my lab on Friday?"></textarea></div><button class="button" name="action" value="request">Send request</button></form>
<?php else: ?><p class="hint">This item is currently borrowed. Check back later.</p><?php endif; ?></section></div>
<?php page_footer(); ?>
