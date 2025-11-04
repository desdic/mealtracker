<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include('db.php');

if (!isset($_GET['mealday'])) {
    die('0');
}

$mealday = intval($_GET['mealday']);

$stmt = $pdo->prepare("SELECT mi.amount, f.kcal, f.unit 
                       FROM mealitems mi
                       JOIN food f ON mi.fooditem = f.id
                       WHERE mi.mealday = ?");
$stmt->execute([$mealday]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $i) {
    $unit = $i['unit'] > 0 ? $i['unit'] : 1;
    $total += ($i['amount'] / $unit) * $i['kcal'];
}

echo number_format($total, 1);

