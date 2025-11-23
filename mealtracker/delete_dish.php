<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once("logging.php");

$id=$_GET['id'];
$userid = $_SESSION['user_id'];

try {
	$pdo->prepare("DELETE FROM dish WHERE id=? AND addedby=?")->execute([$id,$userid]);
	header("Location: dishes.php");
} catch (PDOException $e) {
	log_error("failed deleting dish: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

