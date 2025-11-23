<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'] ?? 'light';

if($_SERVER['REQUEST_METHOD']=='POST'){
	AddUser($pdo);
    header("Location: users.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add User</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="<?php echo $theme==='dark'?'bg-dark text-light':'bg-light text-dark'; ?>">
<div class="container mt-3">
<h3>Add User</h3>
<form method="post">
    <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required autocomplete="off"></div>
    <div class="mb-3"><label>First Name</label><input type="text" name="firstname" class="form-control" required autocomplete="off"></div>
    <div class="mb-3"><label>Last Name</label><input type="text" name="lastname" class="form-control" required autocomplete="off"></div>
    <div class="mb-3"><label>Password</label><input type="password" name="checksum" class="form-control" required autocomplete="off"></div>
    <div class="form-check mb-3"><input type="checkbox" name="disabled" class="form-check-input"><label class="form-check-label">Disabled</label></div>
    <div class="form-check mb-3"><input type="checkbox" name="isadmin" class="form-check-input"><label class="form-check-label">Admin</label></div>
    <button class="btn btn-primary">Add</button>
    <a href="users.php" class="btn <?php echo $theme==='dark'?'btn-outline-light':'btn-secondary'; ?>">Back</a>
</form>
</div>
</body>
</html>
