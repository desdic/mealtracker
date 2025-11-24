<?php

require_once("logging.php");

function AddUser($pdo) {
	try {
		$hash = password_hash($_POST['checksum'], PASSWORD_DEFAULT);
		$stmt=$pdo->prepare("INSERT INTO user(username,firstname,lastname,checksum,disabled,isadmin) VALUES(?,?,?,?,?,?)");
		$stmt->execute([$_POST['username'],$_POST['firstname'],$_POST['lastname'],$hash,isset($_POST['disabled'])?1:0,isset($_POST['isadmin'])?1:0]);
	} catch (PDOException $e) {
		log_error("failed to add user: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
}

function GetFoods($pdo) {
	try {
		$allFoods = $pdo->query("SELECT * FROM food")->fetchAll(PDO::FETCH_ASSOC);
		return $allFoods;
	} catch (PDOException $e) {
		log_error("failed fetch foods: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
}

function AddDish($pdo, $food_title, $amounts, $dishName, $userid) {
	try {
		$dishAmount = 0;
		$kcal = 0; $protein=0; $carbs=0; $fat=0;

		foreach($food_title as $i => $title){
			$amount = $amounts[$i];
			$stmtFood = $pdo->prepare("SELECT * FROM food WHERE title=? AND unit=100 LIMIT 1");
			$stmtFood->execute([$title]);
			$food = $stmtFood->fetch(PDO::FETCH_ASSOC);
			if($food){
				$kcal += $food['kcal'] * $amount / $food['unit'];
				$protein += $food['protein'] * $amount / $food['unit'];
				$carbs += $food['carbs'] * $amount / $food['unit'];
				$fat += $food['fat'] * $amount / $food['unit'];
			}
		}

		$stmt = $pdo->prepare("INSERT INTO dish(name,addedby,kcal,protein,carbs,fat,amount) VALUES(?,?,?,?,?,?,?)");
		$stmt->execute([$dishName,$userid,$kcal,$protein,$carbs,$fat,$dishAmount]);
		$dishId = $pdo->lastInsertId();

		foreach($_POST['food_title'] as $i => $title){
			$amount = $_POST['amount'][$i];
			$stmtFood = $pdo->prepare("SELECT * FROM food WHERE title=? AND unit=100 LIMIT 1");
			$stmtFood->execute([$title]);
			$food = $stmtFood->fetch(PDO::FETCH_ASSOC);
			if($food){
				$foodId = $food['id'];
				$stmt = $pdo->prepare("INSERT INTO dishitems(dishid,fooditem,amount,addedby) VALUES(?,?,?,?)");
				$stmt->execute([$dishId,$foodId,$amount,$userid]);
			}
		}

		$stmt = $pdo->prepare("INSERT INTO food(addedby,title,kcal,protein,carbs,fat,unit,dishid) VALUES(?,?,?,?,?,?,?,?)");
		$stmt->execute([$userid,$dishName,$kcal,$protein,$carbs,$fat,1,$dishId]);

		return $dishId;
	} catch (PDOException $e) {
		log_error("failed add dish: " . $e->getMessage());
        http_response_code(500);
		die("error");
	}
}

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
