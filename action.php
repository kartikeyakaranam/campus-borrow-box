<?php
require_once __DIR__ . '/config.php';
check_post();
$action = (string) ($_POST['action'] ?? '');
if ($action === 'logout') { $_SESSION = []; session_regenerate_id(true); redirect('index.php'); }

if ($action === 'register') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        flash('Please enter a name, valid email and password of at least 8 characters.', 'error'); redirect('account.php');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $s = db()->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
    $s->bind_param('sss', $name, $email, $hash);
    try { $s->execute(); } catch (mysqli_sql_exception $error) { flash('That email is already registered. Please log in.', 'error'); redirect('account.php'); }
    session_regenerate_id(true); $_SESSION['user_id'] = db()->insert_id; $_SESSION['user_name'] = $name;
    flash('Welcome to Borrow Box!'); redirect('dashboard.php');
}
if ($action === 'login') {
    $email = strtolower(trim((string) ($_POST['email'] ?? ''))); $password = (string) ($_POST['password'] ?? '');
    $s = db()->prepare('SELECT id,name,password_hash FROM users WHERE email=?'); $s->bind_param('s',$email); $s->execute(); $user=$s->get_result()->fetch_assoc();
    if (!$user || !password_verify($password,$user['password_hash'])) { flash('Email or password is incorrect.', 'error'); redirect('account.php'); }
    session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_name']=$user['name'];
    flash('Welcome back!'); redirect('dashboard.php');
}
require_login();
$uid = user_id();
if ($action === 'add_item') {
    $title=trim((string)($_POST['title']??'')); $category=(string)($_POST['category']??'');
    $description=trim((string)($_POST['description']??'')); $location=trim((string)($_POST['location']??''));
    if ($title==='' || mb_strlen($title)>100 || !in_array($category,['Books','Electronics','Lab','Other'],true) || $description==='' || mb_strlen($description)>600 || $location==='' || mb_strlen($location)>100) {
        flash('Complete every field within the stated limits.', 'error'); redirect('add.php');
    }
    try { $image=save_item_image(); } catch (RuntimeException $error) { flash($error->getMessage(),'error'); redirect('add.php'); }
    $s=db()->prepare('INSERT INTO items (owner_id,title,category,description,pickup_location,image_filename) VALUES (?,?,?,?,?,?)');
    $s->bind_param('isssss',$uid,$title,$category,$description,$location,$image); $s->execute();
    flash('Your item is now listed.'); redirect('item.php?id='.db()->insert_id);
}
if ($action === 'edit_item') {
    $itemId=(int)($_POST['item_id']??0);
    $title=trim((string)($_POST['title']??'')); $category=(string)($_POST['category']??'');
    $description=trim((string)($_POST['description']??'')); $location=trim((string)($_POST['location']??''));
    if ($title==='' || mb_strlen($title)>100 || !in_array($category,['Books','Electronics','Lab','Other'],true) || $description==='' || mb_strlen($description)>600 || $location==='' || mb_strlen($location)>100) {
        flash('Complete every field within the stated limits.', 'error'); redirect('edit.php?id='.$itemId);
    }
    $old=db()->prepare("SELECT image_filename FROM items WHERE id=? AND owner_id=? AND status='available'");
    $old->bind_param('ii',$itemId,$uid); $old->execute(); $existing=$old->get_result()->fetch_assoc();
    if (!$existing) { flash('Only your available listing can be edited.','error'); redirect('dashboard.php'); }
    try { $image=save_item_image(); } catch (RuntimeException $error) { flash($error->getMessage(),'error'); redirect('edit.php?id='.$itemId); }
    $image=$image ?? $existing['image_filename'];
    $s=db()->prepare("UPDATE items SET title=?,category=?,description=?,pickup_location=?,image_filename=? WHERE id=? AND owner_id=? AND status='available'");
    $s->bind_param('sssssii',$title,$category,$description,$location,$image,$itemId,$uid); $s->execute();
    if ($s->affected_rows && $existing['image_filename'] && $image !== $existing['image_filename']) @unlink(__DIR__.'/uploads/'.$existing['image_filename']);
    flash('Listing saved.');
    redirect('item.php?id='.$itemId);
}
if ($action === 'request') {
    $itemId=(int)($_POST['item_id']??0); $message=trim((string)($_POST['message']??''));
    if ($message==='' || mb_strlen($message)>250) { flash('Write a short message of up to 250 characters.', 'error'); redirect('item.php?id='.$itemId); }
    $s=db()->prepare("INSERT INTO requests (item_id,borrower_id,message) SELECT id,?,? FROM items WHERE id=? AND owner_id<>? AND status='available' AND NOT EXISTS (SELECT 1 FROM requests WHERE item_id=? AND borrower_id=? AND status IN ('pending','approved'))");
    $s->bind_param('isiiii',$uid,$message,$itemId,$uid,$itemId,$uid);
    $s->execute();
    if ($s->affected_rows!==1) { flash('This item is unavailable or you already have an active request.', 'error'); redirect('item.php?id='.$itemId); }
    flash('Request sent to the owner.'); redirect('dashboard.php');
}
if ($action === 'delete_item') {
    $itemId=(int)($_POST['item_id']??0);
    $old=db()->prepare("SELECT image_filename FROM items WHERE id=? AND owner_id=? AND status='available'");
    $old->bind_param('ii',$itemId,$uid); $old->execute(); $image=$old->get_result()->fetch_assoc()['image_filename'] ?? null;
    $s=db()->prepare("DELETE FROM items WHERE id=? AND owner_id=? AND status='available'"); $s->bind_param('ii',$itemId,$uid); $s->execute();
    if ($s->affected_rows && $image) @unlink(__DIR__.'/uploads/'.$image);
    flash($s->affected_rows ? 'Listing removed.' : 'A borrowed item cannot be removed.', $s->affected_rows ? 'success' : 'error'); redirect('dashboard.php');
}
if (in_array($action,['approve','decline','return','cancel'],true)) {
    $requestId=(int)($_POST['request_id']??0);
    $dueDate=(string)($_POST['due_date']??'');
    $parsedDate=DateTimeImmutable::createFromFormat('!Y-m-d',$dueDate);
    if ($action==='approve' && (!$parsedDate || $parsedDate->format('Y-m-d')!==$dueDate || $dueDate<date('Y-m-d') || $dueDate>date('Y-m-d',strtotime('+1 year')))) {
        flash('Choose a return date within the next year.', 'error'); redirect('dashboard.php');
    }
    db()->begin_transaction();
    try {
        $s=db()->prepare('SELECT requests.*, items.owner_id, items.status AS item_status FROM requests JOIN items ON items.id=requests.item_id WHERE requests.id=? FOR UPDATE');
        $s->bind_param('i',$requestId); $s->execute(); $r=$s->get_result()->fetch_assoc();
        if (!$r) throw new RuntimeException('Request not found.');
        if ($action==='cancel') {
            if ((int)$r['borrower_id']!==$uid || $r['status']!=='pending') throw new RuntimeException('Request cannot be cancelled.');
            $next='declined';
        } else {
            if ((int)$r['owner_id']!==$uid) throw new RuntimeException('Only the owner can do that.');
            if ($action==='return' && ($r['status']!=='approved' || $r['item_status']!=='lent')) throw new RuntimeException('This loan is not active.');
            if (in_array($action,['approve','decline'],true) && ($r['status']!=='pending' || $r['item_status']!=='available')) throw new RuntimeException('This request is no longer open.');
            $next=['approve'=>'approved','decline'=>'declined','return'=>'returned'][$action];
        }
        $s=db()->prepare('UPDATE requests SET status=? WHERE id=?'); $s->bind_param('si',$next,$requestId); $s->execute();
        if ($action==='approve') {
            $s=db()->prepare('UPDATE requests SET due_date=? WHERE id=?'); $s->bind_param('si',$dueDate,$requestId); $s->execute();
            $s=db()->prepare("UPDATE items SET status='lent' WHERE id=?"); $s->bind_param('i',$r['item_id']); $s->execute();
            $s=db()->prepare("UPDATE requests SET status='declined' WHERE item_id=? AND id<>? AND status='pending'"); $s->bind_param('ii',$r['item_id'],$requestId); $s->execute();
        }
        if ($action==='return') { $s=db()->prepare("UPDATE items SET status='available' WHERE id=?"); $s->bind_param('i',$r['item_id']); $s->execute(); }
        db()->commit(); flash(['approve'=>'Request approved.','decline'=>'Request declined.','return'=>'Item marked returned and available again.','cancel'=>'Request cancelled.'][$action]);
    } catch (Throwable $error) { db()->rollback(); flash($error->getMessage(),'error'); }
    redirect('dashboard.php');
}
http_response_code(400); exit('Unknown action.');
