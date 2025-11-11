<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
// ADDED for theming
require_once 'user_preferences.php';

$dishId = $_GET['id'];
$userid = $_SESSION['user_id'];
// ADDED for theming
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'] ?? 'light';

$stmt = $pdo->prepare("SELECT id FROM dish WHERE id=? and addedby=?");
$stmt->execute([$dishId,$userid]);
$dishid = $stmt->fetchColumn();

if ($dishid != $dishId) {
	die("you don't own this dish");
}

$stmt = $pdo->prepare("SELECT * FROM dish WHERE id=?");
$stmt->execute([$dishId]);
$dish = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT di.*, f.title, f.kcal, f.unit FROM dishitems di JOIN food f ON di.fooditem=f.id WHERE di.dishid=?");
$stmt->execute([$dishId]);
$dishItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']=='POST'){
    $dishName = $_POST['name'];
    $kcal = 0; $protein=0; $carbs=0; $fat=0;
    $totalAmount = 0;

    $pdo->prepare("DELETE FROM dishitems WHERE dishid=? AND addedby=?")->execute([$dishId,$userid]);

    $titles = $_POST['food_title'] ?? [];
    $amounts = $_POST['amount'] ?? [];

    foreach($titles as $i => $title){
        $amount = $amounts[$i] ?? 0;
        $totalAmount += $amount;
        $stmtFood = $pdo->prepare("SELECT * FROM food WHERE title=? AND unit=100 LIMIT 1");
        $stmtFood->execute([$title]);
        $food = $stmtFood->fetch(PDO::FETCH_ASSOC);
        if($food){
            $foodId = $food['id'];
            $kcal += $food['kcal'] * $amount / $food['unit'];
            $protein += $food['protein'] * $amount / $food['unit'];
            $carbs += $food['carbs'] * $amount / $food['unit'];
            $fat += $food['fat'] * $amount / $food['unit'];

            $stmtInsert = $pdo->prepare("INSERT INTO dishitems(dishid,fooditem,amount,addedby) VALUES(?,?,?,?)");
            $stmtInsert->execute([$dishId,$foodId,$amount,$userid]);
        }
    }

    $stmt = $pdo->prepare("UPDATE dish SET name=?,kcal=?,protein=?,carbs=?,fat=?,amount=? WHERE id=?");
    $stmt->execute([$dishName,$kcal,$protein,$carbs,$fat,$totalAmount,$dishId]);

    $stmt = $pdo->prepare("UPDATE food SET title=?,kcal=?,protein=?,carbs=?,fat=?,unit=? WHERE dishid=?");
    $stmt->execute([$dishName,$kcal,$protein,$carbs,$fat,$totalAmount, $dishId]);

    header("Location: edit_dish.php?id=".$dishId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Dish</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
/* Adjusting styles for theming */
.autocomplete-suggestions {
    /* Use CSS variables or theme-based values for border/background/text */
    border: 1px solid var(--bs-border-color);
    max-height: 150px;
    overflow-y: auto;
    position: absolute;
    /* Use Bootstrap utility for background/text color based on theme */
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    z-index: 1000;
    width: 100%;
    /* Added to prevent it from bleeding out of container */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175); 
}
.autocomplete-suggestion {
    padding: 5px 10px;
    cursor: pointer;
}
.autocomplete-suggestion:hover {
    /* Use Bootstrap primary color for hover */
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary-text-emphasis);
}
</style>
</head>
<body class="<?php echo $theme==='dark'?'bg-dark text-light':'bg-light text-dark'; ?>">
<div class="container mt-3">

<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
  <div class="container-fluid">
    <a href="dishes.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Edit dish</span>
    <a href="index.php" class="btn btn-secondary">&laquo; Home</a>
  </div>
</nav>

<div class="mb-3">
    <strong>Total kcal: <span id="totalKcal">0.0</span></strong>
</div>

