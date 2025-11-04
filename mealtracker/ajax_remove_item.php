<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';
header('Content-Type: application/json');

$itemId = $_POST['item_id'] ?? null;
if (!$itemId) { echo json_encode(['success'=>false,'message'=>'Invalid item']); exit; }

$stmt = $pdo->prepare("SELECT * FROM dishitems WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) { echo json_encode(['success'=>false,'message'=>'Item not found']); exit; }

$dishId = $item['dishid'];

$stmt = $pdo->prepare("SELECT * FROM food WHERE id = ?");
$stmt->execute([$item['fooditem']]);
$food = $stmt->fetch(PDO::FETCH_ASSOC);

$subKcal =    $item['amount'] / $food[unit] * $food['kcal'];
$subProtein = $item['amount'] / $food[unit] * $food['protein'];
$subCarbs =   $item['amount'] / $food[unit] * $food['carbs'];
$subFat =     $item['amount'] / $food[unit] * $food['fat'];

$stmt = $pdo->prepare("
    UPDATE dish
    SET kcal = kcal - ?, protein = protein - ?, carbs = carbs - ?, fat = fat - ?
    WHERE id = ?
");
$stmt->execute([$subKcal, $subProtein, $subCarbs, $subFat, $dishId]);

$stmt = $pdo->prepare("
    UPDATE food f
    JOIN dish d ON d.id = f.dishid
    SET f.kcal = d.kcal, f.protein = d.protein, f.carbs = d.carbs, f.fat = d.fat
    WHERE f.dishid = ?
");
$stmt->execute([$dishId]);

$stmt = $pdo->prepare("DELETE FROM dishitems WHERE id = ?");
$stmt->execute([$itemId]);

$stmt = $pdo->prepare("SELECT * FROM dish WHERE id = ?");
$stmt->execute([$dishId]);
$dish = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'dish'=>$dish]);

