<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');

$userid = $_SESSION['user_id'];

$allFoods = GetFoods($pdo);

if($_SERVER['REQUEST_METHOD']=='POST'){
    $dishName = $_POST['name'];
	$dishId = AddDish($pdo, $_POST['food_title'], $_POST['amount'], $dishName, $userid);

    header("Location: edit_dish.php?id=".$dishId); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Dish</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.autocomplete-suggestions {
    border: 1px solid #ddd;
    max-height: 150px;
    overflow-y: auto;
    position: absolute;
    background: white;
    z-index: 1000;
    width: 100%;
}
.autocomplete-suggestion {
    padding: 5px 10px;
    cursor: pointer;
}
.autocomplete-suggestion:hover {
    background: #f0f0f0;
}
</style>
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Add Dish</h3>

<form method="post" id="dishForm">
  <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>

  <h5>Ingredients</h5>
  <div id="ingredients" style="position: relative;"></div>

  <div class="mb-2">
    <button type="button" class="btn btn-secondary" onclick="addIngredient()">+ Add Ingredient</button>
  </div>

  <br>
  <button class="btn btn-primary">Add Dish</button>
  <a href="dishes.php" class="btn btn-secondary">Back</a>
</form>
</div>

<script>
let allFoodsCache = {};
<?php foreach($allFoods as $f): ?>
allFoodsCache["<?php echo addslashes($f['title']); ?>"] = {
    id: <?php echo $f['id']; ?>,
    kcal: <?php echo $f['kcal']; ?>,
    unit: <?php echo $f['unit']; ?>
};
<?php endforeach; ?>

function addIngredient(value='') {
    const div = document.createElement('div');
    div.className='d-flex mb-2 ingredient-row position-relative';
    div.innerHTML = `
        <input type="text" name="food_title[]" class="form-control me-2 food-search" placeholder="Search food..." value="${value}" required autocomplete="off">
        <input type="number" step="0.01" name="amount[]" class="form-control me-2 amount-input" value="100" required>
        <button type="button" class="btn btn-danger">×</button>
    `;
    const container = document.getElementById('ingredients');
    container.appendChild(div);

    const searchInput = div.querySelector('.food-search');
    const amountInput = div.querySelector('.amount-input');
    const removeBtn = div.querySelector('.btn-danger');

    attachFoodSearch(searchInput);
    removeBtn.addEventListener('click', ()=> div.remove());
    amountInput.addEventListener('focus', function() { this.select(); });
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
                if(suggestionsDiv) { suggestionsDiv.remove(); suggestionsDiv=null; }
                suggestionsDiv=document.createElement('div');
                suggestionsDiv.className='autocomplete-suggestions';
                data.forEach(food=>{
                    allFoodsCache[food.title]={...food,unit:100};
                    const divOption=document.createElement('div');
                    divOption.className='autocomplete-suggestion';
                    divOption.textContent=food.title;
                    divOption.addEventListener('click', ()=>{
                        self.value=food.title;
                        if(suggestionsDiv){ suggestionsDiv.remove(); suggestionsDiv=null; }
                        input.parentNode.querySelector('.amount-input').focus();
                    });
                    suggestionsDiv.appendChild(divOption);
                });
                self.parentNode.appendChild(suggestionsDiv);
            });
        },200);
    });

    document.addEventListener('click', e=>{
        if(suggestionsDiv && !suggestionsDiv.contains(e.target) && e.target !== input){
            suggestionsDiv.remove();
            suggestionsDiv=null;
        }
    });
}

// initialize with one ingredient
addIngredient();
</script>
</body>
</html>

