<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
require_once("logging.php");

$id = $_POST['id'] ?? null;
$amount = $_POST['amount'] ?? null;

if(!$id || !$amount){
    echo json_encode(['error'=>'Missing data']);
    exit;
}

try {
	$stmt = $pdo->prepare("UPDATE mealitems SET amount=? WHERE id=?");
	$stmt->execute([$amount,$id]);

	$stmt = $pdo->prepare("SELECT f.kcal, f.unit FROM mealitems mi JOIN food f ON mi.fooditem=f.id WHERE mi.id=?");
	$stmt->execute([$id]);
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	log_error("failed updating mealitem: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

echo json_encode($data);

