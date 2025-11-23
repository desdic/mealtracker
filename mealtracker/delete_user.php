<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
require_once("logging.php");

try {
	$id=$_GET['id'];
	$pdo->prepare("DELETE FROM user WHERE id=?")->execute([$id]);
	header("Location: users.php");
} catch (PDOException $e) {
	log_error("failed deleting user: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

