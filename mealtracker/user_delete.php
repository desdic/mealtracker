<?php
session_start();
require 'db.php';
if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM user WHERE id=?");
    $stmt->execute([$id]);
}

header("Location: users.php");
exit;

