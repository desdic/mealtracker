<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
include('db.php');
$userid = $_SESSION['user_id'];

$foods = $pdo->query("SELECT * FROM food ORDER BY dishid, title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Foods</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
    <div class="container-fluid">
        <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
        <span class="navbar-brand mx-auto">Foods</span>
        <a href="add_food.php" class="btn btn-primary">+ Add</a>
    </div>
</nav>
<div class="container">
    <ul class="list-group">
        <?php foreach($foods as $f): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start">
            <span class="me-2 flex-grow-1" style="min-width: 0;">
                <strong class="d-block text-truncate"><?php echo htmlspecialchars($f['title']); ?></strong>
                <small class="text-muted text-truncate d-block">
                    (<?php echo $f['kcal']; ?> kcal,
                    P: <?php echo $f['protein']; ?>g,
                    C: <?php echo $f['carbs']; ?>g,
                    F: <?php echo $f['fat']; ?>g
                    per <?php echo $f['unit']; ?>)
                </small>
            </span>
            <div class="d-flex flex-shrink-0 gap-1">
                <?php if (is_null($f['dishid'])): ?>
                    <?php if ($userid == $f['addedby']): ?>
                <a href="edit_food.php?id=<?php echo $f['id']; ?>" class="btn btn-sm">✏️</a>
                <a href="delete_food.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this food?')">×</a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($userid == $f['addedby']): ?>
                <a href="edit_dish.php?id=<?php echo $f['dishid']; ?>" class="btn btn-sm">✏️🍽️</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>
