<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];

$id=$_GET['id'];
$stmt=$pdo->prepare("SELECT * FROM weighttrack WHERE id=? AND userid=?");
$stmt->execute([$id,$userid]);
$weight=$stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']=='POST'){
    $stmt=$pdo->prepare("UPDATE weighttrack SET created=?,weight=? WHERE id=? AND userid=?");
    $stmt->execute([$_POST['created'],$_POST['weight'],$id,$userid]);
    header("Location: weights.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Weight</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: <?php echo $theme==='dark'?'#121212':'#f8f9fa'; ?>;
    color: <?php echo $theme==='dark'?'#fff':'#000'; ?>;
}
.card, .form-control {
    <?php if($theme==='dark'): ?>
    background-color: #343a40;
    color: #fff;
    border-color: #495057;
    <?php endif; ?>
}
.btn-close {
    <?php if($theme==='dark'): ?>filter: invert(1);<?php endif; ?>
}
</style>
</head>
<body class="<?php echo $theme==='dark'?'bg-dark text-light':'bg-light text-dark'; ?>">

<div class="container mt-3">
<div class="card p-3 <?php echo $theme==='dark'?'bg-secondary text-light':''; ?>">
<h3>Edit Weight</h3>
<form method="post">
  <div class="mb-3">
    <label>Date</label>
    <input type="date" name="created" class="form-control <?php echo $theme==='dark'?'bg-dark text-light border-light':''; ?>" value="<?php echo $weight['created']; ?>" required>
  </div>
  <div class="mb-3">
    <label>Weight (kg)</label>
    <input type="number" step="0.01" name="weight" class="form-control <?php echo $theme==='dark'?'bg-dark text-light border-light':''; ?>" value="<?php echo $weight['weight']; ?>" required>
  </div>
  <button class="btn btn-primary">Save</button>
  <a href="weights.php" class="btn btn-secondary">Back</a>
</form>
</div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>

