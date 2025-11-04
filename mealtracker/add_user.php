<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');

if($_SERVER['REQUEST_METHOD']=='POST'){
	$hash = password_hash($_POST['checksum'], PASSWORD_DEFAULT);
    $stmt=$pdo->prepare("INSERT INTO user(username,firstname,lastname,checksum,disabled,isadmin) VALUES(?,?,?,?,?,?)");
    $stmt->execute([$_POST['username'],$_POST['firstname'],$_POST['lastname'],$hash,isset($_POST['disabled'])?1:0,isset($_POST['isadmin'])?1:0]);
    header("Location: users.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add User</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Add User</h3>
<form method="post">
  <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required autocomplete="off"></div>
  <div class="mb-3"><label>First Name</label><input type="text" name="firstname" class="form-control" required autocomplete="off"></div>
  <div class="mb-3"><label>Last Name</label><input type="text" name="lastname" class="form-control" required autocomplete="off"></div>
  <div class="mb-3"><label>Password</label><input type="password" name="checksum" class="form-control" required autocomplete="off"></div>
  <div class="form-check mb-3"><input type="checkbox" name="disabled" class="form-check-input"><label class="form-check-label">Disabled</label></div>
  <div class="form-check mb-3"><input type="checkbox" type="password" name="isadmin" class="form-check-input"><label class="form-check-label">Admin</label></div>
  <button class="btn btn-primary">Add</button>
  <a href="users.php" class="btn btn-secondary">Back</a>
</form>
</div>
</body>
</html>

