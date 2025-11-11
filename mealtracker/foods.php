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

$foods = $pdo->query("SELECT * FROM food ORDER BY dishid, title")->fetchAll(PDO::FETCH_ASSOC);

// Define alternating classes based on the current theme
$light_bg_even = 'bg-white';
$light_bg_odd = 'bg-light';
$dark_bg_even = 'bg-secondary'; // A slightly lighter dark color
$dark_bg_odd = 'bg-darker'; // A custom class for a slightly darker dark color
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Foods</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* Custom class for a darker background in dark mode */
.bg-darker {
    background-color: #212529 !important; /* Slightly darker than Bootstrap's .bg-secondary (#495057) or standard .bg-dark (#343a40) */
}
/* Ensure the text is visible on the custom darker background */
.bg-darker.text-light small.text-muted {
    color: #adb5bd !important;
}

.list-group-item:hover {
    /* Use a fixed, noticeable hover color regardless of current background */
    background-color: <?php echo $theme === 'dark' ? '#495057' : '#f0f0f0'; ?> !important; 
    cursor: pointer;
}
/* Ensure hover text stays visible */
.list-group-item:hover small.text-muted {
    color: <?php echo $theme === 'dark' ? '#dee2e6' : '#6c757d'; ?> !important;
}
</style>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">

<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
    <div class="container-fluid">
        <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
        <span class="navbar-brand mx-auto">Foods</span>
        <a href="add_food.php" class="btn btn-primary">+ Add</a>
    </div>
</nav>

<div class="container">
    <ul class="list-group">
        <?php $i = 0; foreach($foods as $f): 
            // Determine class based on row index and theme
            if ($theme === 'dark') {
                $row_class = ($i % 2 == 0) ? $dark_bg_even . ' text-light' : $dark_bg_odd . ' text-light';
            } else {
                $row_class = ($i % 2 == 0) ? $light_bg_even : $light_bg_odd;
            }
            // Increment row counter
            $i++;
        ?>
        <li class="list-group-item d-flex justify-content-between align-items-start <?php echo $row_class; ?> border-<?php echo $theme === 'dark' ? 'secondary' : 'light'; ?>">
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
						<a href="edit_food.php?id=<?php echo $f['id']; ?>" 
						   class="btn btn-sm <?php echo $theme === 'dark' ? 'text-light' : 'text-dark'; ?>" 
						   style="background: none; border: none;">✏️</a>
						<button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $f['id']; ?>)">×</button>
					<?php endif; ?>
				<?php else: ?>
					<?php if ($userid == $f['addedby']): ?>
						<a href="edit_dish.php?id=<?php echo $f['dishid']; ?>" 
						   class="btn btn-sm <?php echo $theme === 'dark' ? 'text-light' : 'text-dark'; ?>" 
						   style="background: none; border: none;">✏️🍽️</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content <?php echo $theme === 'dark' ? 'bg-dark text-light' : ''; ?>">
      <div class="modal-header border-<?php echo $theme === 'dark' ? 'secondary' : 'light'; ?>">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" <?php echo $theme === 'dark' ? 'data-bs-theme="dark"' : ''; ?>></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this food?
      </div>
      <div class="modal-footer border-<?php echo $theme === 'dark' ? 'secondary' : 'light'; ?>">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Delete</a>
      </div>
    </div>
  </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<script>
let confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
let confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

function confirmDelete(foodId){
    confirmDeleteBtn.href = 'delete_food.php?id=' + foodId;
    confirmDeleteModal.show();
}
</script>
</body>
</html>
