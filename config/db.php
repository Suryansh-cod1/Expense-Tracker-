<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Email config (for PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'your_email@gmail.com');
define('MAIL_PASS', 'your_app_password');
define('MAIL_PORT', 587);
?>
