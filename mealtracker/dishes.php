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

$dishes = $pdo->query("SELECT d.*, d.addedby as addedbyid, u.username AS addedby FROM dish d JOIN user u ON d.addedby=u.id ORDER BY d.name")->fetchAll(PDO::FETCH_ASSOC);

// Define alternating classes based on the current theme
$light_bg_even = 'bg-white';
$light_bg_odd = 'bg-light';
$dark_bg_even = 'bg-secondary';
$dark_bg_odd = 'bg-darker'; // Custom class for striping in dark mode
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dishes</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
/* Custom class for a darker background in dark mode */
.bg-darker {
    background-color: #212529 !important;
}
.dish-clickable {
    cursor: pointer;
}
.dish-clickable:hover,
.list-group-item:hover { /* Updated hover to ensure it overrides striped colors */
    background-color: <?php echo $theme === 'dark' ? '#495057' : '#f8f9fa'; ?> !important;
}
</style>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">

<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Dishes</span>
    <a href="add_dish.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>

<div class="container">
  <ul class="list-group">
    <?php $i = 0; foreach($dishes as $dish): 
        // Determine class based on row index and theme for striping
        if ($theme === 'dark') {
            $row_class = ($i % 2 == 0) ? $dark_bg_even . ' text-light' : $dark_bg_odd . ' text-light';
        } else {
            $row_class = ($i % 2 == 0) ? $light_bg_even : $light_bg_odd;
        }
        $i++;
    ?>
    <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $row_class; ?>">

        <?php if ($userid == $dish['addedbyid']): ?>
      <div class="flex-grow-1 dish-clickable" onclick="window.location='edit_dish.php?id=<?php echo $dish['id']; ?>'">
        <?php echo htmlspecialchars($dish['name']); ?> (<?php echo $dish['kcal']; ?> kcal)
      </div>
        <?php else: ?>
      <div class="flex-grow-1 dish-clickable">
        <?php echo htmlspecialchars($dish['name']); ?> (<?php echo $dish['kcal']; ?> kcal)
      </div>
        <?php endif; ?>

      <div class="d-flex flex-shrink-0 gap-1">
        <button class="btn btn-sm <?php echo $theme === 'dark' ? 'light' : ''; ?>" onclick="confirmAction('clone', <?php echo $dish['id']; ?>)">📄</button>
        <?php if ($userid == $dish['addedbyid']): ?>
        <button class="btn btn-sm btn-danger" onclick="confirmAction('delete', <?php echo $dish['id']; ?>)">×</button>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content <?php echo $theme === 'dark' ? 'bg-dark text-light' : ''; ?>">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalTitle">Confirm</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" <?php echo $theme === 'dark' ? 'data-bs-theme="dark"' : ''; ?>></button>
      </div>
      <div class="modal-body" id="confirmModalBody">
        Are you sure?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger" id="confirmModalBtn">Yes</a>
      </div>
    </div>
  </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<script>
let confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
let confirmModalBtn = document.getElementById('confirmModalBtn');
let confirmModalTitle = document.getElementById('confirmModalTitle');
let confirmModalBody = document.getElementById('confirmModalBody');

function confirmAction(action, id){
    if(action === 'delete'){
        confirmModalTitle.textContent = 'Confirm Delete';
        confirmModalBody.textContent = 'Delete this dish?';
        confirmModalBtn.className = 'btn btn-danger';
        confirmModalBtn.href = 'delete_dish.php?id=' + id;
    } else if(action === 'clone'){
        confirmModalTitle.textContent = 'Confirm Clone';
        confirmModalBody.textContent = 'Clone this dish?';
        confirmModalBtn.className = 'btn btn-primary';
        confirmModalBtn.href = 'clone_dish.php?id=' + id;
    }
    confirmModal.show();
}
</script>
</body>
</html>
