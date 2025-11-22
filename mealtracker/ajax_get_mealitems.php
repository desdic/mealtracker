<?php
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php'); 

header('Content-Type: application/json');

$itemId = isset($_GET['id']) ? intval($_GET['id']) : null;
$userid = $_SESSION['user_id'];

if (!$itemId) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(["error" => "Missing item ID."]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT mi.id, mi.mealtype, mi.amount, mi.fooditem, f.title, f.kcal, f.unit
    FROM mealitems mi
    JOIN food f ON mi.fooditem = f.id
    WHERE mi.id = ? AND mi.userid = ?
");
$stmt->execute([$itemId, $userid]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(["error" => "Item not found or access denied."]);
    exit;
}

ob_end_clean(); 

echo json_encode($item);
