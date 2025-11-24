<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}

require 'db.php';
require_once 'user_preferences.php';
require_once("logging.php");

$userid = $_SESSION['user_id'];
$preferences = getUserPreferences($pdo, $userid);
$theme = $preferences['theme'];

try {
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
} catch (PDOException $e) {
	log_error("failed getting nutritions: " . $e->getMessage());
	http_response_code(500);
	die("error");
}

$dates = [];
$kcal = [];

// We will calculate percentages based on caloric contribution:
// Protein = 4 kcal/g, Carbs = 4 kcal/g, Fat = 9 kcal/g
$proteinPct = [];
$carbsPct = [];
$fatPct = [];

foreach ($data as $row) {
    $dates[] = $row['date'];
    $kcal[] = (float)$row['kcal'];

    $p_g = (float)$row['protein'];
    $c_g = (float)$row['carbs'];
    $f_g = (float)$row['fat'];

    $p_cal = $p_g * 4;
    $c_cal = $c_g * 4;
    $f_cal = $f_g * 9;
    
    $total_macro_cal = $p_cal + $c_cal + $f_cal;

    if ($total_macro_cal > 0) {
        $proteinPct[] = round(($p_cal / $total_macro_cal) * 100, 1);
        $carbsPct[] = round(($c_cal / $total_macro_cal) * 100, 1);
        $fatPct[] = round(($f_cal / $total_macro_cal) * 100, 1);
    } else {
        $proteinPct[] = 0;
        $carbsPct[] = 0;
        $fatPct[] = 0;
    }
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

    <h5 class="mb-2">Calories</h5>
    <div class="card mb-4 <?php echo $theme === 'dark' ? 'bg-secondary text-light' : ''; ?> shadow-sm">
        <div class="card-body">
          <canvas id="nutritionChart"></canvas>
        </div>
    </div>

    <h5 class="mb-2">Macros Breakdown (%)</h5>
    <div class="card mb-3 <?php echo $theme === 'dark' ? 'bg-secondary text-light' : ''; ?> shadow-sm">
        <div class="card-body">
          <canvas id="macroChart"></canvas>
        </div>
    </div>

</div>

<script>
// -------------------------
// CALORIE CHART
// -------------------------
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
                title: { display: true, text: 'Kcal', color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' },
                ticks: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }
            }
        }
    }
});

// -------------------------
// MACRO CHART (%)
// -------------------------
const ctxMacro = document.getElementById('macroChart').getContext('2d');
const macroChart = new Chart(ctxMacro, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            { 
                label: 'Protein (%)', 
                data: <?= json_encode($proteinPct) ?>, 
                borderColor: 'rgba(75, 192, 192, 1)', // Greenish
                backgroundColor: 'rgba(75, 192, 192, 0.2)', 
                tension: 0.2 
            },
            { 
                label: 'Carbs (%)', 
                data: <?= json_encode($carbsPct) ?>, 
                borderColor: 'rgba(54, 162, 235, 1)', // Blueish
                backgroundColor: 'rgba(54, 162, 235, 0.2)', 
                tension: 0.2 
            },
            { 
                label: 'Fat (%)', 
                data: <?= json_encode($fatPct) ?>, 
                borderColor: 'rgba(255, 206, 86, 1)', // Yellowish
                backgroundColor: 'rgba(255, 206, 86, 0.2)', 
                tension: 0.2 
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }, position: 'top' },
            tooltip: { 
                mode: 'index', 
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += context.parsed.y + '%';
                        }
                        return label;
                    }
                }
            }
        },
        interaction: { mode: 'nearest', axis: 'x', intersect: false },
        scales: {
            x: { 
                title: { display: true, text: 'Date', color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' },
                ticks: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }
            },
            y: { 
                beginAtZero: true, 
                max: 100, // Fix scale to 100%
                title: { display: true, text: 'Percentage', color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' },
                ticks: { color: '<?php echo $theme === 'dark' ? "#fff" : "#000"; ?>' }
            }
        }
    }
});
</script>

<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>
