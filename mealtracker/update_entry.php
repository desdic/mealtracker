<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
	exit(json_encode(['success'=>false,'error'=>'Not logged in']));
}

$user_id = $_SESSION['user_id'];
$id = $_POST['id'] ?? null;
$amount = $_POST['amount'] ?? null;

if (!$id || !$amount) exit(json_encode(['success'=>false,'error'=>'Missing fields']));

include 'db.php';

$stmt = $pdo->prepare("UPDATE mealitems SET amount=? WHERE id=? and userid=?");
if ($stmt->execute([$amount, $id, $user_id])) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
?>

