<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
	ob_end_clean(); 
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$mealday   = isset($_POST['mealday']) ? intval($_POST['mealday']) : null;
$mealtype  = isset($_POST['mealtype']) ? intval($_POST['mealtype']) : null;
$fooditem  = isset($_POST['fooditem']) ? intval($_POST['fooditem']) : null;
$amount    = isset($_POST['amount']) ? floatval($_POST['amount']) : null;

if(!$mealday || !$mealtype || !$fooditem || !$amount){
	ob_end_clean(); 
    http_response_code(400);
    echo "Missing required fields";
    exit;
}

$userid = $_SESSION['user_id'];

// TOOD
$stmt = $pdo->prepare("INSERT INTO mealitems (amount, mealday, fooditem, mealtype, userid) VALUES (?,?,?,?,?)");
if (!$stmt->execute([$amount, $mealday, $fooditem, $mealtype, $userid])) {
	ob_end_clean(); 
    http_response_code(500);
    echo "Database insert failed";
    exit;
}

$lastId = $pdo->lastInsertId();

ob_end_clean(); 

echo $lastId;
