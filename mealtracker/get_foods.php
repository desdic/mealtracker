<?php

session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

require 'db.php';

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM food WHERE addedby=? ORDER BY title ASC");
$stmt->execute([$uid]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

