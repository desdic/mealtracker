<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
require_once 'user_preferences.php';
require_once("logging.php");

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];
try {
	$users = $pdo->query("SELECT * FROM user")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	log_error("failed get users: " . $e->getMessage());
	http_response_code(500);
	die("error");
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<style>
.list-group-item:hover {
    background-color: <?php echo $theme === 'dark' ? '#495057' : '#f8f9fa'; ?>;
    cursor: pointer;
}
</style>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">

<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Users</span>
    <a href="add_user.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>

<div class="container">
  <ul class="list-group">
    <?php foreach($users as $u): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $theme === 'dark' ? 'bg-secondary text-light' : ''; ?>">
      <?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['firstname'].' '.$u['lastname']); ?>)
      <div>
        <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn btn-sm <?php echo $theme === 'dark' ? 'light' : 'btn-outline-secondary'; ?>">✏️</a>
        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $u['id']; ?>)">×</button>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

<!-- Custom Confirm Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content <?php echo $theme === 'dark' ? 'bg-dark text-light' : ''; ?>">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" <?php echo $theme === 'dark' ? 'data-bs-theme="dark"' : ''; ?>></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this user?
      </div>
      <div class="modal-footer">
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

function confirmDelete(userId){
    confirmDeleteBtn.href = 'delete_user.php?id=' + userId;
    confirmDeleteModal.show();
}
</script>

</body>
</html>

