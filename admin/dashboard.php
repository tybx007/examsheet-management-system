<?php include('../includes/auth.php'); 
include('../config/db.php'); 
include('../includes/header.php'); ?>
<?php if($_SESSION['user']['role']!='admin'){ header('Location: ../login.php'); exit; } ?>
<div class="report-container"> 
    <div class="report-box">
        <h2>Admin Dashboard</h2>

        <?php
        $central = $conn->query("SELECT SUM(quantity) AS total FROM inventory WHERE location='central'")->fetch_assoc()['total'];
        $requests = $conn->query("SELECT COUNT(*) AS total FROM requests WHERE status='pending'")->fetch_assoc()['total'];
        ?>

        <p><b>Central Stock:</b> <?= $central ?: 0 ?></p>
        <p><b>Pending Requests:</b> <?= $requests ?></p>
    </div>

</div>

<?php include('../includes/footer.php'); ?>
