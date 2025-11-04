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

try {
    $userId = $user_id;

    $mealType = $_POST['meal_id'] ?? $_POST['mealtype'] ?? null;
    $foodId   = $_POST['food_id'] ?? $_POST['foodId'] ?? null;
    $amount   = $_POST['amount'] ?? null;
    $date     = $_POST['date'] ?? date('Y-m-d');

    if (!$mealType)  throw new Exception('Missing meal_id (mealtype).');
    if (!$foodId)    throw new Exception('Missing food_id.');
    if ($amount === null || $amount === '') throw new Exception('Missing amount.');

    $mealType = (int)$mealType;
    $foodId   = (int)$foodId;
    $amount   = (float)$amount;
    $date     = date('Y-m-d', strtotime($date)); // normalize date

    if ($mealType <= 0) throw new Exception('Invalid meal_id.');
    if ($foodId <= 0)   throw new Exception('Invalid food_id.');
    if ($amount <= 0)   throw new Exception('Amount must be > 0.');

    $stmt = $pdo->prepare("SELECT id FROM mealday WHERE userid = ? AND date = ?");
    $stmt->execute([$userId, $date]);
    $mealday = $stmt->fetchColumn();

    if (!$mealday) {
        $ins = $pdo->prepare("INSERT INTO mealday (userid, date) VALUES (?, ?)");
        $ins->execute([$userId, $date]);
        $mealday = $pdo->lastInsertId();
        if (!$mealday) throw new Exception('Failed to create mealday.');
    }

    $stmt = $pdo->prepare("SELECT id FROM food WHERE id = ? LIMIT 1");
    $stmt->execute([$foodId]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Food item not found (id=' . $foodId . ').');
    }

    $stmt = $pdo->prepare("SELECT id FROM mealtypes WHERE id = ? LIMIT 1");
    $stmt->execute([$mealType]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Meal type not found (id=' . $mealType . ').');
    }

    $ins = $pdo->prepare("INSERT INTO mealitems (amount, mealday, fooditem, mealtype, userid) VALUES (?, ?, ?, ?, ?)");
    $ok = $ins->execute([$amount, $mealday, $foodId, $mealType, $userId]);

    if ($ok) {
        echo json_encode(['success' => true, 'mealday' => (int)$mealday]);
        exit;
    } else {
        $err = $ins->errorInfo();
        throw new Exception('Insert failed: ' . ($err[2] ?? 'unknown'));
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
