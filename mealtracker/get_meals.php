<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

$stmt = $pdo->query("SELECT id, name FROM mealtypes ORDER BY rank ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

