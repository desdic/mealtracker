<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

$item_id = $_POST['item_id'] ?? 0;
$amount = $_POST['amount'] ?? 1;

if (!$item_id || $amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM dishitems WHERE id=?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new Exception("Item not found");

    $stmt = $pdo->prepare("UPDATE dishitems SET amount=? WHERE id=?");
    $stmt->execute([$amount, $item_id]);

    $dish_id = $item['dishid'];

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
        $mult = $row['amount'] / $row['unit'];
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

    echo json_encode(['success'=>true,'dish'=>$totals]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

