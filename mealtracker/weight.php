<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $weight = $_POST['weight'];
    $created = $_POST['created'] ?? date('Y-m-d');

    if ($id) {
        $stmt = $pdo->prepare("UPDATE weighttrack SET weight = ?, created = ? WHERE id = ? and userid=?");
        $stmt->execute([$weight, $created, $id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO weighttrack (weight, created, userid) VALUES (?, ?, ?)");
        $stmt->execute([$weight, $created, $user_id]);
    }
    header("Location: weight.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM weighttrack WHERE id = ? and userid = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM weighttrack WHERE userid = ? ORDER BY created ASC");
$stmt->execute([$user_id]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$weights = [];
foreach ($entries as $row) {
    $dates[] = $row['created'];
    $weights[] = $row['weight'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vægt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <script src="assets/chart.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4 text-center">Vægt</h1>

	<div>
	<a href="index.php" class="btn btn-primary mb-3">⬅ Tilbage</a>
	</div>

    <!-- Weight Progress Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Vægt</h5>
            <canvas id="weightChart" height="200"></canvas>
        </div>
    </div>

    <!-- Add / Edit Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="id" id="entryId">
                <input type="hidden" name="action" value="save">
                
                <div class="col-12 col-md-4">
                    <input type="date" name="created" id="dateInput" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                    <input type="number" step="0.1" name="weight" id="weightInput" class="form-control" placeholder="Vægt" required>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">Tilføj vægt</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Dato</th>
                        <th>Vægt (kg)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['created']) ?></td>
                        <td><?= htmlspecialchars($row['weight']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-secondary editBtn"
                                    data-id="<?= $row['id'] ?>"
                                    data-weight="<?= $row['weight'] ?>"
                                    data-created="<?= $row['created'] ?>">Rediger</button>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Er du sikker?')">Slet</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

