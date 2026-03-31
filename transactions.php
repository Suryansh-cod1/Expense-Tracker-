<?php
session_start();
require 'config/db.php';

$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$from_filter = $_GET['from'] ?? '';
$to_filter = $_GET['to'] ?? '';

$sql = "SELECT * FROM transactions WHERE 1=1";
$params = []; $types = '';

if ($category_filter) { $sql .= " AND category=?"; $params[] = $category_filter; $types .= 's'; }
if ($type_filter)     { $sql .= " AND type=?";     $params[] = $type_filter;     $types .= 's'; }
if ($from_filter)     { $sql .= " AND date>=?";    $params[] = $from_filter;     $types .= 's'; }
if ($to_filter)       { $sql .= " AND date<=?";    $params[] = $to_filter;       $types .= 's'; }
$sql .= " ORDER BY date DESC, created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Expense Tracker - Transactions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-5 pt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold">Transactions</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#txModal" onclick="resetModal()">+ Add Transaction</button>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
      <?= htmlspecialchars($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
      <select name="category" class="form-select form-select-sm">
        <option value="">All Categories</option>
        <?php foreach(['Food','Travel','Health','Entertainment','Education','Utilities','Salary','Others'] as $c): ?>
          <option <?= $category_filter==$c?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="type" class="form-select form-select-sm">
        <option value="">All Types</option>
        <option value="income" <?= $type_filter=='income'?'selected':'' ?>>Income</option>
        <option value="expense" <?= $type_filter=='expense'?'selected':'' ?>>Expense</option>
      </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from_filter) ?>"></div>
    <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to_filter) ?>"></div>
    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="transactions.php" class="btn btn-sm btn-outline-secondary w-100">Clear</a></div>
  </form>

  <!-- Table -->
  <div class="card shadow-sm mb-5">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th>Amount</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php while ($row = $transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><span class="badge <?= $row['type']=='income'?'bg-success':'bg-danger' ?>"><?= ucfirst($row['type']) ?></span></td>
            <td class="<?= $row['type']=='income'?'text-success':'text-danger' ?> fw-bold">
              <?= $row['type']=='income'?'+':'-' ?>₹<?= number_format($row['amount'],2) ?>
            </td>
            <td>
              <button class="btn btn-sm btn-warning me-1" onclick="editModal(<?= $row['id'] ?>,'<?= $row['amount'] ?>','<?= addslashes($row['description']) ?>','<?= $row['category'] ?>','<?= $row['type'] ?>','<?= $row['date'] ?>')" data-bs-toggle="modal" data-bs-target="#txModal">Edit</button>
              <form method="POST" action="process_transaction.php" class="d-inline" onsubmit="return confirm('Delete this transaction?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="txModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalTitle">Add Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="process_transaction.php" onsubmit="return validateForm()">
        <div class="modal-body">
          <input type="hidden" name="action" id="formAction" value="add">
          <input type="hidden" name="id" id="txId">
          <div class="mb-3">
            <label class="form-label">Amount (₹)</label>
            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01">
            <div class="text-danger small" id="amountErr"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="description" id="description" class="form-control">
            <div class="text-danger small" id="descErr"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" id="category" class="form-select">
              <?php foreach(['Food','Travel','Health','Entertainment','Education','Utilities','Salary','Others'] as $c): ?>
                <option><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" id="txType" class="form-select">
              <option value="expense">Expense</option>
              <option value="income">Income</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" id="txDate" class="form-control">
            <div class="text-danger small" id="dateErr"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
