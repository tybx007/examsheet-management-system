<?php
include('../includes/auth.php');
include('../config/db.php');
include('../includes/header.php');

if ($_SESSION['user']['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Validate request ID
if (!isset($_GET['id'])) {
    echo "<div class='report-container'><p class='error'>Invalid request ID.</p></div>";
    include('../includes/footer.php');
    exit;
}

$id = intval($_GET['id']);
$req = $conn->query("SELECT * FROM requests WHERE id=$id")->fetch_assoc();

if (!$req) {
    echo "<div class='report-container'><p class='error'>Request not found.</p></div>";
    include('../includes/footer.php');
    exit;
}

$dept = $req['department_name'];
$type = $req['sheet_type'];
$qty  = $req['quantity'];
$status = $req['status'];

// Handle approval/rejection
if (isset($_POST['approve'])) {
    $stock = $conn->query("SELECT * FROM inventory WHERE sheet_type='$type' AND location='central'")->fetch_assoc();
    if ($stock && $stock['quantity'] >= $qty) {
        $conn->query("UPDATE inventory SET quantity=quantity-$qty WHERE id=".$stock['id']);

        $check = $conn->query("SELECT * FROM inventory WHERE sheet_type='$type' AND department_name='$dept'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE inventory SET quantity=quantity+$qty WHERE sheet_type='$type' AND department_name='$dept'");
        } else {
            $conn->query("INSERT INTO inventory (sheet_type, quantity, location, department_name)
                          VALUES ('$type', '$qty', 'department', '$dept')");
        }

        $conn->query("UPDATE requests SET status='approved', date_processed=NOW() WHERE id=$id");
        echo "<div class='report-container'><p class='success'>Request approved successfully!</p></div>";
    } else {
        echo "<div class='report-container'><p class='error'>Not enough stock in central inventory!</p></div>";
    }
}

if (isset($_POST['reject'])) {
    $conn->query("UPDATE requests SET status='rejected', date_processed=NOW() WHERE id=$id");
    echo "<div class='report-container'><p class='error'>Request rejected.</p></div>";
}
?>

<div class="report-container">
    <div class="report-box">
        <h2>Approve or Reject Request</h2>

        <table class="report-table">
            <tr><th>Request ID</th><td><?= $req['id'] ?></td></tr>
            <tr><th>Department</th><td><?= $req['department_name'] ?></td></tr>
            <tr><th>Sheet Type</th><td><?= $req['sheet_type'] ?></td></tr>
            <tr><th>Quantity</th><td><?= $req['quantity'] ?></td></tr>
            <tr><th>Status</th><td><?= ucfirst($req['status']) ?></td></tr>
            <tr><th>Date Requested</th><td><?= $req['date_requested'] ?></td></tr>
        </table>

        <?php if ($status == 'pending'): ?>
        <form method="POST" class="report-form">
            <button type="submit" name="approve" class="btn btn-approve">Approve</button>
            <button type="submit" name="reject" class="btn btn-reject">Reject</button>
        </form>
        <?php else: ?>
            <p>Status: <strong><?= ucfirst($status) ?></strong></p>
        <?php endif; ?>

        <p><a href="requests.php" class="back-link">← Back to Requests</a></p>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
