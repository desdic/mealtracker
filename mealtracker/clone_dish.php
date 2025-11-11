<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
require 'db.php';
require_once 'user_preferences.php';

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die('missing parameter');
}

$preferences = getUserPreferences($pdo, $user_id);

$dish_id = (int)($_GET['id']);
$dateformat = $preferences['dateformat'];

try {
	$today = date($dateformat);
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM dish WHERE id = ?");
    $stmt->execute([$dish_id]);
    $dish = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("INSERT INTO dish (name, addedby, kcal, protein, carbs, fat, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $newName = $today . " " . $dish['name'];
    $stmt->execute([$newName, $user_id, $dish['kcal'], $dish['protein'], $dish['carbs'], $dish['fat'], $dish['amount']]);
    $newDishId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM food WHERE dishid = ?");
    $stmt->execute([$dish_id]);
    if ($food = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("INSERT INTO food (addedby, title, kcal, protein, carbs, fat, dishid, unit)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $today . " " . $food['title'],
            $food['kcal'],
            $food['protein'],
            $food['carbs'],
            $food['fat'],
            $newDishId,
            $food['unit']
        ]);
    }

    $stmt = $pdo->prepare("SELECT * FROM dishitems WHERE dishid = ?");
    $stmt->execute([$dish_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $stmt = $pdo->prepare("INSERT INTO dishitems (dishid, fooditem, amount, addedby) VALUES (?, ?, ?, ?)");
        $stmt->execute([$newDishId, $item['fooditem'], $item['amount'], $user_id]);
    }

    $pdo->commit();

    header("Location: dishes.php"); exit;

} catch (Exception $e) {
    $pdo->rollBack();
	die("Database error: " . $e->getMessage());
}

