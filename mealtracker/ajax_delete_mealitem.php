<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    http_response_code(400);
    echo 'Missing id';
    exit;
}

$userid = $_SESSION['user_id'];

$stmt = $pdo->prepare("DELETE FROM mealitems WHERE id = ? AND userid = ?");
$ok = $stmt->execute([$id, $userid]);

if ($ok && $stmt->rowCount() > 0) {
    echo 'OK';
} else {
    http_response_code(404);
    echo 'Not found or not deleted';
}

