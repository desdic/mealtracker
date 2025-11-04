<?php
session_start();
if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');

require 'db.php';

$id = $_GET['id'] ?? null;
$user = ['username'=>'', 'firstname'=>'', 'lastname'=>'', 'disabled'=>0, 'isadmin'=>0];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $password = $_POST['password'];
    $disabled = isset($_POST['disabled']) ? 1 : 0;
    $isadmin = isset($_POST['isadmin']) ? 1 : 0;

    if ($id) {
        if ($password) {
            $checksum = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET username=?, firstname=?, lastname=?, checksum=?, disabled=?, isadmin=? WHERE id=?");
            $stmt->execute([$username, $firstname, $lastname, $checksum, $disabled, $isadmin, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE user SET username=?, firstname=?, lastname=?, disabled=?, isadmin=? WHERE id=?");
            $stmt->execute([$username, $firstname, $lastname, $disabled, $isadmin, $id]);
        }
    } else {
        $checksum = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (username, firstname, lastname, checksum, disabled, isadmin) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$username, $firstname, $lastname, $checksum, $disabled, $isadmin]);
    }

    header("Location: users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $id ? 'Edit' : 'Add' ?> User</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-4 text-center"><?= $id ? 'Edit' : 'Add' ?> User</h1>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Firstname</label>
                            <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lastname</label>
                            <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="disabled" id="disabled" <?= $user['disabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="disabled">Disabled</label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="isadmin" id="isadmin" <?= $user['isadmin'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isadmin">Admin</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary"><?= $id ? 'Update' : 'Add' ?> User</button>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>

