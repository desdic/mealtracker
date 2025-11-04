<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$id=$_GET['id'];
$pdo->prepare("DELETE FROM food WHERE id=?")->execute([$id]);
header("Location: foods.php");

