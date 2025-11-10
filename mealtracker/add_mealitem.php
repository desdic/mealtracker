<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');

if (!isset($_GET['mealday']) || !isset($_GET['mealtype'])) {
    die("Error: Missing parameters.");
}

$mealdayId = intval($_GET['mealday']);
$mealtypeId = intval($_GET['mealtype']);
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$userid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM mealtypes WHERE id=?");
$stmt->execute([$mealtypeId]);
$mealtype = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT mi.*, f.title, f.kcal, f.unit
                       FROM mealitems mi
                       JOIN food f ON mi.fooditem=f.id
                       WHERE mi.mealday=? AND mi.mealtype=? and mi.userid=?");
$stmt->execute([$mealdayId, $mealtypeId,$userid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function renderMealItemRow($item) {
    $amount = floatval($item['amount']);
    $unit = floatval($item['unit']);
    if ($unit <= 0) $unit = 1;
    $kcal = ($amount / $unit) * $item['kcal'];

    return '<div class="meal-item-row" id="mealitem-'.$item['id'].'" '
        .'data-kcal="'.htmlspecialchars($item['kcal']).'" '
        .'data-unit="'.htmlspecialchars($unit).'" '
        .'data-foodid="'.htmlspecialchars($item['fooditem']).'" '
        .'data-amount="'.htmlspecialchars($amount).'">'
        .'<div class="d-flex justify-content-between align-items-center mb-1">'
            .'<div class="meal-item-food flex-grow-1" onclick="editMealItem('.$item['id'].')">'
                .htmlspecialchars($item['title']).' × '.$amount
            .'</div>'
            .'<button class="btn btn-sm btn-danger" onclick="deleteMealItem('.$item['id'].', true)">×</button>'
        .'</div>'
        .'<div class="meal-item-kcal">('.number_format($kcal,1).' kcal)</div>'
    .'</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meal Items - <?php echo htmlspecialchars($mealtype['name']); ?></title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.meal-card{border-radius:1rem; padding:1rem; background-color:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.meal-item-row{padding:0.5rem;border-radius:0.25rem;margin-bottom:0.25rem; border:1px solid #ddd;}
.meal-item-row:nth-child(even){background-color:#f8f9fa;}
.meal-item-row:nth-child(odd){background-color:#ffffff;}
.meal-item-food{flex-grow:1;cursor:pointer;}
.meal-item-kcal{font-size:0.9rem;color:#333;margin-top:0.2rem;}
.autocomplete-list{z-index:1100;max-height:200px;overflow-y:auto;}
.autocomplete-list .item{padding:0.3rem 0.5rem;cursor:pointer;}
.autocomplete-list .item:hover{background:#f1f1f1;}
</style>
</head>
<body class="bg-light">

<div class="container mt-3">
    <button class="btn btn-secondary mb-3" onclick="window.history.back()">← Back</button>
    <h4><?php echo htmlspecialchars($mealtype['name']); ?> - <?php echo $date; ?></h4>
    <div class="mb-2 fw-bold">Daily Total: <span id="daily-total-top">0</span> kcal</div>
    <div class="mb-3 fw-bold">Meal Total: <span id="meal-total-top">0</span> kcal</div>

    <div class="meal-card" id="meal-items-container">
        <?php foreach ($items as $i): echo renderMealItemRow($i); endforeach; ?>
    </div>

    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#mealItemModal" id="addNewBtn">+ Add Item</button>
</div>

<!-- Modal -->
<div class="modal fade" id="mealItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Meal Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalMealType" value="<?php echo $mealtypeId; ?>">
                <input type="hidden" id="modalItemId">
                <div class="mb-3 position-relative">
                    <label>Food</label>
                    <input type="text" id="modalFoodSearch" class="form-control" autocomplete="off">
                    <input type="hidden" id="modalFoodId">
                    <div class="autocomplete-list position-absolute bg-white border w-100"></div>
                </div>
                <div class="mb-3">
                    <label>Amount</label>
                    <input type="number" step="0.01" id="modalAmount" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="modalSaveBtn">Save</button>
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Back</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.computeRowKcal = function(row){
    const ds = row.dataset;
    return ds.unit>0 ? (parseFloat(ds.amount)/parseFloat(ds.unit))*parseFloat(ds.kcal) : 0;
}

window.recalcTotals = function(){
    let mealTotal = 0;

    // Calculate current meal total
    document.querySelectorAll('#meal-items-container .meal-item-row').forEach(row=>{
        mealTotal += computeRowKcal(row);
    });

    // Update meal total instantly
    document.getElementById('meal-total-top').textContent = mealTotal.toFixed(1);

    // Fetch updated daily total from DB (includes all mealtypes)
    fetch('ajax_get_daily_total.php?mealday=<?php echo $mealdayId; ?>')
        .then(r=>r.text())
        .then(val=>{
            const total = parseFloat(val);
            if (!isNaN(total))
                document.getElementById('daily-total-top').textContent = total.toFixed(1);
        })
        .catch(err=>console.error('Daily total fetch failed', err));
}

window.deleteMealItem = function(id, confirmDelete){
    if(confirmDelete && !confirm('Delete this item?')) return;
    fetch('ajax_delete_mealitem.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+encodeURIComponent(id)
    }).then(r=>r.text()).then(resp=>{
        if(resp.trim()!=='OK'){ alert('Delete failed'); return; }
        const el=document.getElementById('mealitem-'+id);
        if(el) el.remove();
        recalcTotals();
    }).catch(()=>alert('Delete failed'));
}

window.editMealItem = function(id){
    const row = document.getElementById('mealitem-'+id);
    if(!row) return;
    const foodName = row.querySelector('.meal-item-food').textContent.split('×')[0].trim();
    const amount = parseFloat(row.dataset.amount);

    modalFoodInput.value = foodName;
    modalFoodId.value = row.dataset.foodid;
    modalAmountInput.value = amount;
    modalItemId.value = id;

    modal.show();
}

document.addEventListener('DOMContentLoaded', function(){
    window.modalEl = document.getElementById('mealItemModal');
    window.modal = new bootstrap.Modal(modalEl);
    window.modalFoodInput = document.getElementById('modalFoodSearch');
    window.modalFoodId = document.getElementById('modalFoodId');
    window.modalAmountInput = document.getElementById('modalAmount');
    window.modalItemId = document.getElementById('modalItemId');
    window.modalSaveBtn = document.getElementById('modalSaveBtn');
    window.autocompleteList = modalEl.querySelector('.autocomplete-list');

    document.getElementById('addNewBtn').addEventListener('click', function(){
        modalFoodInput.value=''; modalFoodId.value=''; modalAmountInput.value=''; modalItemId.value='';
    });

    modalFoodInput.addEventListener('input', function(){
        const val = this.value;
        if(val.length < 1){ autocompleteList.innerHTML=''; return; }

        fetch('ajax_search_food.php?q='+encodeURIComponent(val))
        .then(r=>r.json())
        .then(data=>{
            autocompleteList.innerHTML='';
            data.forEach(f=>{
                const d = document.createElement('div');
                d.className = 'item';
                d.textContent = f.title;
                d.addEventListener('click', ()=>{
                    modalFoodInput.value = f.title;
                    modalFoodId.value = f.id;
                    autocompleteList.innerHTML='';
                });
                autocompleteList.appendChild(d);
            });
        }).catch(()=>autocompleteList.innerHTML='');
    });

    modalSaveBtn.addEventListener('click', function(){
        const id = modalItemId.value;
        const amt = parseFloat(modalAmountInput.value);
        const foodId = modalFoodId.value;
        const mealType = document.getElementById('modalMealType').value;

        if(!amt || (!id && !foodId)){ alert('Select food and enter amount'); return; }

        if(id){ 
            fetch('ajax_update_mealitem.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'id='+id+'&amount='+amt
            }).then(r=>r.json()).then(data=>{
                const row = document.getElementById('mealitem-'+id);
                row.dataset.amount = amt;
                row.dataset.kcal = data.kcal;
                row.dataset.unit = data.unit;
                row.querySelector('.meal-item-food').textContent = row.querySelector('.meal-item-food').textContent.split('×')[0].trim()+' × '+amt;
                row.querySelector('.meal-item-kcal').textContent = '('+((amt/data.unit)*data.kcal).toFixed(1)+' kcal)';
                recalcTotals();
                modal.hide();
            });
        } else {
            fetch('ajax_save_mealitem.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'mealday=<?php echo $mealdayId; ?>&mealtype='+mealType+'&fooditem='+foodId+'&amount='+amt
            }).then(r=>r.text())
            .then(itemId_str=>{
                const itemId = parseInt(itemId_str.trim());
                if(!itemId) throw new Error('Failed to get item ID');
                return fetch('ajax_get_mealitems.php?id=' + itemId);
            }).then(r=>r.json()).then(item=>{
                const container = document.getElementById('meal-items-container');
                const div = document.createElement('div');
                div.className='meal-item-row';
                div.id='mealitem-'+item.id;
                div.dataset.kcal=item.kcal;
                div.dataset.unit=item.unit;
                div.dataset.amount=item.amount;
                div.dataset.foodid=item.fooditem;
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="meal-item-food flex-grow-1" onclick="editMealItem(${item.id})">${item.title} × ${item.amount}</div>
                        <button class="btn btn-sm btn-danger" onclick="deleteMealItem(${item.id}, true)">×</button>
                    </div>
                    <div class="meal-item-kcal">(${((item.amount/item.unit)*item.kcal).toFixed(1)} kcal)</div>
                `;
                container.appendChild(div);
                recalcTotals();
                modal.hide();
            }).catch(err=>{alert('Add failed: '+err.message); console.error(err);});
        }
    });

    fetch('ajax_get_daily_total.php?mealday=<?php echo $mealdayId; ?>')
        .then(r=>r.text())
        .then(val=>{
            const total = parseFloat(val);
            if (!isNaN(total))
                document.getElementById('daily-total-top').textContent = total.toFixed(1);
        })
        .catch(err=>console.error('Initial daily total fetch failed', err));

    recalcTotals();
});
</script>
</body>
</html>

