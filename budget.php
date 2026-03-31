<?php
session_start();
require 'config/db.php';

$month = date('F'); $year = date('Y');
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Store alert email in cookie if submitted
if (!empty($_POST['alert_email'])) {
    setcookie('alert_email', $_POST['alert_email'], time() + (86400 * 365), '/');
    $_COOKIE['alert_email'] = $_POST['alert_email'];
}

// Fetch all budgets with current spending
$budgets_res = $conn->query("SELECT b.*, COALESCE(SUM(t.amount),0) as spent FROM budgets b LEFT JOIN transactions t ON t.category=b.category AND t.type='expense' AND MONTH(t.date)=MONTH(CURDATE()) AND YEAR(t.date)=YEAR(CURDATE()) WHERE b.month='$month' AND b.year=$year GROUP BY b.id ORDER BY b.category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Expense Tracker - Budget</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-5 pt-4">
  <h4 class="fw-bold mb-4">Budget Goals — <?= $month ?> <?= $year ?></h4>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Set Budget Form -->
    <div class="col-md-4">
      <div class="card shadow-sm p-4">
        <h6 class="fw-bold mb-3">Set Monthly Budget</h6>
        <form method="POST" action="process_budget.php">
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <?php foreach(['Food','Travel','Health','Entertainment','Education','Utilities','Others'] as $c): ?>
                <option><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Monthly Limit (₹)</label>
            <input type="number" name="monthly_limit" class="form-control" min="1" step="0.01" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Set Budget</button>
        </form>
        <hr>
        <h6 class="fw-bold mb-3">Alert Email</h6>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Email for Budget Alerts</label>
            <input type="email" name="alert_email" class="form-control" value="<?= htmlspecialchars($_COOKIE['alert_email'] ?? '') ?>" placeholder="your@email.com">
            <small class="text-muted">Saved in browser cookie</small>
          </div>
          <button type="submit" class="btn btn-success w-100">Save Email</button>
        </form>
      </div>
    </div>

    <!-- Budget Progress List -->
    <div class="col-md-8">
      <?php if ($budgets_res->num_rows === 0): ?>
        <div class="alert alert-info">No budgets set for this month yet.</div>
      <?php else: ?>
        <?php while ($b = $budgets_res->fetch_assoc()):
          $pct = $b['monthly_limit'] > 0 ? round(($b['spent'] / $b['monthly_limit']) * 100) : 0;
          $bar_class = $pct >= 100 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');
          $display_pct = min($pct, 100);
        ?>
        <div class="card shadow-sm mb-3 p-3">
          <div class="d-flex justify-content-between mb-2">
            <strong><?= htmlspecialchars($b['category']) ?></strong>
            <span>₹<?= number_format($b['spent'],2) ?> / ₹<?= number_format($b['monthly_limit'],2) ?> (<?= $pct ?>%)</span>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-<?= $bar_class ?> progress-bar-striped" style="width: <?= $display_pct ?>%"></div>
          </div>
          <?php if ($pct >= 100): ?>
            <small class="text-danger mt-1">⛔ Budget Exceeded!</small>
          <?php elseif ($pct >= 80): ?>
            <small class="text-warning mt-1">⚠️ Approaching Budget Limit!</small>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
