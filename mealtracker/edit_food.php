<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$id=$_GET['id'];
$userid = $_SESSION['user_id'];
$stmt=$pdo->prepare("SELECT * FROM food WHERE id=?");
$stmt->execute([$id]);
$food=$stmt->fetch(PDO::FETCH_ASSOC);

if ($userid!=$food['addedby']) {
	die("you didn't add this dish");
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    $stmt=$pdo->prepare("UPDATE food SET title=?,kcal=?,protein=?,carbs=?,fat=?,unit=? WHERE id=?");
    $stmt->execute([$_POST['title'],$_POST['kcal'],$_POST['protein'],$_POST['carbs'],$_POST['fat'],$_POST['unit'],$id]);
    header("Location: foods.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Food</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Edit Food</h3>
<form method="post">
  <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($food['title']); ?>" required></div>
  <div class="mb-3"><label>Kcal</label><input type="number" name="kcal" class="form-control" value="<?php echo $food['kcal']; ?>" required></div>
  <div class="mb-3"><label>Protein</label><input type="number" step="0.01" name="protein" class="form-control" value="<?php echo $food['protein']; ?>" required></div>
  <div class="mb-3"><label>Carbs</label><input type="number" step="0.01" name="carbs" class="form-control" value="<?php echo $food['carbs']; ?>" required></div>
  <div class="mb-3"><label>Fat</label><input type="number" step="0.01" name="fat" class="form-control" value="<?php echo $food['fat']; ?>" required></div>
  <div class="mb-3"><label>Unit</label><input type="number" step="0.01" name="unit" class="form-control" value="<?php echo $food['unit']; ?>" required></div>
  <button class="btn btn-primary">Save</button>
  <a href="foods.php" class="btn btn-secondary">Back</a>
</form>
</div>
</body>
</html>

