<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../config/db.php');
include('../includes/auth.php');
include('../includes/header.php');

$message = ''; // <--- initialize to avoid "Undefined variable" notice

// Handle approve/reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    $req = $conn->query("SELECT * FROM requests WHERE id=$id")->fetch_assoc();

    if ($req) {
        $dept = $req['department_name'];
        $sheetType = $req['sheet_type'];
        $qty = $req['quantity'];

        if ($action == 'approve') {
            // Check stock
            $stock = $conn->query("SELECT * FROM inventory WHERE sheet_type='$sheetType' AND location='central'")->fetch_assoc();
            if ($stock && $stock['quantity'] >= $qty) {
                $conn->query("UPDATE inventory SET quantity=quantity-$qty WHERE id=".$stock['id']);
                $check = $conn->query("SELECT * FROM inventory WHERE sheet_type='$sheetType' AND department_name='$dept' AND location='department'");
                if ($check->num_rows > 0) {
                    $conn->query("UPDATE inventory SET quantity=quantity+$qty WHERE sheet_type='$sheetType' AND department_name='$dept'");
                } else {
                    $conn->query("INSERT INTO inventory (sheet_type, quantity, location, department_name)
                                  VALUES ('$sheetType', '$qty', 'department', '$dept')");
                }
                $conn->query("UPDATE requests SET status='approved', date_processed=NOW() WHERE id=$id");
                $message = "<p class='success'>Request approved!</p>";
            } else {
                $message = "<p class='error'>Not enough stock!</p>";
            }
        } 
        elseif ($action == 'reject') {
            if (!isset($_POST['rejection_cause'])) {
                ?>
                <div class="glass-container">
                    <h3>Enter Rejection Cause for Request ID <?= $id ?></h3>
                    <form method="POST" style="text-align:center;">
                        <textarea name="rejection_cause" placeholder="Enter reason..." required
                                  style="width:60%; height:80px; border-radius:6px; padding:10px; background: rgba(255,255,255,0.1); color:white;"></textarea><br><br>
                        <button type="submit" class="btn-primary">Submit</button>
                    </form>
                </div>
                <?php
                include('../includes/footer.php');
                exit;
            } else {
                $cause = $conn->real_escape_string($_POST['rejection_cause']);
                $conn->query("UPDATE requests SET status='rejected', date_processed=NOW() WHERE id=$id");
                $insert = $conn->query("INSERT INTO rejections (request_id, rejection_cause, rejected_by)
                                        VALUES ($id, '$cause', '".$_SESSION['user']['name']."')");
                if ($insert) {
                    $message = "<p class='success'>Request rejected! Reason saved</p>";
                } else {
                    $message = "<p class='error'>Request rejected but reason not saved: ".$conn->error."</p>";
                }
            }
        }
    }
}

// Get all requests
$result = $conn->query("SELECT * FROM requests ORDER BY date_requested DESC");
?>

<div class="report-container">
    <div class="message-box"><?= $message ?></div>
    <h2>📄 Department Requests</h2>

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Department</th>
                <th>Sheet Type</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Date Requested</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td><?= ucfirst($row['sheet_type']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= ucfirst($row['status']) ?></td>
                        <td><?= $row['date_requested'] ?></td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <a href="?action=approve&id=<?= $row['id'] ?>" class="btn-primary" style="margin-right:5px;">Approve</a>
                                <a href="?action=reject&id=<?= $row['id'] ?>" class="btn-primary">Reject</a>
                            <?php else: ?>
                                <?= ucfirst($row['status']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>



<?php include('../includes/footer.php'); ?>
