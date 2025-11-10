<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
$dishes = $pdo->query("SELECT d.*, d.addedby as addedbyid, u.username AS addedby FROM dish d JOIN user u ON d.addedby=u.id ORDER BY d.name")->fetchAll(PDO::FETCH_ASSOC);
$userid = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dishes</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
/* Make row clickable */
.dish-clickable {
    cursor: pointer;
}
.dish-clickable:hover {
    background-color: #f8f9fa;
}
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Dishes</span>
    <a href="add_dish.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>
<div class="container">
  <ul class="list-group">
    <?php foreach($dishes as $dish): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">

        <?php if ($userid == $dish['addedbyid']): ?>
      <div class="flex-grow-1 dish-clickable" onclick="window.location='edit_dish.php?id=<?php echo $dish['id']; ?>'">
        <?php echo htmlspecialchars($dish['name']); ?> (<?php echo $dish['kcal']; ?> kcal)
      </div>
        <?php else: ?>
      <div class="flex-grow-1 dish-clickable">
        <?php echo htmlspecialchars($dish['name']); ?> (<?php echo $dish['kcal']; ?> kcal)
      </div>
        <?php endif; ?>

      <div>
        <?php if ($userid == $dish['addedbyid']): ?>
        <a href="delete_dish.php?id=<?php echo $dish['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this dish?')">×</a>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
</body>
</html>

