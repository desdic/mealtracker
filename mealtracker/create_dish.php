<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
require 'db.php';

if (isset($_POST['create_dish'])) {
    $name = trim($_POST['dish_name']);
    if (!$name) die('Dish name required.');

    $stmt = $pdo->prepare("INSERT INTO dish (name, addedby, kcal, protein, carbs, fat, amount) VALUES (?, ?, 0, 0, 0, 0, 0)");
    $stmt->execute([$name, $userId]);
    $dishId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO food (addedby, title, kcal, protein, carbs, fat, dishid, unit) VALUES (?, ?, 0, 0, 0, 0, ?, 1)");
    $stmt->execute([$userId, $name, $dishId]);

    header("Location: create_dish.php?dish_id=$dishId");
    exit;
}

$dish = null;
$dishItems = [];
if (isset($_GET['dish_id'])) {
    $dishId = (int)$_GET['dish_id'];
    $stmt = $pdo->prepare("SELECT * FROM dish WHERE id = ?");
    $stmt->execute([$dishId]);
    $dish = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT di.*, f.title
        FROM dishitems di
        JOIN food f ON f.id = di.fooditem
        WHERE di.dishid = ?
        ORDER BY di.id DESC
    ");
    $stmt->execute([$dishId]);
    $dishItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lav en ret</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<script src="assets/jquery-3.7.1.min.js"></script>
<style>
.search-result { cursor: pointer; }
.search-result:hover { background-color: #f0f0f0; }
[contenteditable="true"] { outline: 2px solid #0d6efd33; background-color: #fff; border-radius: 5px; padding: 2px 6px; }
</style>
</head>
<body class="bg-light p-3">

<div class="container">
    <h2 class="text-center mb-4">🍽️ Lav en ret</h2>
    <div>
        <a href="index.php" class="btn btn-primary mb-3">⬅ Tilbage</a>
    </div>

    <?php if (!$dish): ?>
    <!-- Create Dish Form -->
    <form method="post" class="card p-3 shadow-sm mb-3">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="dish_name" class="form-control" autocomplete="off" required>
        </div>
        <button type="submit" name="create_dish" class="btn btn-primary w-100">Lav ret</button>
    </form>
    <?php else: ?>
    <!-- Dish Summary -->
    <div class="card p-3 mb-3 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 id="dish-name" contenteditable="false" class="mb-0"><?= htmlspecialchars($dish['name']) ?></h4>
            <button id="edit-dish-name" class="btn btn-sm btn-outline-secondary">✏️ Rediger</button>
        </div>
        <p class="mb-1"><strong>Kcal:</strong> <span id="dish-kcal"><?= $dish['kcal'] ?></span></p>
        <p class="mb-1"><strong>Protein:</strong> <span id="dish-protein"><?= $dish['protein'] ?></span> g</p>
        <p class="mb-1"><strong>Kulhydrater:</strong> <span id="dish-carbs"><?= $dish['carbs'] ?></span> g</p>
        <p class="mb-1"><strong>Fedt:</strong> <span id="dish-fat"><?= $dish['fat'] ?></span> g</p>
    </div>

    <!-- Add Food Item with Typeahead -->
    <div class="card p-3 mb-3 shadow-sm">
        <label class="form-label">Search Food</label>
        <input type="text" id="food-search" class="form-control" placeholder="Søg efter fødevare" autocomplete="off">
        <ul id="search-results" class="list-group mt-2"></ul>
    </div>

    <!-- Current Items -->
    <div class="card p-3 shadow-sm">
        <h5>Ingredienser</h5>
        <ul id="dish-items" class="list-group">
            <?php foreach ($dishItems as $item): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?= $item['id'] ?>">
                <span><?= htmlspecialchars($item['title']) ?> × </span>
                <input type="number" class="form-control form-control-sm amount-input" style="width:80px; display:inline-block" min="0" step="0.01" value="<?= $item['amount'] ?>">
                <button class="btn btn-sm btn-danger remove-item ms-2">-</button>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<script>
const dishId = <?= $dish['id'] ?? 0 ?>;

// --- SEARCH FOODS WITH TYPEAHEAD ---
$('#food-search').on('input', function() {
    const query = $(this).val().trim();
    const $results = $('#search-results');
    $results.empty();
    if (!query) return;

    $.getJSON('search_foods.php', { q: query })
    .done(function(data) {
        if (!data.length) {
            $results.html('<li class="list-group-item text-muted">No results found</li>');
            return;
        }
        const html = data.map(f => 
            `<li class="list-group-item search-result" data-id="${f.id}">${f.title}</li>`
        ).join('');
        $results.html(html);
    })
    .fail(function() {
        $results.html('<li class="list-group-item text-danger">Error fetching results</li>');
    });
});

// --- ADD FOOD ITEM ON CLICK ---
$(document).on('click', '.search-result', function() {
    const foodId = $(this).data('id');
    const foodTitle = $(this).text();
    const amount = prompt(`Angiv antal gram for "${foodTitle}":`, "100");
    if (!amount || amount <= 0) return;

    $.post('ajax_dish_item.php', { dish_id: dishId, food_id: foodId, amount: amount }, function(res) {
        if (res.success) {
            $('#dish-items').prepend(`
                <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${res.item.id}">
                    ${res.item.title} × 
                    <input type="number" class="form-control form-control-sm amount-input" style="width:80px; display:inline-block" min="0" step="0.01" value="${res.item.amount}">
                    <button class="btn btn-sm btn-danger remove-item ms-2">-</button>
                </li>
            `);
            $('#dish-kcal').text(res.dish.kcal);
            $('#dish-protein').text(res.dish.protein);
            $('#dish-carbs').text(res.dish.carbs);
            $('#dish-fat').text(res.dish.fat);
            $('#search-results').empty();
            $('#food-search').val('');
        } else {
            alert(res.message);
        }
    }, 'json');
});

// --- REMOVE ITEM ---
$(document).on('click', '.remove-item', function() {
    const li = $(this).closest('li');
    const itemId = li.data('id');
    if (!confirm('Slet denne?')) return;

    $.post('ajax_remove_item.php', { item_id: itemId }, function(res) {
        if (res.success) {
            li.remove();
            $('#dish-kcal').text(res.dish.kcal);
            $('#dish-protein').text(res.dish.protein);
            $('#dish-carbs').text(res.dish.carbs);
            $('#dish-fat').text(res.dish.fat);
        } else {
            alert(res.message);
        }
    }, 'json');
});

// --- UPDATE AMOUNT ---
$(document).on('change', '.amount-input', function() {
    const li = $(this).closest('li');
    const itemId = li.data('id');
    const amount = parseFloat($(this).val());
    if (isNaN(amount) || amount <= 0) {
        alert('Amount must be greater than 0');
        return;
    }

    $.post('ajax_update_item.php', { item_id: itemId, amount: amount }, function(res) {
        if (res.success) {
            $('#dish-kcal').text(res.dish.kcal);
            $('#dish-protein').text(res.dish.protein);
            $('#dish-carbs').text(res.dish.carbs);
            $('#dish-fat').text(res.dish.fat);
        } else {
            alert(res.message);
        }
    }, 'json');
});

// --- EDIT DISH NAME (auto-save on blur) ---
$('#edit-dish-name').on('click', function() {
    const $btn = $(this);
    const $name = $('#dish-name');

    if ($name.attr('contenteditable') === 'false') {
        $name.attr('contenteditable', 'true').focus();
        $btn.text('Gemmer automatisk…');
        $btn.prop('disabled', true);
    }
});

$('#dish-name').on('blur', function() {
    const $name = $(this);
    const newName = $name.text().trim();

    if (!newName) {
        alert('Navn kan ikke være tomt.');
        return;
    }

    $.post('ajax_update_dish_name.php', { dish_id: dishId, name: newName }, function(res) {
        if (res.success) {
            $name.attr('contenteditable', 'false');
            $('#edit-dish-name').text('✏️ Rediger').prop('disabled', false);
        } else {
            alert(res.message);
        }
    }, 'json');
});

// --- SAVE ON ENTER ---
$('#dish-name').on('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        $(this).blur(); // triggers blur save
    }
});
</script>

</body>
</html>

