<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
require_once("logging.php");

try {
	$id=$_GET['id'];
	$pdo->prepare("DELETE FROM food WHERE id=?")->execute([$id]);
	header("Location: foods.php");
} catch (PDOException $e) {
	log_error("failed deleting food: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

