<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$dish_id = (int)($_POST['dish_id'] ?? 0);

if ($dish_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid dish ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM dish WHERE id = ?");
    $stmt->execute([$dish_id]);
    $dish = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dish || $dish['addedby'] != $user_id) {
        throw new Exception("Dish not found or unauthorized");
    }

    $stmt = $pdo->prepare("INSERT INTO dish (name, addedby, kcal, protein, carbs, fat) VALUES (?, ?, ?, ?, ?, ?)");
    $newName = "Copy of " . $dish['name'];
    $stmt->execute([$newName, $user_id, $dish['kcal'], $dish['protein'], $dish['carbs'], $dish['fat']]);
    $newDishId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM food WHERE dishid = ?");
    $stmt->execute([$dish_id]);
    if ($food = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("INSERT INTO food (addedby, title, kcal, protein, carbs, fat, dishid, unit)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            "Copy of " . $food['title'],
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
        $stmt = $pdo->prepare("INSERT INTO dishitems (dishid, fooditem, amount) VALUES (?, ?, ?)");
        $stmt->execute([$newDishId, $item['fooditem'], $item['amount']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

