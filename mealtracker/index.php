<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once 'user_preferences.php';
require_once("logging.php");

$expiry = time() + 172800; // 2 days
setcookie(session_name(), session_id(), $expiry, "/");

$userid = $_SESSION['user_id'];

$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];
$dateformat = $preferences['dateformat'];

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$formattedDate = date($dateformat, strtotime($date));

try {
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

	$stmt = $pdo->prepare("SELECT cups FROM water_intake WHERE mealdayid = ?");
	$stmt->execute([$mealdayId]);
	$waterIntake = $stmt->fetch(PDO::FETCH_ASSOC);
	$currentCups = $waterIntake ? intval($waterIntake['cups']) : 0;
} catch (PDOException $e) {
	log_error("failed to fetch index data: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

function renderMealItemRow($item, $theme) {
    $amount = floatval($item['amount']);
    $kcal = floatval($item['kcal']);
    $unit = floatval($item['unit']);
    if($unit <= 0) $unit = 1;
    
    // Calculate total item nutrients
    $multiplier = ($unit > 0) ? ($amount / $unit) : 0;
    $itemKcal = $multiplier * $kcal;
    
    // Pass macronutrient data for local calculation
    $protein = floatval($item['protein'] ?? 0);
    $carbs = floatval($item['carbs'] ?? 0);
    $fat = floatval($item['fat'] ?? 0);
    $itemProtein = $multiplier * $protein;
    $itemCarbs = $multiplier * $carbs;
    $itemFat = $multiplier * $fat;
    
    $row_class = $theme === 'dark' ? 'meal-item-row-dark' : 'meal-item-row-light';

    // Added data attributes for macro nutrients
    return '<div class="meal-item-row '.$row_class.'" id="mealitem-'.$item['id'].'" '
        .'data-kcal="'.htmlspecialchars($kcal).'" '
        .'data-unit="'.htmlspecialchars($unit).'" '
        .'data-foodid="'.htmlspecialchars($item['fooditem']).'" '
        .'data-amount="'.htmlspecialchars($amount).'"'
        .'data-protein="'.htmlspecialchars($protein).'"'
        .'data-carbs="'.htmlspecialchars($carbs).'"'
        .'data-fat="'.htmlspecialchars($fat).'">'
        .'<div class="d-flex justify-content-between align-items-center">'
            .'<div class="meal-item-food flex-grow-1" onclick="editMealItem('.$item['id'].')">'
                .htmlspecialchars($item['title']).' × '.$amount
            .'</div>'
            .'<button class="btn btn-sm btn-danger" onclick="deleteMealItem('.$item['id'].')">×</button>'
        .'</div>'
        // MODIFIED: Use meal-item-kcal-compact class for uniform, small text
        .'<div class="meal-item-kcal-compact">'
        .number_format($itemKcal,1).' kcal'
        .' <span class="meal-item-macros-compact">(P:'.number_format($itemProtein, 1).'g / C:'.number_format($itemCarbs, 1).'g / F:'.number_format($itemFat, 1).'g)</span>'
        .'</div>'
    .'</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meal Tracker</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.autocomplete-list { z-index: 1100; max-height: 200px; overflow-y: auto; }
/* Conditional background colors for rows based on theme */
<?php if ($theme === 'dark'): ?>
.meal-item-row-dark:nth-child(even){ background-color:#343a40; } /* bg-secondary darker */
.meal-item-row-dark:nth-child(odd){ background-color:#212529; } /* bg-dark */
.meal-item-row-dark .meal-item-kcal-compact { color:#adb5bd; } /* text-light muted */
<?php else: ?>
.meal-item-row-light:nth-child(even){ background-color:#f8f9fa; } /* bg-light */
.meal-item-row-light:nth-child(odd){ background-color:#ffffff; } /* bg-white */
.meal-item-row-light .meal-item-kcal-compact { color:#333; } /* dark text */
<?php endif; ?>
.meal-item-row{ padding:0.5rem; border-radius:0.25rem; margin-bottom:0.25rem; }
.meal-item-food{ cursor:pointer; }
.autocomplete-list .item { padding:0.3rem 0.5rem; cursor:pointer; }
/* Autocomplete list hover */
.autocomplete-list .item:hover { background:<?php echo $theme === 'dark' ? '#495057' : '#f1f1f1'; ?>; }
.mealtype-header .collapse-toggle { transition: transform 0.2s; }
.mealtype-header .collapse-toggle[aria-expanded="true"] { transform: rotate(180deg); }

/* --- WATER TRACKER CSS (Empty Circle and Checkmark Icons) --- */
.cup-icon {
    font-size: 2rem;
    cursor: pointer;
    padding: 0 0.25rem; 
    /* Prevent blue highlight when selecting the emoji text content */
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* IE10+/Edge */
    user-select: none; /* Standard syntax */
}

/* Force selection color to match card background for invisible selection */
.card-body::selection {
    background: <?php echo $theme === 'dark' ? '#343a40' : '#ffffff'; ?>;
    color: transparent; /* Hide text color during selection */
}
.card-body::-moz-selection {
    background: <?php echo $theme === 'dark' ? '#343a40' : '#ffffff'; ?>;
    color: transparent;
}

/* --- MOBILE OPTIMIZATION for Water Tracker --- */
@media (max-width: 576px) { 
    .cup-icon {
        /* Slightly reduce icon size on small screens */
        font-size: 1.5rem; 
        /* Reduce padding/margin to fit all 8 across */
        padding: 0 0.15rem; 
    }
    .d-flex.justify-content-center {
        /* Ensure the container itself can shrink */
        width: 100%;
        box-sizing: border-box;
    }
}
/* --- END WATER TRACKER CSS --- */

/* Added style for the new summary row */
.daily-summary-row {
    font-size: 0.9rem;
}

/* Mealtype Macro Summary is smaller */
.mealtype-macro-summary {
    font-size: 0.75rem; 
    font-weight: normal;
    display: block;
    margin-top: 0.25rem;
}

/* NEW: Style for per-item Kcal/P/C/F summary */
.meal-item-kcal-compact {
    font-size: 0.75rem; /* Set Kcal and P/C/F to the same smaller size */
}
.meal-item-macros-compact {
    font-size: 1em; /* Inherit size from parent (.meal-item-kcal-compact) */
    color: inherit; 
}
</style>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">
<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
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
    <h4 class="text-center mb-0"><?php echo $formattedDate; ?></h4>
    <a href="?date=<?php echo date('Y-m-d', strtotime("$date +1 day")); ?>" class="btn btn-secondary">Next &raquo;</a>
</div>

<div class="text-center mb-3">
    <div class="fw-bold">Daily Total: <span id="daily-total-kcal">0</span> kcal</div>
    <div class="daily-summary-row">
        Protein: <span id="daily-total-protein">0</span>g | 
        Carbs: <span id="daily-total-carbs">0</span>g | 
        Fat: <span id="daily-total-fat">0</span>g
    </div>
</div>

<?php 
// Select meal items, now including protein, carbs, and fat (assuming these columns exist in 'food' table)
foreach($mealtypes as $mt):
    $stmt = $pdo->prepare("SELECT mi.*, f.title, f.kcal, f.unit, f.protein, f.carbs, f.fat 
                          FROM mealitems mi 
                          JOIN food f ON mi.fooditem=f.id 
                          WHERE mi.mealday=? AND mi.mealtype=?");
    $stmt->execute([$mealdayId, $mt['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card mb-3 <?php echo $theme === 'dark' ? 'bg-secondary text-light border-light' : ''; ?>">
    <div class="card-header mealtype-header d-flex justify-content-between align-items-center <?php echo $theme === 'dark' ? 'bg-dark border-light' : 'bg-white'; ?>">
        <a href="add_mealitem.php?mealday=<?php echo $mealdayId; ?>&mealtype=<?php echo $mt['id']; ?>&date=<?php echo $date; ?>" class="text-decoration-none flex-grow-1 <?php echo $theme === 'dark' ? 'text-light' : 'text-dark'; ?>">
            <strong><?php echo htmlspecialchars($mt['name']); ?> (<span id="total-kcal-<?php echo $mt['id']; ?>">0</span> kcal)</strong>
            
            <span class="mealtype-macro-summary" id="mealtype-summary-<?php echo $mt['id']; ?>">
                P: 0g | C: 0g | F: 0g
            </span>
        </a>
        <button class="btn btn-sm btn-outline-secondary collapse-toggle <?php echo $theme === 'dark' ? 'text-light border-light' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $mt['id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $mt['id']; ?>">
            &#9660;
        </button>
    </div>
    <div class="collapse" id="collapse-<?php echo $mt['id']; ?>">
        <div class="card-body" id="mealtype-<?php echo $mt['id']; ?>">
            <?php foreach($items as $mi): echo renderMealItemRow($mi, $theme); endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="card mb-3 <?php echo $theme === 'dark' ? 'bg-secondary text-light border-light' : ''; ?>">
    <div class="card-header mealtype-header d-flex justify-content-between align-items-center <?php echo $theme === 'dark' ? 'text-light' : 'text-dark'; ?>">
        <strong class="<?php echo $theme === 'dark' ? 'text-light' : 'text-dark'; ?>">💧 Water Intake (<span id="water-count-text"><?php echo $currentCups; ?></span> / 8 cups)</strong>
    </div>
    <div class="card-body text-center">
        <div class="d-flex justify-content-center" id="cup-tracker-container" data-mealday-id="<?php echo $mealdayId; ?>">
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <?php
                // Use the appropriate emoji based on initial state
                $emoji = ($i <= $currentCups) ? '✅' : '⚪';
                $cupClass = ($i <= $currentCups) ? ' cup-full' : ''; // Keep the class for JS logic
                ?>
                <span class="cup-icon<?php echo $cupClass; ?> me-2" data-cup-index="<?php echo $i; ?>">
                    <?php echo $emoji; ?>
                </span>
            <?php endfor; ?>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="mealItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content <?php echo $theme === 'dark' ? 'bg-dark text-light' : ''; ?>">
            <div class="modal-header <?php echo $theme === 'dark' ? 'border-secondary' : ''; ?>">
                <h5 class="modal-title">Edit Meal Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" <?php echo $theme === 'dark' ? 'data-bs-theme="dark"' : ''; ?>></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalMealType">
                <input type="hidden" id="modalItemId">
                <div class="mb-3 position-relative">
                    <label>Food</label>
                    <input type="text" id="modalFoodSearch" class="form-control" autocomplete="off" disabled>
                    <input type="hidden" id="modalFoodId">
                    <div class="autocomplete-list position-absolute border w-100 <?php echo $theme === 'dark' ? 'bg-dark border-light' : 'bg-white'; ?>"></div> 
                </div>
                <div class="mb-3">
                    <label>Amount</label>
                    <input type="number" step="0.01" id="modalAmount" class="form-control">
                </div>
                </div>
            <div class="modal-footer <?php echo $theme === 'dark' ? 'border-secondary' : ''; ?>">
                <button type="button" class="btn btn-primary" id="modalSaveBtn">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<script>
// Extended function to compute all nutritional values for a row
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

// Global variable to store the macro nutrients for the modal (still needed for old preview functions if they were called elsewhere, but the elements are gone)
window.modalFoodKcal = 0;
window.modalFoodUnit = 1;
window.modalFoodProtein = 0;
window.modalFoodCarbs = 0;
window.modalFoodFat = 0;

window.recalcTotals = function(){
    let total_kcal = 0;
    let total_protein = 0;
    let total_carbs = 0;
    let total_fat = 0;

    document.querySelectorAll('[id^="mealtype-"]').forEach(mt => {
        let mealtypeTotal = { kcal: 0, protein: 0, carbs: 0, fat: 0 }; // Initialize macro totals
        mt.querySelectorAll('.meal-item-row').forEach(rw => {
            const nutrients = computeRowNutrients(rw);
            
            // Mealtype totals
            mealtypeTotal.kcal += nutrients.kcal;
            mealtypeTotal.protein += nutrients.protein;
            mealtypeTotal.carbs += nutrients.carbs;
            mealtypeTotal.fat += nutrients.fat;
            
            // Daily totals
            total_kcal += nutrients.kcal;
            total_protein += nutrients.protein;
            total_carbs += nutrients.carbs;
            total_fat += nutrients.fat;
        });
        
        const mtId = mt.id.split('-')[1];
        
        // Update Mealtype Kcal display
        const mtKcalElem = document.getElementById('total-kcal-'+mtId);
        if (mtKcalElem) mtKcalElem.textContent = mealtypeTotal.kcal.toFixed(1);
        
        // Update Mealtype Macro Summary Display
        const mtSummaryElem = document.getElementById('mealtype-summary-'+mtId);
        if (mtSummaryElem) {
            mtSummaryElem.textContent = 
                `P: ${mealtypeTotal.protein.toFixed(1)}g | C: ${mealtypeTotal.carbs.toFixed(1)}g | F: ${mealtypeTotal.fat.toFixed(1)}g`;
        }
    });

    // Update Daily Total display elements
    document.getElementById('daily-total-kcal').textContent = total_kcal.toFixed(1);
    document.getElementById('daily-total-protein').textContent = total_protein.toFixed(1);
    document.getElementById('daily-total-carbs').textContent = total_carbs.toFixed(1);
    document.getElementById('daily-total-fat').textContent = total_fat.toFixed(1);
}

// --- DELETE MEAL ITEM ---
window.deleteMealItem = function(id){
    if(!confirm('Delete this item?')) return;

    const row = document.getElementById('mealitem-' + id);
    if (!row) return;

    fetch('ajax_delete_mealitem.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id)
    })
    .then(r => r.text())
    .then(txt => {
        if(txt.trim() === 'OK'){
            // Remove the row *after* confirmation
            row.remove(); 
            // Recalculate totals globally, which will account for the missing row
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
    // Removed: window.kcalPreview=document.getElementById('modalKcalPreview');
    // Removed: window.proteinPreview=document.getElementById('modalProteinPreview');
    // Removed: window.carbsPreview=document.getElementById('modalCarbsPreview');
    // Removed: window.fatPreview=document.getElementById('modalFatPreview');
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
        const protein=row.dataset.protein;
        const carbs=row.dataset.carbs;
        const fat=row.dataset.fat;

        window.foodInput.value=foodName;
        window.foodInput.disabled=true;
        window.foodIdInput.value=foodId;
        window.amountInput.value=amount;

        // Still set these global variables, even if we don't display the preview
        window.modalFoodKcal=parseFloat(kcal);
        window.modalFoodUnit=parseFloat(unit);
        window.modalFoodProtein=parseFloat(protein);
        window.modalFoodCarbs=parseFloat(carbs);
        window.modalFoodFat=parseFloat(fat);
        window.modalItemId = id;
        window.modalMealType = row.parentNode.id.split('-')[1];
        
        // Initial preview calculation is now just a placeholder function call
        window.updateModalPreview(amount);

        document.getElementById('modalMealType').value = window.modalMealType;
        document.getElementById('modalItemId').value = window.modalItemId;

        window.modal.show();
    }
    
    // New function to update the preview in the modal - MODIFIED to do nothing
    window.updateModalPreview = function(amt){
        /* This function no longer updates visible elements, but the logic 
        is kept here in case you re-introduce the preview later or it's 
        called elsewhere.
        
        if(!isNaN(amt) && window.modalFoodUnit > 0){
            const multiplier = amt/window.modalFoodUnit;
            window.kcalPreview.textContent=(multiplier * window.modalFoodKcal).toFixed(1);
            window.proteinPreview.textContent=(multiplier * window.modalFoodProtein).toFixed(1);
            window.carbsPreview.textContent=(multiplier * window.modalFoodCarbs).toFixed(1);
            window.fatPreview.textContent=(multiplier * window.modalFoodFat).toFixed(1);
        } else {
            window.kcalPreview.textContent='0.0';
            window.proteinPreview.textContent='0.0';
            window.carbsPreview.textContent='0.0';
            window.fatPreview.textContent='0.0';
        }
        */
        return; // Do nothing since the elements were removed
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
        })
        .then(r=>r.json())
        .then(data=>{
            const row=document.getElementById('mealitem-'+currentItemId);
            if(!row) return;

            // Update row dataset with new amount (mandatory) and potentially new food nutrients (if returned)
            row.dataset.amount = amt;
            // Assuming the server returns the per-unit nutrient values from the food table
            if(data.kcal!==undefined) row.dataset.kcal = data.kcal;
            if(data.unit!==undefined) row.dataset.unit = data.unit;
            if(data.protein!==undefined) row.dataset.protein = data.protein;
            if(data.carbs!==undefined) row.dataset.carbs = data.carbs;
            if(data.fat!==undefined) row.dataset.fat = data.fat;
            
            // Recalculate and update the text elements on the page
            const foodLabelEl = row.querySelector('.meal-item-food');
            if(foodLabelEl){
                const name = foodLabelEl.textContent.split('×')[0].trim();
                foodLabelEl.textContent = name + ' × ' + amt;
            }
            
            const nutrients = computeRowNutrients(row);
            const kcalTextEl = row.querySelector('.meal-item-kcal-compact'); 
            if(kcalTextEl) {
                // Update the HTML structure for the compact Kcal and macro summary
                kcalTextEl.innerHTML = 
                    `${nutrients.kcal.toFixed(1)} kcal <span class="meal-item-macros-compact">(P:${nutrients.protein.toFixed(1)}g / C:${nutrients.carbs.toFixed(1)}g / F:${nutrients.fat.toFixed(1)}g)</span>`;
            }
            
            recalcTotals();
            modal.hide();
        }).catch(err=>{alert('Update failed');});
    });

    // amount input updates kcal AND macro preview (no longer updates visible preview, but runs the function)
    amountInput.addEventListener('input', ()=>{
        const amt=parseFloat(amountInput.value);
        window.updateModalPreview(amt);
    });

    recalcTotals();


    // --- WATER TRACKER JAVASCRIPT ---
    const cupTracker = document.getElementById('cup-tracker-container');
    const waterCountText = document.getElementById('water-count-text');
    const cupIcons = document.querySelectorAll('#cup-tracker-container .cup-icon');
    const mealDayId = cupTracker ? cupTracker.dataset.mealdayId : null;
    const fullClass = 'cup-full'; // Custom class for the filled state
    const emptyIcon = '⚪';
    const fullIcon = '✅';
    
    /**
     * Sends the new cup count to the server.
     */
    function updateWaterDatabase(newCount) {
        if (!mealDayId) return;

        fetch('ajax_update_water.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `mealdayid=${encodeURIComponent(mealDayId)}&cups=${encodeURIComponent(newCount)}`
        })
        .then(r => r.text())
        .then(txt => {
            if (txt.trim() !== 'OK') {
                console.error('Failed to update water intake:', txt);
                alert('Failed to save water intake. Refreshing page.');
                location.reload(); 
            }
        })
        .catch(e => {
            console.error('AJAX Error:', e);
            alert('Error connecting to server for water update. Refreshing page.');
            location.reload();
        });
    }

    /**
     * Updates the visual state based on a given count. Used for initial load and after clicks.
     */
    function updateWaterDisplay(consumedCount) {
        waterCountText.textContent = consumedCount;
        
        cupIcons.forEach(cup => {
             const cupIndex = parseInt(cup.getAttribute('data-cup-index'));
             if (cupIndex <= consumedCount) {
                 cup.classList.add(fullClass);
                 cup.textContent = fullIcon; // Set to green checkmark
             } else {
                 cup.classList.remove(fullClass);
                 cup.textContent = emptyIcon; // Set to empty circle
             }
           });
    }

    /**
     * Handles the click event on any cup icon (Toggling logic).
     */
    if (cupTracker) {
        cupTracker.addEventListener('click', function(event) {
            const clickedCup = event.target;
            
            if (clickedCup.classList.contains('cup-icon')) {
                // Determine the current state of the clicked cup
                const isCurrentlyFull = clickedCup.classList.contains(fullClass);
                
                // If the clicked cup is NOT full, we fill it and all cups before it.
                // If it IS full, we empty it and all cups AFTER it.
                let clickedIndex = parseInt(clickedCup.getAttribute('data-cup-index'));

                cupIcons.forEach(cup => {
                    const cupIndex = parseInt(cup.getAttribute('data-cup-index'));
                    
                    if (isCurrentlyFull) {
                        // If it's full, and the index is >= the clicked index, empty it
                        if (cupIndex >= clickedIndex) {
                            cup.classList.remove(fullClass);
                            cup.textContent = emptyIcon;
                        }
                    } else {
                        // If it's empty, and the index is <= the clicked index, fill it
                        if (cupIndex <= clickedIndex) {
                            cup.classList.add(fullClass);
                            cup.textContent = fullIcon;
                        }
                    }
                });

                // Recalculate the new total consumed cups by counting the 'full' ones
                let newCount = 0;
                cupIcons.forEach(cup => {
                    if (cup.classList.contains(fullClass)) {
                        newCount++;
                    }
                });
                
                // Update the display text and save to database
                waterCountText.textContent = newCount;
                updateWaterDatabase(newCount);
            }
        });
    }
    
    // Initial display setup using the PHP-fetched value:
    updateWaterDisplay(parseInt(waterCountText.textContent));
    // --- END WATER TRACKER JAVASCRIPT ---

});
</script>
</body>
</html>
