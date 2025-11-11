<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

require 'db.php';
require_once 'user_preferences.php';

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];

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
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nutrition Chart</title>
<link href="assets/bootstrap.min.css" rel="stylesheet">
<script src="assets/chart.js"></script>
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light text-dark'; ?>">

<div class="container mt-3">


<nav class="navbar navbar-expand-lg <?php echo $theme === 'dark' ? 'navbar-dark bg-dark' : 'navbar-light bg-white'; ?> shadow-sm mb-3">
  <div class="container-fluid">
    <a href="index.php" class="btn btn-secondary">&laquo; Back</a>
    <span class="navbar-brand mx-auto">Nutrition over time</span>
  </div>
</nav>

  <div class="card mb-3 <?php echo $theme === 'dark' ? 'bg-secondary text-light' : ''; ?> shadow-sm">
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
            { 
                label: 'Calories', 
                data: <?= json_encode($kcal) ?>, 
                borderColor: '<?php echo $theme === 'dark' ? "rgba(255, 99, 132, 1)" : "red"; ?>', 
                backgroundColor: '<?php echo $theme === 'dark' ? "rgba(255, 99, 132, 0.2)" : "rgba(255,0,0,0.2)"; ?>', 
                tension: 0.2 
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }, position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        interaction: { mode: 'nearest', axis: 'x', intersect: false },
        scales: {
            x: { 
                title: { display: true, text: 'Date', color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' },
                ticks: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }
            },
            y: { 
                beginAtZero: true, 
                title: { display: true, text: 'Amount', color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' },
                ticks: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }
            }
        }
    }
});
</script>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>

