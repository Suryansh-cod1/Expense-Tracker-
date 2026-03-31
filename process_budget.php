<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $monthly_limit = floatval($_POST['monthly_limit'] ?? 0);
    $month = date('F');
    $year = date('Y');

    if (empty($category) || $monthly_limit <= 0) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Please enter a valid category and limit.'];
        header('Location: budget.php'); exit;
    }

    $stmt = $conn->prepare("INSERT INTO budgets (category, monthly_limit, month, year) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE monthly_limit=VALUES(monthly_limit)");
    $stmt->bind_param("sdsi", $category, $monthly_limit, $month, $year);
    $stmt->execute();
    $_SESSION['flash'] = ['type'=>'success','msg'=>"Budget for $category set to ₹$monthly_limit for $month $year."];
}
header('Location: budget.php');
exit;
?>
