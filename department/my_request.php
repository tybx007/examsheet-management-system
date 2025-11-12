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
    <h2>📄 My Requests</h2>

    <?php
    $dept = $_SESSION['user']['department_name'];
    $res = $conn->query("SELECT * FROM requests WHERE department_name='$dept' ORDER BY date_requested DESC");
    ?>

    <table>
        <thead>
            <tr>
                <th>Sheet Type</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Date Requested</th>
            </tr>
        </thead>
        <tbody>
            <?php if($res->num_rows > 0): ?>
                <?php while($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords($r['sheet_type'])) ?></td>
                        <td><?= $r['quantity'] ?></td>
                        <td><?= ucfirst($r['status']) ?></td>
                        <td><?= $r['date_requested'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
/* Same style as report page */
.report-container {
    max-width: 1100px;
    margin: 40px auto;
    background: rgba(10, 168, 207, 0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 40px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    color: #f0f2f4;
    text-align: center;
}

.report-container h2 {
    margin-bottom: 25px;
    color: #f0f2f4;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
    background: rgba(255,255,255,0.05);
    color: #f0f2f4;
    border-radius: 6px;
    overflow: hidden;
}

th, td {
    border: 1px solid rgba(255,255,255,0.2);
    padding: 10px;
    text-align: center;
}

th {
    background-color: rgba(255,255,255,0.15);
    font-weight: 600;
}

tbody tr:hover td {
    background-color: rgba(255,255,255,0.1);
}
</style>

<?php include('../includes/footer.php'); ?>
