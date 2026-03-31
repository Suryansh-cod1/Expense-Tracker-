<?php
session_start();
require 'config/db.php';

// Summary totals
$income_res = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='income'");
$expense_res = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='expense'");
$total_income = $income_res->fetch_assoc()['total'];
$total_expense = $expense_res->fetch_assoc()['total'];
$net_balance = $total_income - $total_expense;

// Recent transactions
$recent = $conn->query("SELECT * FROM transactions ORDER BY date DESC, created_at DESC LIMIT 5");

// Chart data - category pie
$cat_res = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE type='expense' GROUP BY category");
$cat_labels = []; $cat_data = [];
while ($row = $cat_res->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_data[] = (float)$row['total'];
}

// Chart data - monthly bar
$month_res = $conn->query("SELECT DATE_FORMAT(date,'%b %Y') as month, type, SUM(amount) as total FROM transactions GROUP BY DATE_FORMAT(date,'%b %Y'), type ORDER BY MIN(date)");
$months = []; $monthly_income = []; $monthly_expense = [];
$temp = [];
while ($row = $month_res->fetch_assoc()) {
    $temp[$row['month']][$row['type']] = (float)$row['total'];
    $months[$row['month']] = true;
}
foreach (array_keys($months) as $m) {
    $monthly_income[] = $temp[$m]['income'] ?? 0;
    $monthly_expense[] = $temp[$m]['expense'] ?? 0;
}
$month_labels = array_keys($months);

// Chart data - line chart cumulative
$line_res = $conn->query("SELECT date, SUM(amount) as daily FROM transactions WHERE type='expense' GROUP BY date ORDER BY date");
$line_labels = []; $line_data = []; $cumulative = 0;
while ($row = $line_res->fetch_assoc()) {
    $cumulative += $row['daily'];
    $line_labels[] = $row['date'];
    $line_data[] = $cumulative;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Expense Tracker - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-5 pt-4">
  <h4 class="mb-4 fw-bold">Dashboard</h4>

  <!-- Summary Cards -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card text-white bg-success shadow">
        <div class="card-body text-center">
          <h6 class="card-title">Total Income</h6>
          <h3 class="fw-bold">₹<?= number_format($total_income, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white bg-danger shadow">
        <div class="card-body text-center">
          <h6 class="card-title">Total Expenses</h6>
          <h3 class="fw-bold">₹<?= number_format($total_expense, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-white <?= $net_balance >= 0 ? 'bg-primary' : 'bg-warning' ?> shadow">
        <div class="card-body text-center">
          <h6 class="card-title">Net Balance</h6>
          <h3 class="fw-bold">₹<?= number_format($net_balance, 2) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h6 class="text-center text-muted mb-2">Category Breakdown</h6>
        <canvas id="pieChart"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h6 class="text-center text-muted mb-2">Monthly Comparison</h6>
        <canvas id="barChart"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm p-3">
        <h6 class="text-center text-muted mb-2">Spending Trend</h6>
        <canvas id="lineChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Recent Transactions -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-dark text-white">Recent Transactions</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-secondary">
          <tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th>Amount</th></tr>
        </thead>
        <tbody>
          <?php while ($row = $recent->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td>
              <span class="badge <?= $row['type']=='income'?'bg-success':'bg-danger' ?>">
                <?= ucfirst($row['type']) ?>
              </span>
            </td>
            <td class="<?= $row['type']=='income'?'text-success':'text-danger' ?> fw-bold">
              <?= $row['type']=='income'?'+':'-' ?>₹<?= number_format($row['amount'],2) ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const catLabels = <?= json_encode($cat_labels) ?>;
const catData = <?= json_encode($cat_data) ?>;
const monthLabels = <?= json_encode($month_labels) ?>;
const monthlyIncome = <?= json_encode($monthly_income) ?>;
const monthlyExpense = <?= json_encode($monthly_expense) ?>;
const lineLabels = <?= json_encode($line_labels) ?>;
const lineData = <?= json_encode($line_data) ?>;

new Chart(document.getElementById('pieChart'), {
  type: 'pie',
  data: {
    labels: catLabels,
    datasets: [{ data: catData, backgroundColor: ['#198754','#dc3545','#0d6efd','#ffc107','#6f42c1','#20c997','#fd7e14','#6c757d'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
});

new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: monthLabels,
    datasets: [
      { label: 'Income', data: monthlyIncome, backgroundColor: '#198754' },
      { label: 'Expense', data: monthlyExpense, backgroundColor: '#dc3545' }
    ]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: lineLabels,
    datasets: [{ label: 'Cumulative Expense', data: lineData, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3 }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
