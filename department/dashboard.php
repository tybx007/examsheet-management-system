<?php include('../includes/auth.php'); include('../config/db.php'); include('../includes/header.php'); ?>
<?php if($_SESSION['user']['role']!='department'){ header('Location: ../login.php'); exit; } ?>
<div class="report-container"> 
    <div class="report-box">
        <h2>Department Dashboard (<?= $_SESSION['user']['department_name'] ?>)</h2>

        <?php
        $dept=$_SESSION['user']['department_name'];
        $stock=$conn->query("SELECT SUM(quantity) AS total FROM inventory WHERE department_name='$dept'")->fetch_assoc()['total'];
        $pending=$conn->query("SELECT COUNT(*) AS total FROM requests WHERE department_name='$dept' AND status='pending'")->fetch_assoc()['total'];
        ?>

        <p><b>Department Stock:</b> <?= $stock ?: 0 ?></p>
        <p><b>Pending Requests:</b> <?= $pending ?></p>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
