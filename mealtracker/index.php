<?php
include('db.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$userid = $_SESSION['user_id'];

$mealtypes = $pdo->query("SELECT * FROM mealtypes ORDER BY rank")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM mealday WHERE userid=? AND date=?");
$stmt->execute([$userid,$date]);
$mealday = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$mealday){
    $pdo->prepare("INSERT INTO mealday(userid,date) VALUES(?,?)")->execute([$userid,$date]);
    $stmt = $pdo->prepare("SELECT * FROM mealday WHERE userid=? AND date=?");
    $stmt->execute([$userid,$date]);
    $mealday = $stmt->fetch(PDO::FETCH_ASSOC);
}
$mealdayId = $mealday['id'];

function renderMealItemRow($item) {
    $amount = floatval($item['amount']);
    $kcal = floatval($item['kcal']);
    $unit = floatval($item['unit']);
    if($unit <= 0) $unit = 1;
    $itemKcal = ($unit > 0) ? ($amount / $unit) * $kcal : 0;

    return '<div class="meal-item-row" id="mealitem-'.$item['id'].'" '
        .'data-kcal="'.htmlspecialchars($kcal).'" '
        .'data-unit="'.htmlspecialchars($unit).'" '
        .'data-foodid="'.htmlspecialchars($item['fooditem']).'" '
        .'data-amount="'.htmlspecialchars($amount).'">'
        .'<div class="d-flex justify-content-between align-items-center">'
            .'<div class="meal-item-food flex-grow-1" onclick="editMealItem('.$item['id'].')">'
                .htmlspecialchars($item['title']).' × '.$amount
            .'</div>'
            .'<button class="btn btn-sm btn-danger" onclick="deleteMealItem('.$item['id'].')">×</button>'
        .'</div>'
        .'<div class="meal-item-kcal">('.number_format($itemKcal,1).' kcal)</div>'
    .'</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meal Tracker</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.autocomplete-list { z-index: 1100; max-height: 200px; overflow-y: auto; }
.meal-item-row:nth-child(even){ background-color:#f8f9fa; }
.meal-item-row:nth-child(odd){ background-color:#ffffff; }
.meal-item-row{ padding:0.5rem; border-radius:0.25rem; margin-bottom:0.25rem; }
.meal-item-food{ cursor:pointer; }
.autocomplete-list .item { padding:0.3rem 0.5rem; cursor:pointer; }
.autocomplete-list .item:hover { background:#f1f1f1; }
.meal-item-kcal { font-size:0.9rem; color:#333; }
.mealtype-header .collapse-toggle { transition: transform 0.2s; }
.mealtype-header .collapse-toggle[aria-expanded="true"] { transform: rotate(180deg); }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">MealTracker</a>
        <div class="ms-auto me-3">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="foods.php">Foods</a></li>
                <li class="nav-item"><a class="nav-link" href="dishes.php">Dishes</a></li>
                <li class="nav-item"><a class="nav-link" href="weights.php">Weights</a></li>
                <?php if (isset($_SESSION['isadmin']) && $_SESSION['isadmin']) : ?>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="nutrigraph.php">Nutri graph</a></li>
                <li class="nav-item"><a class="nav-link" href="preferences.php">👤</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="?date=<?php echo date('Y-m-d', strtotime("$date -1 day")); ?>" class="btn btn-secondary">&laquo; Prev</a>
    <h4 class="text-center mb-0"><?php echo $date; ?></h4>
    <a href="?date=<?php echo date('Y-m-d', strtotime("$date +1 day")); ?>" class="btn btn-secondary">Next &raquo;</a>
</div>

<div class="text-center mb-3 fw-bold">Daily Total: <span id="daily-total-top">0</span> kcal</div>

<?php foreach($mealtypes as $mt):
    $stmt = $pdo->prepare("SELECT mi.*, f.title, f.kcal, f.unit
                             FROM mealitems mi 
                             JOIN food f ON mi.fooditem=f.id 
                             WHERE mi.mealday=? AND mi.mealtype=?");
    $stmt->execute([$mealdayId, $mt['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card mb-3">
    <div class="card-header mealtype-header d-flex justify-content-between align-items-center">
        <a href="add_mealitem.php?mealday=<?php echo $mealdayId; ?>&mealtype=<?php echo $mt['id']; ?>&date=<?php echo $date; ?>" class="text-decoration-none flex-grow-1">
            <strong><?php echo htmlspecialchars($mt['name']); ?> (<span id="total-kcal-<?php echo $mt['id']; ?>">0</span> kcal)</strong>
        </a>
        <button class="btn btn-sm btn-outline-secondary collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $mt['id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $mt['id']; ?>">
            &#9660;
        </button>
    </div>
    <div class="collapse" id="collapse-<?php echo $mt['id']; ?>">
        <div class="card-body" id="mealtype-<?php echo $mt['id']; ?>">
            <?php foreach($items as $mi): echo renderMealItemRow($mi); endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

</div>

<div class="modal fade" id="mealItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Meal Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalMealType">
                <input type="hidden" id="modalItemId">
                <div class="mb-3 position-relative">
                    <label>Food</label>
                    <input type="text" id="modalFoodSearch" class="form-control" autocomplete="off" disabled>
                    <input type="hidden" id="modalFoodId">
                    <div class="autocomplete-list position-absolute bg-white border w-100"></div>
                </div>
                <div class="mb-3">
                    <label>Amount</label>
                    <input type="number" step="0.01" id="modalAmount" class="form-control">
                </div>
                <div class="mb-3">kcal: <span id="modalKcalPreview"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="modalSaveBtn">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<script>
window.computeRowKcal = function(row){
    const ds = row.dataset;
    if (ds && ds.kcal !== undefined && ds.unit !== undefined && ds.amount !== undefined) {
        const kcal = parseFloat(ds.kcal) || 0;
        const unit = parseFloat(ds.unit) || 1;
        const amount = parseFloat(ds.amount) || 0;
        return (unit>0)? (amount/unit)*kcal : 0;
    }
    return 0;
}

window.recalcTotals = function(){
    let dailyTotal = 0;
    document.querySelectorAll('[id^="mealtype-"]').forEach(mt => {
        let mealtypeTotal = 0;
        mt.querySelectorAll('.meal-item-row').forEach(rw => {
            mealtypeTotal += computeRowKcal(rw);
        });
        const mtId = mt.id.split('-')[1];
        const mtElem = document.getElementById('total-kcal-'+mtId);
        if (mtElem) mtElem.textContent = mealtypeTotal.toFixed(1);
        dailyTotal += mealtypeTotal;
    });
    document.getElementById('daily-total-top').textContent = dailyTotal.toFixed(1);
}

// --- DELETE MEAL ITEM ---
window.deleteMealItem = function(id){
    if(!confirm('Delete this item?')) return;

    fetch('ajax_delete_mealitem.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id)
    })
    .then(r => r.text())
    .then(txt => {
        if(txt.trim() === 'OK'){
            const row = document.getElementById('mealitem-' + id);
            if(row) row.remove();
            recalcTotals();
        } else {
            alert('Delete failed: ' + txt);
        }
    })
    .catch(e => alert('Delete failed'));
}

// Reload on back from bfcache
window.addEventListener('pageshow', function(event){
    if(event.persisted){
        window.location.reload();
    }
});

document.addEventListener('DOMContentLoaded', function(){
    window.modalEl=document.getElementById('mealItemModal');
    window.modal = new bootstrap.Modal(modalEl); 
    window.foodInput=document.getElementById('modalFoodSearch');
    window.foodIdInput=document.getElementById('modalFoodId');
    window.amountInput=document.getElementById('modalAmount');
    window.kcalPreview=document.getElementById('modalKcalPreview');
    window.saveBtn=document.getElementById('modalSaveBtn');
    window.autocompleteList=modalEl.querySelector('.autocomplete-list');

    // --- Edit meal item function ---
    window.editMealItem = function(id){
        const row=document.getElementById('mealitem-'+id);
        if(!row) return;

        const foodName=row.querySelector('.meal-item-food').textContent.split('×')[0].trim();
        const amount=parseFloat(row.dataset.amount);
        const foodId=row.dataset.foodid;
        const kcal=row.dataset.kcal;
        const unit=row.dataset.unit;

        window.foodInput.value=foodName;
        window.foodInput.disabled=true;
        window.foodIdInput.value=foodId;
        window.amountInput.value=amount;

        window.modalFoodKcal=parseFloat(kcal);
        window.modalFoodUnit=parseFloat(unit);
        window.modalItemId = id;
        window.modalMealType = row.parentNode.id.split('-')[1];

        window.kcalPreview.textContent=(amount/window.modalFoodUnit*window.modalFoodKcal).toFixed(1);
        document.getElementById('modalMealType').value = window.modalMealType;
        document.getElementById('modalItemId').value = window.modalItemId;

        window.modal.show();
    }

    // --- Save changes ---
    saveBtn.addEventListener('click', ()=>{
        const amt=parseFloat(amountInput.value);
        const currentItemId = document.getElementById('modalItemId').value;
        if(!amt || !currentItemId){ alert('Enter amount'); return; }

        fetch('ajax_update_mealitem.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'id='+encodeURIComponent(currentItemId)+'&amount='+encodeURIComponent(amt)
        }).then(r=>r.json())
        .then(data=>{
            const row=document.getElementById('mealitem-'+currentItemId);
            if(!row) return;
            row.dataset.amount = amt;
            if(data.kcal!==undefined) row.dataset.kcal = data.kcal;
            if(data.unit!==undefined) row.dataset.unit = data.unit;
            const foodLabelEl = row.querySelector('.meal-item-food');
            if(foodLabelEl){
                const name = foodLabelEl.textContent.split('×')[0].trim();
                foodLabelEl.textContent = name + ' × ' + amt;
            }
            const kcalTextEl = row.querySelector('.meal-item-kcal');
            if(kcalTextEl) kcalTextEl.textContent = '('+computeRowKcal(row).toFixed(1)+' kcal)';
            recalcTotals();
            modal.hide();
        }).catch(err=>{alert('Update failed');});
    });

    // amount input updates kcal
    amountInput.addEventListener('input', ()=>{
        const amt=parseFloat(amountInput.value);
        if(!isNaN(amt) && window.modalFoodUnit){ kcalPreview.textContent=(amt/window.modalFoodUnit*window.modalFoodKcal).toFixed(1); }
        else kcalPreview.textContent='';
    });

    recalcTotals();
});
</script>
</body>
</html>

