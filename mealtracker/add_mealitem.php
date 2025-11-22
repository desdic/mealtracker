<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];

if (!isset($_GET['mealday']) || !isset($_GET['mealtype'])) {
    die("Error: Missing parameters.");
}

$mealdayId = intval($_GET['mealday']);
$mealtypeId = intval($_GET['mealtype']);
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM mealtypes WHERE id=?");
$stmt->execute([$mealtypeId]);
$mealtype = $stmt->fetch(PDO::FETCH_ASSOC);

// 1. PHP Query: Select macro nutrient columns
$stmt = $pdo->prepare("SELECT mi.*, f.title, f.kcal, f.unit, f.protein, f.carbs, f.fat
                        FROM mealitems mi
                        JOIN food f ON mi.fooditem=f.id
                        WHERE mi.mealday=? AND mi.mealtype=? and mi.userid=?");
$stmt->execute([$mealdayId, $mealtypeId,$userid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Updated renderMealItemRow to include macro data
function renderMealItemRow($item) {
    $amount = floatval($item['amount']);
    $kcalPerUnit = floatval($item['kcal'] ?? 0);
    $proteinPerUnit = floatval($item['protein'] ?? 0);
    $carbsPerUnit = floatval($item['carbs'] ?? 0);
    $fatPerUnit = floatval($item['fat'] ?? 0);
    
    $unit = floatval($item['unit']);
    if ($unit <= 0) $unit = 1;

    $multiplier = ($unit > 0) ? ($amount / $unit) : 0;
    
    $itemKcal = $multiplier * $kcalPerUnit;
    $itemProtein = $multiplier * $proteinPerUnit;
    $itemCarbs = $multiplier * $carbsPerUnit;
    $itemFat = $multiplier * $fatPerUnit;

    return '<div class="meal-item-row" id="mealitem-'.$item['id'].'" '
        .'data-kcal="'.htmlspecialchars($kcalPerUnit).'" '
        .'data-unit="'.htmlspecialchars($unit).'" '
        .'data-foodid="'.htmlspecialchars($item['fooditem']).'" '
        .'data-amount="'.htmlspecialchars($amount).'"'
        .'data-protein="'.htmlspecialchars($proteinPerUnit).'"'
        .'data-carbs="'.htmlspecialchars($carbsPerUnit).'"'
        .'data-fat="'.htmlspecialchars($fatPerUnit).'">'
        .'<div class="d-flex justify-content-between align-items-center mb-1">'
            .'<div class="meal-item-food flex-grow-1" onclick="editMealItem('.$item['id'].')">'
                .htmlspecialchars($item['title']).' × '.$amount
            .'</div>'
            .'<button class="btn btn-sm btn-danger" onclick="confirmDelete('.$item['id'].')">×</button>'
        .'</div>'
        .'<div class="meal-item-kcal">('
        .number_format($itemKcal,1).' kcal'
        .' / P:'.number_format($itemProtein, 1).'g'
        .' / C:'.number_format($itemCarbs, 1).'g'
        .' / F:'.number_format($itemFat, 1).'g'
        .')</div>'
    .'</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meal Items - <?php echo htmlspecialchars($mealtype['name']); ?></title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
body.bg-light, body.bg-dark { background-color: <?php echo $theme === 'dark' ? '#121212' : '#f8f9fa'; ?>; color: <?php echo $theme === 'dark' ? '#eee' : '#000'; ?>; }
.meal-card{border-radius:1rem; padding:1rem; background-color: <?php echo $theme === 'dark' ? '#1e1e1e' : '#fff'; ?>; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.meal-item-row{padding:0.5rem;border-radius:0.25rem;margin-bottom:0.25rem; border:1px solid <?php echo $theme === 'dark' ? '#333' : '#ddd'; ?>;}
.meal-item-row:nth-child(even){background-color: <?php echo $theme === 'dark' ? '#2a2a2a' : '#f8f9fa'; ?>;}
.meal-item-row:nth-child(odd){background-color: <?php echo $theme === 'dark' ? '#1e1e1e' : '#ffffff'; ?>;}
.meal-item-food{flex-grow:1;cursor:pointer; color: <?php echo $theme === 'dark' ? '#fff' : '#000'; ?>;}
.meal-item-kcal{font-size:0.9rem;color: <?php echo $theme === 'dark' ? '#ccc' : '#333'; ?>;margin-top:0.2rem;}
.autocomplete-list{z-index:1100;max-height:200px;overflow-y:auto; background-color: <?php echo $theme === 'dark' ? '#2a2a2a' : '#fff'; ?>; color: <?php echo $theme === 'dark' ? '#eee' : '#000'; ?>; border: 1px solid <?php echo $theme === 'dark' ? '#555' : '#ddd'; ?>;}
.autocomplete-list .item{padding:0.3rem 0.5rem;cursor:pointer;}
.autocomplete-list .item:hover{background: <?php echo $theme === 'dark' ? '#3a3a3a' : '#f1f1f1'; ?>;}
#modalFoodSearch, #modalAmount { background-color: <?php echo $theme === 'dark' ? '#1e1e1e' : '#fff'; ?>; color: <?php echo $theme === 'dark' ? '#eee' : '#000'; ?>; border: 1px solid <?php echo $theme === 'dark' ? '#555' : '#ccc'; ?>; }
/* Added style for the new summary row */
.daily-summary-row { font-size: 0.85rem; }
</style>
</head>
<body class="bg-<?php echo $theme; ?>">

<div class="container mt-3">
    <button class="btn btn-secondary mb-3" onclick="window.history.back()">← Back</button>
    <h4><?php echo htmlspecialchars($mealtype['name']); ?> - <?php echo $date; ?></h4>
    
    <div class="mb-2 fw-bold">Daily Total: <span id="daily-total-kcal">0</span> kcal</div>
    <div class="daily-summary-row mb-2">
        Protein: <span id="daily-total-protein">0</span>g | 
        Carbs: <span id="daily-total-carbs">0</span>g | 
        Fat: <span id="daily-total-fat">0</span>g
    </div>
    
    <div class="mb-3 fw-bold">Meal Total: <span id="meal-total-kcal">0</span> kcal</div>
    <div class="daily-summary-row mb-3">
        Protein: <span id="meal-total-protein">0</span>g | 
        Carbs: <span id="meal-total-carbs">0</span>g | 
        Fat: <span id="meal-total-fat">0</span>g
    </div>

    <div class="meal-card" id="meal-items-container">
        <?php foreach ($items as $i): echo renderMealItemRow($i); endforeach; ?>
    </div>

    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#mealItemModal" id="addNewBtn">+ Add Item</button>
</div>

<div class="modal fade" id="mealItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: <?php echo $theme === 'dark' ? '#1e1e1e' : '#fff'; ?>; color: <?php echo $theme === 'dark' ? '#eee' : '#000'; ?>;">
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
                    <div class="autocomplete-list position-absolute w-100"></div>
                </div>
                <div class="mb-3">
                    <label>Amount</label>
                    <input type="number" step="0.01" id="modalAmount" class="form-control">
                </div>
                
                <div class="mb-3 d-none">Kcal: <span id="modalKcalPreview">0.0</span></div>
                <div class="mb-3 d-none">Protein: <span id="modalProteinPreview">0.0</span>g | Carbs: <span id="modalCarbsPreview">0.0</span>g | Fat: <span id="modalFatPreview">0.0</span>g</div>
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
// 4a. New function to compute all nutritional values for a row
window.computeRowNutrients = function(row){
    const ds = row.dataset;
    const result = { kcal: 0, protein: 0, carbs: 0, fat: 0 };
    
    if (ds && ds.kcal !== undefined && ds.unit !== undefined && ds.amount !== undefined) {
        const kcal = parseFloat(ds.kcal) || 0;
        const protein = parseFloat(ds.protein) || 0;
        const carbs = parseFloat(ds.carbs) || 0;
        const fat = parseFloat(ds.fat) || 0;
        const unit = parseFloat(ds.unit) || 1;
        const amount = parseFloat(ds.amount) || 0;
        const multiplier = (unit > 0) ? (amount / unit) : 0;
        
        result.kcal = multiplier * kcal;
        result.protein = multiplier * protein;
        result.carbs = multiplier * carbs;
        result.fat = multiplier * fat;
    }
    return result;
}

// Global variables for modal preview
window.modalFoodKcal = 0;
window.modalFoodUnit = 1;
window.modalFoodProtein = 0;
window.modalFoodCarbs = 0;
window.modalFoodFat = 0;

// 4b. Updated recalcTotals to calculate and fetch all macro totals
window.recalcTotals = function(){
    let mealTotalKcal = 0;
    let mealTotalProtein = 0;
    let mealTotalCarbs = 0;
    let mealTotalFat = 0;

    document.querySelectorAll('#meal-items-container .meal-item-row').forEach(row=>{
        const nutrients = computeRowNutrients(row);
        mealTotalKcal += nutrients.kcal;
        mealTotalProtein += nutrients.protein;
        mealTotalCarbs += nutrients.carbs;
        mealTotalFat += nutrients.fat;
    });
    
    document.getElementById('meal-total-kcal').textContent = mealTotalKcal.toFixed(1);
    document.getElementById('meal-total-protein').textContent = mealTotalProtein.toFixed(1);
    document.getElementById('meal-total-carbs').textContent = mealTotalCarbs.toFixed(1);
    document.getElementById('meal-total-fat').textContent = mealTotalFat.toFixed(1);

    // Fetch Daily Total (now expects a JSON response with total_kcal, total_protein, etc.)
    fetch('ajax_get_daily_total.php?mealday=<?php echo $mealdayId; ?>')
        .then(r=>r.json()) // Expecting JSON now
        .then(data=>{
            if (data && data.total_kcal !== undefined)
                document.getElementById('daily-total-kcal').textContent = parseFloat(data.total_kcal).toFixed(1);
            if (data && data.total_protein !== undefined)
                document.getElementById('daily-total-protein').textContent = parseFloat(data.total_protein).toFixed(1);
            if (data && data.total_carbs !== undefined)
                document.getElementById('daily-total-carbs').textContent = parseFloat(data.total_carbs).toFixed(1);
            if (data && data.total_fat !== undefined)
                document.getElementById('daily-total-fat').textContent = parseFloat(data.total_fat).toFixed(1);
        })
        .catch(err=>console.error('Daily total fetch failed', err));
}

// Custom dark/light confirm for delete
function confirmDelete(id){
    const msg = 'Delete this item?';
    if(<?php echo $theme === 'dark' ? 'true' : 'false'; ?>){
        // Dark-themed confirm using Bootstrap modal
        let modalDiv = document.createElement('div');
        modalDiv.innerHTML = `<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="background-color:#1e1e1e;color:#eee;">
                    <div class="modal-body">${msg}</div>
                    <div class="modal-footer">
                        <button id="confirmYes" class="btn btn-danger">Yes</button>
                        <button id="confirmNo" class="btn btn-secondary">No</button>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.appendChild(modalDiv);
        const bsModal = new bootstrap.Modal(modalDiv.querySelector('.modal'));
        bsModal.show();
        modalDiv.querySelector('#confirmYes').onclick = ()=>{
            deleteMealItem(id,false);
            bsModal.hide();
            modalDiv.remove();
        };
        modalDiv.querySelector('#confirmNo').onclick = ()=>{
            bsModal.hide();
            modalDiv.remove();
        };
    } else {
        if(confirm(msg)) deleteMealItem(id,false);
    }
}

window.deleteMealItem = function(id, confirmDeleteFlag){
    if(confirmDeleteFlag) return;
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

// New function to update the preview in the modal
window.updateModalPreview = function(amt){
    if(!isNaN(amt) && window.modalFoodUnit > 0){
        const multiplier = amt/window.modalFoodUnit;
        modalKcalPreview.textContent=(multiplier * window.modalFoodKcal).toFixed(1);
        modalProteinPreview.textContent=(multiplier * window.modalFoodProtein).toFixed(1);
        modalCarbsPreview.textContent=(multiplier * window.modalFoodCarbs).toFixed(1);
        modalFatPreview.textContent=(multiplier * window.modalFoodFat).toFixed(1);
    } else {
        modalKcalPreview.textContent='0.0';
        modalProteinPreview.textContent='0.0';
        modalCarbsPreview.textContent='0.0';
        modalFatPreview.textContent='0.0';
    }
}

window.editMealItem = function(id){
    const row = document.getElementById('mealitem-'+id);
    if(!row) return;
    const foodName = row.querySelector('.meal-item-food').textContent.split('×')[0].trim();
    const amount = parseFloat(row.dataset.amount);

    modalFoodInput.value = foodName;
    modalFoodInput.disabled = true; // Editing food is disabled in the modal
    modalFoodId.value = row.dataset.foodid;
    modalAmountInput.value = amount;
    modalItemId.value = id;

    // Load macro data from the row datasets
    window.modalFoodKcal = parseFloat(row.dataset.kcal);
    window.modalFoodUnit = parseFloat(row.dataset.unit);
    window.modalFoodProtein = parseFloat(row.dataset.protein);
    window.modalFoodCarbs = parseFloat(row.dataset.carbs);
    window.modalFoodFat = parseFloat(row.dataset.fat);
    
    window.updateModalPreview(amount);

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
    
    // Initialize modal preview elements (now that they exist in HTML)
    window.modalKcalPreview = document.getElementById('modalKcalPreview');
    window.modalProteinPreview = document.getElementById('modalProteinPreview');
    window.modalCarbsPreview = document.getElementById('modalCarbsPreview');
    window.modalFatPreview = document.getElementById('modalFatPreview');

    document.getElementById('addNewBtn').addEventListener('click', function(){
        modalFoodInput.value=''; modalFoodInput.disabled=false; modalFoodId.value=''; modalAmountInput.value=''; modalItemId.value='';
        window.modalFoodKcal = 0; window.modalFoodUnit = 1; window.modalFoodProtein = 0; window.modalFoodCarbs = 0; window.modalFoodFat = 0;
        window.updateModalPreview(0); // Clear preview on new item
    });
    
    // Amount input update listener
    modalAmountInput.addEventListener('input', function(){
        const amt = parseFloat(this.value);
        window.updateModalPreview(amt);
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
                d.style.backgroundColor = '<?php echo $theme === "dark" ? "#2a2a2a" : "#fff"; ?>';
                d.style.color = '<?php echo $theme === "dark" ? "#eee" : "#000"; ?>';
                d.addEventListener('click', ()=>{
                    modalFoodInput.value = f.title;
                    modalFoodId.value = f.id;
                    autocompleteList.innerHTML='';
                    
                    // Store macro data for preview
                    window.modalFoodKcal = parseFloat(f.kcal || 0);
                    window.modalFoodUnit = parseFloat(f.unit || 1);
                    window.modalFoodProtein = parseFloat(f.protein || 0);
                    window.modalFoodCarbs = parseFloat(f.carbs || 0);
                    window.modalFoodFat = parseFloat(f.fat || 0);
                    window.updateModalPreview(parseFloat(modalAmountInput.value) || 0);
                });
                autocompleteList.appendChild(d);
            });
        }).catch(()=>autocompleteList.innerHTML='');
    });

    // 4c. Update modalSaveBtn logic to handle macro data
    modalSaveBtn.addEventListener('click', function(){
        const id = modalItemId.value;
        const amt = parseFloat(modalAmountInput.value);
        const foodId = modalFoodId.value;
        const mealType = document.getElementById('modalMealType').value;

        if(!amt || (!id && !foodId)){ alert('Select food and enter amount'); return; }

        if(id){ // Editing existing item
            fetch('ajax_update_mealitem.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'id='+id+'&amount='+amt
            }).then(r=>r.json()).then(data=>{
                const row = document.getElementById('mealitem-'+id);
                
                // Update row dataset with new amount and new food nutrients (from server response)
                row.dataset.amount = amt;
                row.dataset.kcal = data.kcal;
                row.dataset.unit = data.unit;
                row.dataset.protein = data.protein; // New
                row.dataset.carbs = data.carbs;     // New
                row.dataset.fat = data.fat;         // New
                
                // Recalculate and update the text elements on the page
                const nutrients = computeRowNutrients(row);
                
                row.querySelector('.meal-item-food').textContent = row.querySelector('.meal-item-food').textContent.split('×')[0].trim()+' × '+amt;
                row.querySelector('.meal-item-kcal').innerHTML = `(${nutrients.kcal.toFixed(1)} kcal / P:${nutrients.protein.toFixed(1)}g / C:${nutrients.carbs.toFixed(1)}g / F:${nutrients.fat.toFixed(1)}g)`;

                recalcTotals();
                modal.hide();
            });
        } else { // Adding new item
            fetch('ajax_save_mealitem.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'mealday=<?php echo $mealdayId; ?>&mealtype='+mealType+'&fooditem='+foodId+'&amount='+amt
            }).then(r=>r.text())
            .then(itemId_str=>{
                const itemId = parseInt(itemId_str.trim());
                if(!itemId) throw new Error('Failed to get item ID');
                // The server must return the full item data including title and macros here
                return fetch('ajax_get_mealitems.php?id=' + itemId); 
            }).then(r=>r.json()).then(item=>{
                const container = document.getElementById('meal-items-container');
                const div = document.createElement('div');
                
                // Calculate item totals
                const unit = parseFloat(item.unit || 1);
                const multiplier = (unit > 0) ? (parseFloat(item.amount) / unit) : 0;
                const itemKcal = multiplier * parseFloat(item.kcal || 0);
                const itemProtein = multiplier * parseFloat(item.protein || 0);
                const itemCarbs = multiplier * parseFloat(item.carbs || 0);
                const itemFat = multiplier * parseFloat(item.fat || 0);
                
                // Set all datasets, including macros
                div.className='meal-item-row';
                div.id='mealitem-'+item.id;
                div.dataset.kcal=item.kcal;
                div.dataset.unit=item.unit;
                div.dataset.amount=item.amount;
                div.dataset.foodid=item.fooditem;
                div.dataset.protein=item.protein; // New
                div.dataset.carbs=item.carbs;   // New
                div.dataset.fat=item.fat;       // New
                
                // Set inner HTML with full macro display
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="meal-item-food flex-grow-1" onclick="editMealItem(${item.id})">${item.title} × ${item.amount}</div>
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(${item.id})">×</button>
                    </div>
                    <div class="meal-item-kcal">(${itemKcal.toFixed(1)} kcal / P:${itemProtein.toFixed(1)}g / C:${itemCarbs.toFixed(1)}g / F:${itemFat.toFixed(1)}g)</div>
                `;
                
                container.appendChild(div);
                recalcTotals();
                modal.hide();
            }).catch(err=>{alert('Add failed: '+err.message); console.error(err);});
        }
    });

    // Initial fetch for Daily Totals
    recalcTotals();
});
</script>
</body>
</html>
