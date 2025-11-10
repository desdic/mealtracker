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
$userid = $_SESSION['user_id'];

$sql = "
SELECT 
  SUM(mi.amount / f.unit * f.kcal) total_kcal,
  SUM(mi.amount / f.unit * f.protein) total_protein,
  SUM(mi.amount / f.unit * f.carbs) total_carbs,
  SUM(mi.amount / f.unit * f.fat) total_fat 
FROM mealitems mi JOIN food f ON mi.fooditem = f.id WHERE mi.mealday = ? and mi.userid=?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$mealday,$userid]);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);

echo number_format($totals["total_kcal"], 1);

