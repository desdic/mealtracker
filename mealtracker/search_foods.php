<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require 'db.php';
require_once("logging.php");

$q = trim($_GET['q'] ?? '');
if (!$q) {
    echo json_encode([]);
    exit;
}

try {
	$stmt = $pdo->prepare("SELECT id, title FROM food WHERE title LIKE ? ORDER BY title LIMIT 10");
	$searchTerm = "%$q%";
	$stmt->execute([$searchTerm]);

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	header('Content-Type: application/json');
	echo json_encode($results);
} catch (PDOException $e) {
	log_error("failed to get food: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

