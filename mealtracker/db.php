<?php

require_once("logging.php");

function GetMealType($pdo, $mealtypeId) {
	try {
		$stmt = $pdo->prepare("SELECT * FROM mealtypes WHERE id=?");
		$stmt->execute([$mealtypeId]);
		$mealtype = $stmt->fetch(PDO::FETCH_ASSOC);
		return $mealtype;
	} catch (PDOException $e) {
		log_error("failed to fetch mealtype: " . $e->getMessage());
        http_response_code(500);
		die("error");
	}
}

function GetMealItems($pdo, $mealdayId, $mealtypeId, $userid) {
	try {
		$stmt = $pdo->prepare("SELECT mi.*, f.title, f.kcal, f.unit, f.protein, f.carbs, f.fat
								FROM mealitems mi
								JOIN food f ON mi.fooditem=f.id
								WHERE mi.mealday=? AND mi.mealtype=? and mi.userid=?");
		$stmt->execute([$mealdayId, $mealtypeId,$userid]);
		$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $items;
	} catch (PDOException $e) {
		log_error("failed to fetch meal items: " . $e->getMessage());
        http_response_code(500);
		die("error");
	}
}

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
