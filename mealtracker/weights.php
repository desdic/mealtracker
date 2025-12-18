<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include 'db.php';
require_once 'user_preferences.php';
require_once("logging.php");

$user_id = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $user_id);
$theme = $preferences['theme'];
$dateformat = $preferences['dateformat'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $weight = $_POST['weight'];
    $created = $_POST['created'] ?? date($dateformat);

	try {
		if ($id) {
			$stmt = $pdo->prepare("UPDATE weighttrack SET weight = ?, created = ? WHERE id = ? AND userid=?");
			$stmt->execute([$weight, $created, $id, $user_id]);
		} else {
			$stmt = $pdo->prepare("INSERT INTO weighttrack (weight, created, userid) VALUES (?, ?, ?)");
			$stmt->execute([$weight, $created, $user_id]);
		}
		header("Location: weights.php");
		exit;
	} catch (PDOException $e) {
		log_error("failed to add/update weight: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
}

if (isset($_GET['delete'])) {
	try {
		$id = $_GET['delete'];
		$stmt = $pdo->prepare("DELETE FROM weighttrack WHERE id = ? AND userid = ?");
		$stmt->execute([$id, $user_id]);
		header("Location: weights.php");
		exit;
	} catch (PDOException $e) {
		log_error("failed delete weight: " . $e->getMessage());
		http_response_code(500);
		die("error");
	}
}

try {
	$stmt = $pdo->prepare("SELECT * FROM weighttrack WHERE userid = ? ORDER BY created DESC");
	$stmt->execute([$user_id]);
	$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	log_error("failed to get weight: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

$dates = [];
$weights = [];
foreach (array_reverse($entries) as $row) {
    $dates[] = $row['created'];
    $weights[] = $row['weight'];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <title>Weight</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <script src="assets/chart.js"></script>
    <style>
        /* Make table background transparent to show stripes */
        .card-body.table-responsive {
            background-color: transparent;
        }
    </style>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">

<div class="container py-4">

<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Weight</span>
    <a href="add_dish.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>


    <!-- Chart Card -->
    <div class="card mb-4 <?php echo $theme === 'dark' ? 'text-light bg-secondary border-secondary' : ''; ?>">
        <div class="card-body">
            <h5 class="card-title">Weight</h5>
            <canvas id="weightChart" height="200"></canvas>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card mb-4 <?php echo $theme === 'dark' ? 'text-light bg-secondary border-secondary' : ''; ?>">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="id" id="entryId">
                <input type="hidden" name="action" value="save">

                <div class="col-12 col-md-4">
                    <input type="date" name="created" id="dateInput" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                    <input type="number" step="0.1" name="weight" id="weightInput" class="form-control" placeholder="Weight" required>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">Add weight</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card <?php echo $theme === 'dark' ? 'text-light bg-secondary border-secondary' : ''; ?>">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle <?php echo $theme === 'dark' ? 'table-dark' : ''; ?>">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $row): ?>
                    <tr>
                        <td><?= date($dateformat, strtotime($row['created'])); ?></td>
                        <td><?= htmlspecialchars($row['weight']); ?> kg</td>
                        <td>
                            <button class="btn btn-sm btn-secondary editBtn"
                                    data-id="<?= $row['id'] ?>"
                                    data-weight="<?= $row['weight'] ?>"
                                    data-created="<?= $row['created'] ?>">Edit</button>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content <?php echo $theme === 'dark' ? 'bg-dark text-light' : ''; ?>">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" <?php echo $theme === 'dark' ? 'data-bs-theme="dark"' : ''; ?>></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this weight entry?
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
// Edit button functionality
document.querySelectorAll('.editBtn').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.getAttribute('data-id');
        const weight = button.getAttribute('data-weight');
        const created = button.getAttribute('data-created');

        document.getElementById('entryId').value = id;
        document.getElementById('weightInput').value = weight;
        document.getElementById('dateInput').value = created;
        document.getElementById('submitBtn').textContent = 'Update Weight';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

// Chart.js setup
const ctx = document.getElementById('weightChart').getContext('2d');
const weightChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Weight (kg)',
            data: <?= json_encode($weights) ?>,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.3,
            fill: true,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: { title: { display: true, text: 'Date' } },
            y: { title: { display: true, text: 'Weight (kg)' }, beginAtZero: false }
        }
    }
});
</script>

</body>
</html>

