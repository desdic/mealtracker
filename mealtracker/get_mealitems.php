<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];

require 'db.php';

$date = $_GET['date'] ?? date('Y-m-d');

try {
    $sql = "
    SELECT 
		i.id,
        i.amount,
        i.mealtype,
        f.title AS foodname,
	f.unit,
	f.kcal kcalperunit,
	ROUND(i.amount / f.unit * f.kcal, 1) AS kcal,
	ROUND(i.amount / f.unit * f.protein, 1) AS protein,
	ROUND(i.amount / f.unit * f.carbs, 1) AS carbs,
	ROUND(i.amount / f.unit * f.fat, 1) AS fat

    FROM mealitems i
    JOIN mealday d ON d.id = i.mealday
    JOIN food f ON f.id = i.fooditem
    WHERE d.userid = ? AND d.date = ?
    ORDER BY i.mealtype, i.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $date]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = array_sum(array_column($items, 'kcal'));

    echo json_encode([
        'total_kcal' => round($total, 1),
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['total_kcal' => 0, 'items' => []]);
}
