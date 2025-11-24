<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once("logging.php");

if (!isset($_GET['mealday'])) {
    die('0');
}

$mealday = intval($_GET['mealday']);
$userid = $_SESSION['user_id'];

try {
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
} catch (PDOException $e) {
	log_error("failed getting total daily: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

echo json_encode([
    'total_kcal'    => (float) number_format($totals["total_kcal"], 1, '.', ''),
    'total_protein' => (float) number_format($totals["total_protein"], 1, '.', ''),
    'total_carbs'   => (float) number_format($totals["total_carbs"], 1, '.', ''),
    'total_fat'     => (float) number_format($totals["total_fat"], 1, '.', '')
]);

