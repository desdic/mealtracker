<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$dish_id = (int)($_POST['dish_id'] ?? 0);

if ($dish_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid dish ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT addedby FROM dish WHERE id = ?");
    $stmt->execute([$dish_id]);
    $dish = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dish || $dish['addedby'] != $user_id) {
        throw new Exception('Dish not found or unauthorized');
    }

    $pdo->prepare("DELETE FROM dishitems WHERE dishid = ?")->execute([$dish_id]);

    $pdo->prepare("DELETE FROM food WHERE dishid = ?")->execute([$dish_id]);

    $pdo->prepare("DELETE FROM dish WHERE id = ?")->execute([$dish_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

