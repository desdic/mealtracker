<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$id=$_GET['id'];
$userid = $_SESSION['user_id'];
$pdo->prepare("DELETE FROM weighttrack WHERE id=? AND userid=?")->execute([$id,$userid]);
header("Location: weights.php");

