<?php
session_start();

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

require 'db.php';

$id = $_POST['id'];
$title = $_POST['title'];
$kcal = $_POST['kcal'];
$protein = $_POST['protein'];
$carbs = $_POST['carbs'];
$fat = $_POST['fat'];
$unit = $_POST['unit'];

$stmt = $pdo->prepare("UPDATE food SET title=?, kcal=?, protein=?, carbs=?, fat=?, unit=? WHERE id=?");
$ok = $stmt->execute([$title, $kcal, $protein, $carbs, $fat, $unit, $id]);
echo json_encode(['success' => $ok]);
?>

