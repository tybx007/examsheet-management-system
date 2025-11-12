<?php
// session_start();
$user = $_SESSION['user'] ?? null;

// ✅ Automatically detect correct CSS path
$cssPath = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ||
            strpos($_SERVER['REQUEST_URI'], '/department/') !== false)
            ? '../public/css/style.css'
            : 'public/css/style.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Sheet Management System</title>
    <link rel="stylesheet" href="<?= $cssPath ?>">
</head>
<body>

<header class="header-container">
    <h1 class="site-title">Exam Sheet Management System</h1>
    
    <?php if ($user): ?>
        <nav class="navbar">
            <a href="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ||
                           strpos($_SERVER['REQUEST_URI'], '/department/') !== false)
                           ? '../index.php' : 'index.php'; ?>">Home</a>

            <?php if ($user['role'] == 'admin'): ?>
                <a href="../admin/dashboard.php">Dashboard</a>
                <a href="../admin/inventory.php">Inventory</a>
                <a href="../admin/requests.php">Requests</a>
                <a href="../admin/user_list.php">Userlist</a>
                <a href="../admin/monthly_report.php">Report</a>
            <?php else: ?>
                <a href="../department/dashboard.php">Dashboard</a>
                <a href="../department/inventory.php">Inventory</a>
                <a href="../department/request_form.php">Request Sheets</a>
                <a href="../department/return_sheets.php">Return Sheets</a>
            <?php endif; ?>

            <a href="../logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>

<hr>
