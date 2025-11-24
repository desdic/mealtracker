<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userid = $_SESSION['user_id'];

include('db.php');
require_once 'user_preferences.php';
require_once("logging.php");

$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'] ?? 'light';

if($_SERVER['REQUEST_METHOD']=='POST'){
	try {
		$stmt=$pdo->prepare("INSERT INTO food (addedby,title,kcal,protein,carbs,fat,unit) VALUES (?,?,?,?,?,?,?)");
		$stmt->execute([$userid, $_POST['title'],$_POST['kcal'],$_POST['protein'],$_POST['carbs'],$_POST['fat'],$_POST['unit']]);
	} catch (PDOException $e) {
		log_error("failed to add food: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
    header("Location: foods.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Food</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="<?php echo $theme==='dark'?'bg-dark text-light':'bg-light text-dark'; ?>">
<div class="container mt-3" style="max-width:600px;">
<h3>Add Food</h3>
<form method="post">
  <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
  <div class="mb-3"><label>Kcal</label><input type="number" name="kcal" class="form-control" required></div>
  <div class="mb-3"><label>Fat</label><input type="number" step="0.01" name="fat" class="form-control" required></div>
  <div class="mb-3"><label>Carbs</label><input type="number" step="0.01" name="carbs" class="form-control" required></div>
  <div class="mb-3"><label>Protein</label><input type="number" step="0.01" name="protein" class="form-control" required></div>
  <div class="mb-3"><label>Unit</label><input type="number" step="0.01" name="unit" class="form-control" required></div>
  <button class="btn btn-primary">Add</button>
  <a href="foods.php" class="btn <?php echo $theme==='dark'?'btn-outline-light':'btn-secondary'; ?>">Back</a>
</form>
</div>
</body>
</html>

