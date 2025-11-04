<?php
try {
	$pdo = new PDO('mysql:host=lamp_db;dbname=mealtracker;charset=utf8mb4', 'mealtracker', 'mealtracker');
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if (strpos($_SERVER['REQUEST_URI'], 'ajax_') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["error" => "Database connection failed."]);
        exit;
    } else {
	    die("Database connection failed: " . $e->getMessage());
    }
}
