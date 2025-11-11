<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require_once 'db.php';
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];

// --- Fetch user info ---
$stmt = $pdo->prepare("SELECT username, firstname, lastname FROM user WHERE id = ?");
$stmt->execute([$userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("User not found.");
}

$preferences = getUserPreferences($pdo, $userid);

$dateFormats = [
    'd/m/Y' => 'DD/MM/YYYY',
    'm/d/Y' => 'MM/DD/YYYY',
    'Y/m/d' => 'YYYY/MM/DD',
];

$message_profile = '';
$message_prefs = '';

if (isset($_POST['form_type']) && $_POST['form_type'] === 'profile') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($firstname === '' || $lastname === '') {
        $message_profile = '<div class="alert alert-danger">First and last name cannot be empty.</div>';
    } else {
        $stmt = $pdo->prepare("UPDATE user SET firstname = ?, lastname = ? WHERE id = ?");
        $stmt->execute([$firstname, $lastname, $userid]);

        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $message_profile = '<div class="alert alert-danger">Passwords do not match.</div>';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET checksum = ? WHERE id = ?");
                $stmt->execute([$hash, $userid]);
                $message_profile = '<div class="alert alert-success">Password updated successfully.</div>';
            }
        }

        if (empty($message_profile)) {
            $message_profile = '<div class="alert alert-success">Profile updated successfully.</div>';
        }

        $stmt = $pdo->prepare("SELECT username, firstname, lastname FROM user WHERE id = ?");
        $stmt->execute([$userid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (isset($_POST['form_type']) && $_POST['form_type'] === 'preferences') {
    $new_prefs = [
        'dateformat' => $_POST['dateformat'] ?? 'd/m/Y',
        // 'theme' => $_POST['theme'] ?? 'light'
    ];

    $stmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, preference_key, preference_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
    ");

    foreach ($new_prefs as $key => $value) {
        $stmt->execute([$userid, $key, $value]);
        $preferences[$key] = $value; // update locally too
    }

    $message_prefs = '<div class="alert alert-success">Preferences updated successfully.</div>';
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

<div class="container" style="max-width: 600px;">

    <!-- Profile Info -->
    <?php echo $message_profile; ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Profile Information</h5>
            <form method="post">
                <input type="hidden" name="form_type" value="profile">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                </div>
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
                <button type="submit" class="btn btn-primary w-100">Save Profile</button>
            </form>
        </div>
    </div>

    <!-- Preferences -->
    <?php echo $message_prefs; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Preferences</h5>
            <form method="post">
                <input type="hidden" name="form_type" value="preferences">
                <div class="mb-3">
                    <label class="form-label">Date Format</label>
					<select name="dateformat" class="form-select">
						<?php foreach ($dateFormats as $format => $label): ?>
							<option value="<?php echo $format; ?>" <?php echo ($preferences['dateformat'] === $format) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
                </div>

                <!-- Example of easy expansion -->
                <!--
                <div class="mb-3">
                    <label class="form-label">Theme</label>
                    <select name="theme" class="form-select">
                        <option value="light" <?php echo ($preferences['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo ($preferences['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>
                -->

                <button type="submit" class="btn btn-success w-100">Save Preferences</button>
            </form>
        </div>
    </div>

</div>
</body>
</html>

