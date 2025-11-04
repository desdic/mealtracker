<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
$users = $pdo->query("SELECT * FROM user")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Users</span>
    <a href="add_user.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>
<div class="container">
  <ul class="list-group">
    <?php foreach($users as $u): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['firstname'].' '.$u['lastname']); ?>)
      <div>
        <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn btn-sm">✏️</a>
        <a href="delete_user.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">×</a>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
</body>
</html>

