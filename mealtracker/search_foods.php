<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require 'db.php';

$q = trim($_GET['q'] ?? '');
if (!$q) {
    echo json_encode([]);
    exit;
}

// Prevent SQL injection using LIKE with prepared statement
$stmt = $pdo->prepare("SELECT id, title FROM food WHERE title LIKE ? ORDER BY title LIMIT 10");
$searchTerm = "%$q%";
$stmt->execute([$searchTerm]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);

