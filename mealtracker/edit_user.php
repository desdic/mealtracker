<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

include('db.php');
require_once("logging.php");

$id=$_GET['id'];
$stmt=$pdo->prepare("SELECT * FROM user WHERE id=?");
$stmt->execute([$id]); 
$user=$stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']=='POST'){
    $checksum = $_POST['checksum'];
    if(!empty($checksum)){
        $hash = password_hash($checksum, PASSWORD_DEFAULT);
    } else {
        $hash = $user['checksum']; // keep existing hash
    }

	try {
		$stmt=$pdo->prepare("UPDATE user SET username=?, firstname=?, lastname=?, checksum=?, disabled=?, isadmin=? WHERE id=?");
		$stmt->execute([
			$_POST['username'],
			$_POST['firstname'],
			$_POST['lastname'],
			$hash,
			isset($_POST['disabled'])?1:0,
			isset($_POST['isadmin'])?1:0,
			$id
		]);
		header("Location: users.php"); 
		exit;
	} catch (PDOException $e) {
		log_error("failed updating user: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit User</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-3">
<h3>Edit User</h3>
<form method="post">
  <div class="mb-3">
    <label>Username</label>
    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
  </div>
  <div class="mb-3">
    <label>First Name</label>
    <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
  </div>
  <div class="mb-3">
    <label>Last Name</label>
    <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
  </div>
  <div class="mb-3">
    <label>Password (leave blank to keep current)</label>
    <input type="password" name="checksum" class="form-control" value="">
  </div>
  <div class="form-check mb-3">
    <input type="checkbox" name="disabled" class="form-check-input" <?php echo $user['disabled']?'checked':''; ?>>
    <label class="form-check-label">Disabled</label>
  </div>
  <div class="form-check mb-3">
    <input type="checkbox" name="isadmin" class="form-check-input" <?php echo $user['isadmin']?'checked':''; ?>>
    <label class="form-check-label">Admin</label>
  </div>
  <button class="btn btn-primary">Save</button>
  <a href="users.php" class="btn btn-secondary">Back</a>
</form>
</div>
</body>
</html>

