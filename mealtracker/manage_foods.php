<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html');
  exit;
}
if (!isset($_SESSION['isadmin']) || !$_SESSION['isadmin']) die('Access denied');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administer fødevarer</title>
  <link href="assets/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .card { border-radius: 1rem; }
    .modal-content { border-radius: 1rem; }
    .table th, .table td { vertical-align: middle; }
    @media (max-width: 768px) {
      .table-responsive { font-size: 0.9rem; }
    }
  </style>
</head>
<body class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h4 class="mb-2 mb-md-0">Fødevarer</h4>
    <div>
      <a href="index.php" class="btn btn-secondary me-2">&laquo; Back</a>
      <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">➕ Tilføj</a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Kcal</th>
            <th>Protein</th>
            <th>Kulhydrater</th>
            <th>Fedt</th>
            <th>Unit</th>
            <th style="width:90px;">Actions</th>
          </tr>
        </thead>
        <tbody id="food-table-body"></tbody>
      </table>
    </div>
  </div>

  <!-- Add Food Modal -->
  <div class="modal fade" id="addFoodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Food</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Name</label><input id="food-title" class="form-control"></div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Kcal</label><input type="number" id="food-kcal" class="form-control"></div>
            <div class="col-6"><label class="form-label">Protein (g)</label><input type="number" id="food-protein" class="form-control"></div>
          </div>
          <div class="row g-2 mt-2">
            <div class="col-6"><label class="form-label">Kulhydrater (g)</label><input type="number" id="food-carbs" class="form-control"></div>
            <div class="col-6"><label class="form-label">Fedt (g)</label><input type="number" id="food-fat" class="form-control"></div>
          </div>
          <div class="mt-2"><label class="form-label">Unit</label><input type="number" id="food-unit" class="form-control" placeholder="e.g. gram or piece"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" id="saveFoodBtn">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Food Modal -->
  <div class="modal fade" id="editFoodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Food</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id">
          <div class="mb-3"><label class="form-label">Name</label><input id="edit-title" class="form-control"></div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Kcal</label><input type="number" id="edit-kcal" class="form-control"></div>
            <div class="col-6"><label class="form-label">Protein (g)</label><input type="number" id="edit-protein" class="form-control"></div>
          </div>
          <div class="row g-2 mt-2">
            <div class="col-6"><label class="form-label">Kulhydrater (g)</label><input type="number" id="edit-carbs" class="form-control"></div>
            <div class="col-6"><label class="form-label">Fedt (g)</label><input type="number" id="edit-fat" class="form-control"></div>
          </div>
          <div class="mt-2"><label class="form-label">Unit</label><input id="edit-unit" class="form-control"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" id="updateFoodBtn">Update</button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/bootstrap.bundle.min.js"></script>
  <script>
  async function loadFoods() {
    const res = await fetch('get_foods.php');
    const foods = await res.json();
    const tbody = document.getElementById('food-table-body');
    tbody.innerHTML = '';
    foods.forEach(f => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${f.title}</td>
        <td>${f.kcal}</td>
        <td>${f.protein}</td>
        <td>${f.carbs}</td>
        <td>${f.fat}</td>
        <td>${f.unit}</td>
        <td>
          <button class="btn btn-sm btn-warning me-1" onclick="openEdit(${f.id})">✏️</button>
          <button class="btn btn-sm btn-danger" onclick="deleteFood(${f.id})">🗑️</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // Add new food
  document.getElementById('saveFoodBtn').addEventListener('click', async () => {
    const title = document.getElementById('food-title').value;
    const kcal = document.getElementById('food-kcal').value;
    const protein = document.getElementById('food-protein').value;
    const carbs = document.getElementById('food-carbs').value;
    const fat = document.getElementById('food-fat').value;
    const unit = document.getElementById('food-unit').value;

    if (!title || !kcal || !protein || !carbs || !fat || !unit) {
      alert('Please fill all fields');
      return;
    }

    const res = await fetch('add_food.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `title=${encodeURIComponent(title)}&kcal=${kcal}&protein=${protein}&carbs=${carbs}&fat=${fat}&unit=${encodeURIComponent(unit)}`
    });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('addFoodModal')).hide();
      loadFoods();
    } else {
      alert('Error adding food.');
    }
  });

  // Open edit modal
  async function openEdit(id) {
    const res = await fetch(`get_food.php?id=${id}`);
    const f = await res.json();
    document.getElementById('edit-id').value = f.id;
    document.getElementById('edit-title').value = f.title;
    document.getElementById('edit-kcal').value = f.kcal;
    document.getElementById('edit-protein').value = f.protein;
    document.getElementById('edit-carbs').value = f.carbs;
    document.getElementById('edit-fat').value = f.fat;
    document.getElementById('edit-unit').value = f.unit;
    new bootstrap.Modal(document.getElementById('editFoodModal')).show();
  }

  // Update food
  document.getElementById('updateFoodBtn').addEventListener('click', async () => {
    const id = document.getElementById('edit-id').value;
    const title = document.getElementById('edit-title').value;
    const kcal = document.getElementById('edit-kcal').value;
    const protein = document.getElementById('edit-protein').value;
    const carbs = document.getElementById('edit-carbs').value;
    const fat = document.getElementById('edit-fat').value;
    const unit = document.getElementById('edit-unit').value;

    const res = await fetch('update_food.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}&title=${encodeURIComponent(title)}&kcal=${kcal}&protein=${protein}&carbs=${carbs}&fat=${fat}&unit=${encodeURIComponent(unit)}`
    });
    const data = await res.json();
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('editFoodModal')).hide();
      loadFoods();
    } else {
      alert('Error updating food.');
    }
  });

  // Delete food
  async function deleteFood(id) {
    if (!confirm('Are you sure you want to delete this food?')) return;
    const res = await fetch('delete_food.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}`
    });
    const data = await res.json();
    if (data.success) {
      loadFoods();
    } else {
      alert('Error deleting food.');
    }
  }

  loadFoods();
  </script>
</body>
</html>

