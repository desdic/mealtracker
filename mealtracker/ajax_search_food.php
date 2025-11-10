<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$q = isset($_GET['q']) ? $_GET['q'] : '';

$params = ["%$q%"];
$addon = '';
$unit = '';
if (isset($_GET['unit'])) {
	$addon = " and unit=? ";
	$params[] = $_GET['unit'];
}
$params[] = "$q%";

$query = "
	SELECT id,title 
	FROM food 
	WHERE title LIKE ? " . $addon . "
	ORDER BY
	CASE 
	WHEN title like ? THEN 0
	ELSE 1
	END, title LIMIT 10";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);

