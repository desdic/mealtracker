<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');

$id=$_GET['id'];
$userid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM dish WHERE id=? and addedby=?");
$stmt->execute([$id,$userid]);
$dishid = $stmt->fetchColumn();

if ($dishid != $id) {
	die("you don't own this dish");
}

$pdo->prepare("DELETE FROM dishitems WHERE dishid=? AND addedby=?")->execute([$id,$userid]);
$pdo->prepare("DELETE FROM dish WHERE id=? AND addedby=?")->execute([$id,$userid]);
header("Location: dishes.php");

