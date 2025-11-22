<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$dish_id = $_POST['dish_id'] ?? 0;
$food_id = $_POST['food_id'] ?? 0;
$amount = $_POST['amount'] ?? 1;

if (!$dish_id || !$food_id || $amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

require 'db.php';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM food WHERE id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food) throw new Exception("Food not found");

    $stmt = $pdo->prepare("INSERT INTO dishitems (dishid, fooditem, amount) VALUES (?, ?, ?)");
    $stmt->execute([$dish_id, $food_id, $amount]);
    $item_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        SELECT di.amount, f.kcal, f.protein, f.carbs, f.fat, f.unit
        FROM dishitems di
        JOIN food f ON f.id = di.fooditem
        WHERE di.dishid = ?
    ");
    $stmt->execute([$dish_id]);
    $totals = ['kcal'=>0,'protein'=>0,'carbs'=>0,'fat'=>0];
	$total_amount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$total_amount += $row['amount'];
        $mult = $row['amount'] / $row['unit']; // take unit into account
        $totals['kcal'] += $row['kcal'] * $mult;
        $totals['protein'] += $row['protein'] * $mult;
        $totals['carbs'] += $row['carbs'] * $mult;
        $totals['fat'] += $row['fat'] * $mult;
    }

    $stmt = $pdo->prepare("UPDATE dish SET kcal=?, protein=?, carbs=?, fat=?, amount=? WHERE id=?");
    $stmt->execute([$totals['kcal'], $totals['protein'], $totals['carbs'], $totals['fat'], $total_amount, $dish_id]);

    $stmt = $pdo->prepare("UPDATE food SET kcal=?, protein=?, carbs=?, fat=?, unit=? WHERE dishid=?");
    $stmt->execute([$totals['kcal'], $totals['protein'], $totals['carbs'], $totals['fat'], $total_amount, $dish_id]);

    $pdo->commit();

    echo json_encode([
        'success'=>true,
        'item'=>['id'=>$item_id,'title'=>$food['title'],'amount'=>$amount],
        'dish'=>$totals
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

