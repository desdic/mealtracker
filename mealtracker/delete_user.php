<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
$id=$_GET['id'];
$pdo->prepare("DELETE FROM user WHERE id=?")->execute([$id]);
header("Location: users.php");

