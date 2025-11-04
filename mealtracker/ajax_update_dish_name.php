<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

$dishId = $_POST['dish_id'] ?? 0;
$newName = trim($_POST['name'] ?? '');

if (!$dishId || !$newName) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$stmt = $pdo->prepare("SELECT addedby FROM dish WHERE id = ?");
$stmt->execute([$dishId]);
$dish = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dish || $dish['addedby'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("UPDATE dish SET name = ? WHERE id = ?");
$stmt->execute([$newName, $dishId]);

$stmt = $pdo->prepare("UPDATE food SET title = ? WHERE dishid = ?");
$stmt->execute([$newName, $dishId]);

echo json_encode(['success' => true, 'name' => $newName]);

