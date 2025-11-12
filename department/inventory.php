<?php 
include('../includes/auth.php'); 
include('../config/db.php'); 
include('../includes/header.php'); 

// Only department users
if($_SESSION['user']['role'] != 'department'){ 
    header('Location: ../login.php'); 
    exit; 
} 
?>

<div class="report-container">
        <h2>📦 Department Inventory</h2>
    <div class="report-box">

        <?php
        $dept = $_SESSION['user']['department_name'];
        $res = $conn->query("SELECT * FROM inventory WHERE location='department' AND department_name='$dept'");
        ?>
        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Sheet Type</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res->num_rows > 0): ?>
                        <?php while($r = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucwords($r['sheet_type'])) ?></td>
                                <td><?= $r['quantity'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No inventory available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php include('../includes/footer.php'); ?>