<form method="post" id="dishForm">
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($dish['name']); ?>" required autocomplete="off">
    </div>

    <h5>Ingredients</h5>
    <div class="mb-2">
        <button type="button" class="btn btn-secondary" onclick="addIngredient()">+ Add Ingredient</button>
    </div>

    <div id="ingredients" style="position: relative;">
        <?php foreach($dishItems as $di): ?>
        <div class="d-flex mb-2 ingredient-row">
            <input type="text" name="food_title[]" class="form-control me-2 food-search" placeholder="Search food..." value="<?php echo htmlspecialchars($di['title']); ?>" required autocomplete="off">
            <input type="number" step="0.01" name="amount[]" class="form-control me-2 amount-input" value="<?php echo $di['amount']; ?>" required>
            <button type="button" class="btn btn-danger" onclick="this.parentNode.remove(); updateTotal();">×</button>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary">Save Dish</button>
    </div>
</form>
</div>

<script>
// preload existing foods into cache
let allFoodsCache = {};
<?php foreach($dishItems as $di): ?>
allFoodsCache["<?php echo addslashes($di['title']); ?>"] = {
    id: <?php echo $di['fooditem']; ?>,
    kcal: <?php echo $di['kcal']; ?>,
    unit: <?php echo $di['unit']; ?>
};
<?php endforeach; ?>

function addIngredient(value='') {
    const div = document.createElement('div');
    div.className='d-flex mb-2 ingredient-row';
    div.innerHTML = `
      <input type="text" name="food_title[]" class="form-control me-2 food-search" placeholder="Search food..." value="${value}" required autocomplete="off">
      <input type="number" step="0.01" name="amount[]" class="form-control me-2 amount-input" value="100" required>
      <button type="button" class="btn btn-danger">×</button>
    `;
    const container = document.getElementById('ingredients');
    container.prepend(div);

    const searchInput = div.querySelector('.food-search');
    const amountInput = div.querySelector('.amount-input');
    const removeBtn = div.querySelector('.btn-danger');

    attachFoodSearch(searchInput);
    amountInput.addEventListener('input', updateTotal);
    removeBtn.addEventListener('click', ()=>{ div.remove(); updateTotal(); });

    // select default 100 so typing overwrites
    amountInput.addEventListener('focus', function() { this.select(); });

    updateTotal();
}

function updateTotal(){
    let total=0;
    document.querySelectorAll('.ingredient-row').forEach(row=>{
        const search = row.querySelector('.food-search');
        const amount = parseFloat(row.querySelector('.amount-input').value) || 0;
        const foodData = allFoodsCache[search.value];
        if(foodData){
            total += amount / parseFloat(foodData.unit) * parseFloat(foodData.kcal);
        }
    });
    document.getElementById('totalKcal').textContent = total.toFixed(1);
}

function attachFoodSearch(input){
    let suggestionsDiv = null;
    let timeout = null;

    input.addEventListener('input', function(){
        clearTimeout(timeout);
        const query = this.value.trim();
        if(!query) return;
        const self = this;
        timeout = setTimeout(()=>{
            fetch(`ajax_search_food.php?q=${encodeURIComponent(query)}&unit=100`)
            .then(res=>res.json())
            .then(data=>{
                if(suggestionsDiv) {
                    suggestionsDiv.remove();
                    suggestionsDiv = null;
                }
                suggestionsDiv = document.createElement('div');
                suggestionsDiv.className='autocomplete-suggestions';
                data.forEach(food=>{
                    allFoodsCache[food.title] = {...food, unit:100};
                    const divOption = document.createElement('div');
                    divOption.className='autocomplete-suggestion';
                    divOption.textContent = food.title;
                    divOption.addEventListener('click', ()=>{
                        self.value = food.title;
                        if(suggestionsDiv){
                            suggestionsDiv.remove();
                            suggestionsDiv = null;
                        }
                        const amountInput = self.parentNode.querySelector('.amount-input');
                        if(amountInput) amountInput.focus();
                        updateTotal();
                    });
                    suggestionsDiv.appendChild(divOption);
                });
                self.parentNode.appendChild(suggestionsDiv);
            });
        }, 200);
    });

    document.addEventListener('click', e=>{
        if(suggestionsDiv && !suggestionsDiv.contains(e.target) && e.target !== input){
            suggestionsDiv.remove();
            suggestionsDiv = null;
        }
    });
}

// Attach to existing rows
document.querySelectorAll('.food-search').forEach(input => attachFoodSearch(input));
document.querySelectorAll('.amount-input').forEach(input => input.addEventListener('input', updateTotal));

// Initial calculation
updateTotal();
</script>
</body>
</html>
