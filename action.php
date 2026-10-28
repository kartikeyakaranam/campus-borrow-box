<?php
require_once __DIR__ . '/config.php';
check_post();
$action = (string) ($_POST['action'] ?? '');
function valid_money(string $value, float $maximum): bool {
    return (bool) preg_match('/^(?:0|[1-9]\d{0,4})(?:\.\d{1,2})?$/', $value) && (float)$value <= $maximum;
}
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
    $mode=(string)($_POST['pricing_mode']??'flat');
    $fee=$mode==='flat' ? (string)($_POST['fee']??'') : '0';
    $rate=$mode==='hourly' ? (string)($_POST['hourly_rate']??'') : '0';
    $minimum=filter_var($_POST['minimum_hours']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>168]]);
    if ($title==='' || mb_strlen($title)>100 || !in_array($category,['Books','Electronics','Lab','Other'],true) || $description==='' || mb_strlen($description)>600 || $location==='' || mb_strlen($location)>100 || !valid_money($fee,10000) || !valid_money($rate,1000) || !in_array($mode,['flat','hourly'],true) || $minimum===false) {
        flash('Complete every field within the stated limits.', 'error'); redirect('add.php');
    }
    $fee=number_format((float)$fee,2,'.',''); $rate=number_format((float)$rate,2,'.','');
    try { $image=save_item_image(); } catch (RuntimeException $error) { flash($error->getMessage(),'error'); redirect('add.php'); }
    $s=db()->prepare('INSERT INTO items (owner_id,title,category,description,pickup_location,borrowing_fee,pricing_mode,hourly_rate,minimum_hours,image_filename) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $s->bind_param('isssssssis',$uid,$title,$category,$description,$location,$fee,$mode,$rate,$minimum,$image); $s->execute();
    flash('Your item is now listed.'); redirect('item.php?id='.db()->insert_id);
}
if ($action === 'edit_item') {
    $itemId=(int)($_POST['item_id']??0);
    $title=trim((string)($_POST['title']??'')); $category=(string)($_POST['category']??'');
    $description=trim((string)($_POST['description']??'')); $location=trim((string)($_POST['location']??''));
    $mode=(string)($_POST['pricing_mode']??'flat');
    $fee=$mode==='flat' ? (string)($_POST['fee']??'') : '0';
    $rate=$mode==='hourly' ? (string)($_POST['hourly_rate']??'') : '0';
    $minimum=filter_var($_POST['minimum_hours']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>168]]);
    if ($title==='' || mb_strlen($title)>100 || !in_array($category,['Books','Electronics','Lab','Other'],true) || $description==='' || mb_strlen($description)>600 || $location==='' || mb_strlen($location)>100 || !valid_money($fee,10000) || !valid_money($rate,1000) || !in_array($mode,['flat','hourly'],true) || $minimum===false) {
        flash('Complete every field within the stated limits.', 'error'); redirect('edit.php?id='.$itemId);
    }
    $fee=number_format((float)$fee,2,'.',''); $rate=number_format((float)$rate,2,'.','');
    $old=db()->prepare("SELECT image_filename FROM items WHERE id=? AND owner_id=? AND status='available'");
    $old->bind_param('ii',$itemId,$uid); $old->execute(); $existing=$old->get_result()->fetch_assoc();
    if (!$existing) { flash('Only your available listing can be edited.','error'); redirect('dashboard.php'); }
    try { $image=save_item_image(); } catch (RuntimeException $error) { flash($error->getMessage(),'error'); redirect('edit.php?id='.$itemId); }
    $image=$image ?? $existing['image_filename'];
    $s=db()->prepare("UPDATE items SET title=?,category=?,description=?,pickup_location=?,borrowing_fee=?,pricing_mode=?,hourly_rate=?,minimum_hours=?,image_filename=? WHERE id=? AND owner_id=? AND status='available'");
    $s->bind_param('sssssssisii',$title,$category,$description,$location,$fee,$mode,$rate,$minimum,$image,$itemId,$uid); $s->execute();
    if ($s->affected_rows && $existing['image_filename'] && $image !== $existing['image_filename']) @unlink(__DIR__.'/uploads/'.$existing['image_filename']);
    flash('Listing saved.');
    redirect('item.php?id='.$itemId);
}
if ($action === 'request') {
    $itemId=(int)($_POST['item_id']??0); $message=trim((string)($_POST['message']??''));
    $method=(string)($_POST['payment_method']??'');
    if ($message==='' || mb_strlen($message)>250) { flash('Write a short message of up to 250 characters.', 'error'); redirect('item.php?id='.$itemId); }
    $startText=(string)($_POST['start_at']??''); $endText=(string)($_POST['end_at']??'');
    $start=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$startText);
    $end=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$endText);
    if (!$start || !$end || $start->format('Y-m-d\TH:i')!==$startText || $end->format('Y-m-d\TH:i')!==$endText) {
        flash('Choose valid pickup and return times.','error'); redirect('item.php?id='.$itemId);
    }
    $seconds=$end->getTimestamp()-$start->getTimestamp();
    if ($start<new DateTimeImmutable('-1 minute') || $start>new DateTimeImmutable('+90 days') || $seconds<=0 || $seconds>30*24*3600) {
        flash('Choose a future pickup and a return within 30 days.','error'); redirect('item.php?id='.$itemId);
    }
    db()->begin_transaction();
    try {
        $s=db()->prepare('SELECT * FROM items WHERE id=? FOR UPDATE'); $s->bind_param('i',$itemId); $s->execute(); $item=$s->get_result()->fetch_assoc();
        if (!$item || (int)$item['owner_id']===$uid || $item['status']!=='available') throw new RuntimeException('This item is unavailable.');
        if ($seconds<(int)$item['minimum_hours']*3600) throw new RuntimeException('Choose at least '.$item['minimum_hours'].' hours for this item.');
        $s=db()->prepare("SELECT id FROM requests WHERE item_id=? AND borrower_id=? AND status IN ('pending','approved') LIMIT 1");
        $s->bind_param('ii',$itemId,$uid); $s->execute();
        if ($s->get_result()->fetch_assoc()) throw new RuntimeException('You already have an active request for this item.');
        $hours=(int)ceil($seconds/3600);
        $mode=$item['pricing_mode'];
        $rate=$mode==='hourly' ? $item['hourly_rate'] : null;
        $fee=$mode==='hourly' ? number_format(((int)round((float)$item['hourly_rate']*100)*$hours)/100,2,'.','') : $item['borrowing_fee'];
        if ((float)$fee>0 && !in_array($method,['cash','upi'],true)) throw new RuntimeException('Choose cash or UPI for payment at pickup.');
        if ((float)$fee===0.0) $method=null;
        $startSql=$start->format('Y-m-d H:i:s'); $endSql=$end->format('Y-m-d H:i:s');
        $s=db()->prepare('INSERT INTO requests (item_id,borrower_id,message,agreed_fee,pricing_mode,agreed_rate,billable_hours,start_at,end_at,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $s->bind_param('iissssisss',$itemId,$uid,$message,$fee,$mode,$rate,$hours,$startSql,$endSql,$method); $s->execute();
        db()->commit(); flash('Request sent to the owner.');
    } catch (Throwable $error) { db()->rollback(); flash($error->getMessage(),'error'); }
    redirect('dashboard.php');
}
if ($action === 'delete_item') {
    $itemId=(int)($_POST['item_id']??0);
    $old=db()->prepare("SELECT image_filename FROM items WHERE id=? AND owner_id=? AND status='available'");
    $old->bind_param('ii',$itemId,$uid); $old->execute(); $image=$old->get_result()->fetch_assoc()['image_filename'] ?? null;
    $s=db()->prepare("DELETE FROM items WHERE id=? AND owner_id=? AND status='available'"); $s->bind_param('ii',$itemId,$uid); $s->execute();
    if ($s->affected_rows && $image) @unlink(__DIR__.'/uploads/'.$image);
    flash($s->affected_rows ? 'Listing removed.' : 'A borrowed item cannot be removed.', $s->affected_rows ? 'success' : 'error'); redirect('dashboard.php');
}
if ($action === 'mark_paid') {
    $requestId=(int)($_POST['request_id']??0);
    $s=db()->prepare("UPDATE requests JOIN items ON items.id=requests.item_id SET requests.paid_at=NOW() WHERE requests.id=? AND items.owner_id=? AND requests.status IN ('approved','returned') AND requests.agreed_fee>0 AND requests.paid_at IS NULL");
    $s->bind_param('ii',$requestId,$uid); $s->execute();
    flash($s->affected_rows ? 'Payment marked received.' : 'This payment cannot be marked received.', $s->affected_rows ? 'success' : 'error');
    redirect('dashboard.php');
}
if (in_array($action,['approve','decline','return','cancel'],true)) {
    $requestId=(int)($_POST['request_id']??0);
    $dueDate=(string)($_POST['due_date']??'');
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
            if ($action==='approve') {
                if ($r['end_at']) {
                    if (strtotime($r['start_at'])<time()-60) throw new RuntimeException('The requested pickup time has passed. Ask the borrower to request new times.');
                    $dueDate=substr($r['end_at'],0,10);
                } else {
                    $parsedDate=DateTimeImmutable::createFromFormat('!Y-m-d',$dueDate);
                    if (!$parsedDate || $parsedDate->format('Y-m-d')!==$dueDate || $dueDate<date('Y-m-d') || $dueDate>date('Y-m-d',strtotime('+1 year'))) throw new RuntimeException('Choose a return date within the next year.');
                }
            }
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
