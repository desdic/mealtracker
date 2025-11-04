<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$userid = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $stmt=$pdo->prepare("INSERT INTO weighttrack(created,weight,userid) VALUES(?,?,?)");
    $stmt->execute([$_POST['created'],$_POST['weight'],$userid]);
    header("Location: weights.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Weight</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Add Weight</h3>
<form method="post">
  <div class="mb-3"><label>Date</label><input type="date" name="created" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
  <div class="mb-3"><label>Weight (kg)</label><input type="number" step="0.01" name="weight" class="form-control" required></div>
  <button class="btn btn-primary">Add</button>
  <a href="weights.php" class="btn btn-secondary">Back</a>
</form>
</div>
</body>
</html>

