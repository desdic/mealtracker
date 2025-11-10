<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userid = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username, firstname, lastname FROM user WHERE id = ?");
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($firstname === '' || $lastname === '') {
        $message = '<div class="alert alert-danger">First and last name cannot be empty.</div>';
    } else {
        // Update name
        $stmt = $pdo->prepare("UPDATE user SET firstname = ?, lastname = ? WHERE id = ?");
        $stmt->execute([$firstname, $lastname, $userid]);

        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $message = '<div class="alert alert-danger">Passwords do not match.</div>';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET checksum = ? WHERE id = ?");
                $stmt->execute([$hash, $userid]);
                $message = '<div class="alert alert-success">Password updated successfully.</div>';
            }
        }

        if (empty($message)) {
            $message = '<div class="alert alert-success">Profile updated successfully.</div>';
        }
    }

    // Refresh user data
    $stmt = $pdo->prepare("SELECT username, firstname, lastname FROM user WHERE id = ?");
    $stmt->execute([$userid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Preferences</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
    <div class="container-fluid">
        <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
        <span class="navbar-brand mx-auto">User Preferences</span>
    </div>
</nav>

<div class="container" style="max-width: 500px;">
    <?php echo $message; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3"><?php echo htmlspecialchars($user['username']); ?></h5>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                </div>
                <hr>
                <h6 class="text-muted">Change Password</h6>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">Save Changes</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>

