<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

include('db.php');
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];

$preferences = getUserPreferences($pdo, $userid);
$dateformat = $preferences['dateformat'];

$stmt = $pdo->prepare("SELECT * FROM weighttrack WHERE userid=? ORDER BY created ASC");
$stmt->execute([$userid]);
$weights = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weightDates = [];
$weightValues = [];
foreach($weights as $w){
	$weightDates[] = date($dateformat, strtotime($w['created']));
    $weightValues[] = $w['weight'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Weight Tracking</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<script src="assets/chart.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-outline-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Weight Tracking</span>
    <a href="add_weight.php" class="btn btn-primary">+ Add</a>
  </div>
</nav>
<div class="container">

  <!-- Chart -->
  <div class="card mb-3 p-3">
    <canvas id="weightChart" height="150"></canvas>
  </div>

  <!-- Weight list -->
  <ul class="list-group">
    <?php foreach($weights as $w): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <?php echo date($dateformat, strtotime($w['created'])); ?>: <?php echo $w['weight']; ?> kg
      <div>
        <a href="edit_weight.php?id=<?php echo $w['id']; ?>" class="btn btn-sm">✏️</a>
        <a href="delete_weight.php?id=<?php echo $w['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this entry?')">×</a>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

<script>
const ctx = document.getElementById('weightChart').getContext('2d');
const weightChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($weightDates); ?>,
        datasets: [{
            label: 'Weight (kg)',
            data: <?php echo json_encode($weightValues); ?>,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            fill: true,
            tension: 0.2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            x: {
                title: { display: true, text: 'Date' },
                ticks: { maxRotation: 45, minRotation: 0 }
            },
            y: {
                title: { display: true, text: 'Weight (kg)' },
                beginAtZero: false
            }
        }
    }
});
</script>
</body>
</html>

