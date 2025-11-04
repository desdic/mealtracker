<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$id=$_GET['id'];
$userid = $_SESSION['user_id'];
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Weight</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Edit Weight</h3>
<form method="post">
  <div class="mb-3"><label>Date</label><input type="date" name="created" class="form-control" value="<?php echo $weight['created']; ?>" required></div>
  <div class="mb-3"><label>Weight (kg)</label><input type="number" step="0.01" name="weight" class="form-control" value="<?php echo $weight['weight']; ?>" required></div>
  <button class="btn btn-primary">Save</button>
  <a href="weights.php" class="btn btn-secondary">Back</a>
</form>
</div>
</body>
</html>

