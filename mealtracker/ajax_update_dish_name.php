<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'db.php';
require_once("logging.php");

$userid = $_SESSION['user_id'];

$dishId = $_POST['dish_id'] ?? 0;
$newName = trim($_POST['name'] ?? '');

if (!$dishId || !$newName) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
	$stmt = $pdo->prepare("UPDATE dish SET name = ? WHERE id = ? and addedby = ?");
	$stmt->execute([$newName, $dishId, $userid]);

	$stmt = $pdo->prepare("UPDATE food SET title = ? WHERE dishid = ? and addedby = ?");
	$stmt->execute([$newName, $dishId, $userid]);

} catch (PDOException $e) {
	log_error("failed updating dish name: " . $e->getMessage());
	http_response_code(500);
	die("error");
}


echo json_encode(['success' => true, 'name' => $newName]);

