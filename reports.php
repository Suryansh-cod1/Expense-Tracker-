<?php
session_start();
require 'config/db.php';

$from = $_POST['from'] ?? date('Y-m-01');
$to = $_POST['to'] ?? date('Y-m-d');
$report_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE date BETWEEN ? AND ? ORDER BY date DESC");
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total_income = array_sum(array_column(array_filter($all, fn($t) => $t['type']==='income'), 'amount'));
    $total_expense = array_sum(array_column(array_filter($all, fn($t) => $t['type']==='expense'), 'amount'));
    $net_savings = $total_income - $total_expense;

    $cat_breakdown = [];
    foreach ($all as $t) {
        if ($t['type'] === 'expense') {
            $cat_breakdown[$t['category']] = ($cat_breakdown[$t['category']] ?? 0) + $t['amount'];
        }
    }
    $report_data = compact('total_income','total_expense','net_savings','cat_breakdown','all');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Expense Tracker - Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-5 pt-4">
  <h4 class="fw-bold mb-4">Financial Reports</h4>

  <div class="card shadow-sm p-4 mb-4">
    <form method="POST" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">From Date</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">To Date</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>" required>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary w-100">Generate Report</button>
      </div>
    </form>
  </div>

  <?php if ($report_data): ?>
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card text-white bg-success shadow text-center p-3">
          <h6>Total Income</h6>
          <h3>₹<?= number_format($report_data['total_income'],2) ?></h3>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-danger shadow text-center p-3">
          <h6>Total Expense</h6>
          <h3>₹<?= number_format($report_data['total_expense'],2) ?></h3>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-primary shadow text-center p-3">
          <h6>Net Savings</h6>
          <h3>₹<?= number_format($report_data['net_savings'],2) ?></h3>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header bg-dark text-white">Category-Wise Expense Breakdown</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead class="table-secondary">
            <tr><th>Category</th><th>Total Spent</th></tr>
          </thead>
          <tbody>
            <?php foreach ($report_data['cat_breakdown'] as $cat => $amt): ?>
              <tr><td><?= htmlspecialchars($cat) ?></td><td>₹<?= number_format($amt,2) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card shadow-sm mb-5">
      <div class="card-header bg-dark text-white">All Transactions (<?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?>)</div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead class="table-secondary">
            <tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th>Amount</th></tr>
          </thead>
          <tbody>
            <?php foreach ($report_data['all'] as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><span class="badge <?= $row['type']=='income'?'bg-success':'bg-danger' ?>"><?= ucfirst($row['type']) ?></span></td>
                <td class="<?= $row['type']=='income'?'text-success':'text-danger' ?> fw-bold">
                  <?= $row['type']=='income'?'+':'-' ?>₹<?= number_format($row['amount'],2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
