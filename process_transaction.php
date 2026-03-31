<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $type = $_POST['type'] ?? 'expense';
        $date = $_POST['date'] ?? date('Y-m-d');

        if ($amount <= 0 || empty($description) || empty($category) || empty($date)) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'All fields are required and amount must be positive.'];
            header('Location: transactions.php');
            exit;
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO transactions (amount, description, category, type, date) VALUES (?,?,?,?,?)");
            $stmt->bind_param("dssss", $amount, $description, $category, $type, $date);
            $stmt->execute();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Transaction added successfully!'];

            // Check budget alert
            if ($type === 'expense') {
                $month = date('F'); $year = date('Y');
                $b_stmt = $conn->prepare("SELECT monthly_limit FROM budgets WHERE category=? AND month=? AND year=?");
                $b_stmt->bind_param("ssi", $category, $month, $year);
                $b_stmt->execute();
                $b_result = $b_stmt->get_result()->fetch_assoc();
                if ($b_result) {
                    $limit = $b_result['monthly_limit'];
                    $s_stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE category=? AND type='expense' AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())");
                    $s_stmt->bind_param("s", $category);
                    $s_stmt->execute();
                    $spent = $s_stmt->get_result()->fetch_assoc()['total'];
                    if ($spent >= $limit * 0.8) {
                        $alert_email = $_COOKIE['alert_email'] ?? '';
                        if ($alert_email) {
                            $subject = "Budget Alert: $category";
                            $pct = round(($spent/$limit)*100);
                            $message = "Warning! You have spent Rs.$spent in $category which is $pct% of your Rs.$limit budget for $month $year.";
                            $headers = "From: noreply@expensetracker.com";
                            mail($alert_email, $subject, $message, $headers);
                        }
                    }
                }
            }
        } else {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE transactions SET amount=?, description=?, category=?, type=?, date=? WHERE id=?");
            $stmt->bind_param("dssssi", $amount, $description, $category, $type, $date, $id);
            $stmt->execute();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Transaction updated successfully!'];
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Transaction deleted successfully!'];
    }
}
header('Location: transactions.php');
exit;
?>
