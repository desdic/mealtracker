<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

require 'db.php';

$userid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT d.date, 
           SUM(ROUND(i.amount / f.unit * f.kcal, 1)) AS kcal,
           SUM(ROUND(i.amount / f.unit * f.protein, 1)) AS protein,
           SUM(ROUND(i.amount / f.unit * f.carbs, 1)) AS carbs,
           SUM(ROUND(i.amount / f.unit * f.fat, 1)) AS fat
    FROM mealitems i
    JOIN mealday d ON d.id = i.mealday
    JOIN food f ON f.id = i.fooditem
    WHERE d.userid = ?
    GROUP BY d.date
    ORDER BY d.date
");
$stmt->execute([$userid]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$kcal = [];
$protein = [];
$carbs = [];
$fat = [];

foreach ($data as $row) {
    $dates[] = $row['date'];
    $kcal[] = (float)$row['kcal'];
    $protein[] = (float)$row['protein'];
    $carbs[] = (float)$row['carbs'];
    $fat[] = (float)$row['fat'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nutrition Chart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="container mt-3">

  <h3>Nutrition Over Time</h3>

  <div class="mb-3">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <canvas id="nutritionChart"></canvas>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('nutritionChart').getContext('2d');
const nutritionChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            { label: 'Calories', data: <?= json_encode($kcal) ?>, borderColor: 'red', backgroundColor: 'rgba(255,0,0,0.2)', tension: 0.2 }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        interaction: { mode: 'nearest', axis: 'x', intersect: false },
        scales: {
            x: { title: { display: true, text: 'Date' } },
            y: { beginAtZero: true, title: { display: true, text: 'Amount' } }
        }
    }
});
</script>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>

