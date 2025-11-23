<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'db.php';
require_once("logging.php");

$user_id = $_SESSION['user_id'];
$dish_id = (int)($_POST['dish_id'] ?? 0);

if ($dish_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid dish ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM dishitems WHERE dishid = ? and addedby = ?")->execute([$dish_id, $user_id]);
    $pdo->prepare("DELETE FROM food WHERE dishid = ? and addedby = ?")->execute([$dish_id, $user_id]);
    $pdo->prepare("DELETE FROM dish WHERE id = ? and addedby = ?")->execute([$dish_id, $user_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
	log_error("failed deleting dish: " . $e->getMessage());
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'error']);
}
?>

