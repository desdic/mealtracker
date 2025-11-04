<?php

session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

require 'db.php';

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM food WHERE id=?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>

