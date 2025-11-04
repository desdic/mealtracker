<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$username = strtolower($_POST['username']) ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, concat(firstname, ' ', lastname) realname, checksum, isadmin FROM user WHERE username = ? and disabled = false");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['checksum'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['realname'] = $user['realname'];
        $_SESSION['isadmin'] = $user['isadmin'];

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

