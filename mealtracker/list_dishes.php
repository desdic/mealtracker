<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM dish WHERE addedby = ? ORDER BY created DESC");
    $stmt->execute([$user_id]);
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Dishes</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<script src="assets/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">

    <h1 class="mb-4">Retter</h1>

	<div>
	<a href="index.php" class="btn btn-primary mb-3">⬅ Tilbage</a>
	<a href="create_dish.php" class="btn btn-primary mb-3">Lav en ny ret</a>
	</div>

    <?php if (count($dishes) === 0): ?>
        <div class="alert alert-info">Ingen retter. Lav en!</div>
    <?php else: ?>
        <div class="row row-cols-1 g-3">
            <?php foreach ($dishes as $dish): ?>
                <div class="col" id="dish-<?= $dish['id'] ?>">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                            <div class="me-3">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($dish['name']) ?></h5>
                                <p class="card-text mb-0">
                                    <small class="text-muted">
                                        kcal: <?= $dish['kcal'] ?> | protein: <?= $dish['protein'] ?>g | carbs: <?= $dish['carbs'] ?>g | fat: <?= $dish['fat'] ?>g
                                    </small>
                                </p>
                            </div>
                            <div class="mt-2 mt-sm-0">
                                <a href="create_dish.php?dish_id=<?= $dish['id'] ?>" class="btn btn-success btn-sm me-1">Rediger</a>
                                <button class="btn btn-secondary btn-sm me-1 clone-dish" data-id="<?= $dish['id'] ?>">Kopier</button>
                                <button class="btn btn-danger btn-sm delete-dish" data-id="<?= $dish['id'] ?>">Slet</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<script>
$(document).on('click', '.delete-dish', function() {
    const dishId = $(this).data('id');
    if (!confirm('Er du sikker på at du vil slette denne ret?')) return;

    $.post('ajax_delete_dish.php', { dish_id: dishId }, function(res) {
        if (res.success) {
            $('#dish-' + dishId).fadeOut(300, function() { $(this).remove(); });
        } else {
            alert(res.message || 'Failed to delete dish');
        }
    }, 'json');
});

// --- CLONE DISH ---
$(document).on('click', '.clone-dish', function() {
    const dishId = $(this).data('id');
    if (!confirm('Lav en kopi af denne ret?')) return;

    $.post('ajax_clone_dish.php', { dish_id: dishId }, function(res) {
        if (res.success) {
            alert('Ret kopieret!');
            location.reload();
        } else {
            alert(res.message || 'Kopiering fejlede af ret');
        }
    }, 'json');
});
</script>
</body>
</html>

