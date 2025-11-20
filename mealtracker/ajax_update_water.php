<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['user_id'])) {
	ob_end_clean(); 
    http_response_code(401);
    echo "Unauthorized"; 
    exit;
}

include('db.php');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
	ob_end_clean(); 
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$mealdayId = isset($_POST['mealdayid']) ? intval($_POST['mealdayid']) : null;
$cups      = isset($_POST['cups']) ? intval($_POST['cups']) : null;

if(!$mealdayId || $cups === null){
	ob_end_clean(); 
    http_response_code(400);
    echo "Missing required fields (mealdayid or cups)";
    exit;
}

// Sanitize
$cups = min(max(0, $cups), 8);

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM water_intake WHERE mealdayid = ? and userid = ?");
    $stmt->execute([$mealdayId,$userId]);
    $existingWater = $stmt->fetch();

    if ($existingWater) {
        $stmt = $pdo->prepare("UPDATE water_intake SET cups = ? WHERE mealdayid = ? AND userid = ?");
    } else {
        $stmt = $pdo->prepare("INSERT INTO water_intake (cups, mealdayid, userid) VALUES (?, ?, ?)");
    }

    if (!$stmt->execute([$cups, $mealdayId, $userId])) {
        ob_end_clean();
        http_response_code(500);
        echo "Database operation failed";
        exit;
    }

    ob_end_clean(); 
    echo 'OK';

} catch (PDOException $e) {
    ob_end_clean(); 
    http_response_code(500);
    // Note: Logging $e->getMessage() instead of echoing is safer in production.
    echo "Database error";
    exit;
}
?>
