<?php
session_start();
include('../includes/header.php');
include('../config/db.php');

// Only department users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'department') {
    header('Location: ../login.php');
    exit;
}

$dept = $_SESSION['user']['department_name'];
$message = "";

// Fetch available sheet types
$types = $conn->query("
    SELECT DISTINCT sheet_type 
    FROM inventory 
    WHERE location='central' 
    AND sheet_type != '' 
    ORDER BY sheet_type ASC
");

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sheet_type = $_POST['sheet_type'];
    $quantity = intval($_POST['quantity']);

    if (!empty($sheet_type) && $quantity > 0) {
        $sql = "INSERT INTO requests (department_name, sheet_type, quantity, status)
                VALUES ('$dept', '$sheet_type', '$quantity', 'pending')";

        if ($conn->query($sql)) {
            $message = "<p class='success'>Request sent successfully!</p>";
        } else {
            $message = "<p class='error'>Error: " . $conn->error . "</p>";
        }
    } else {
        $message = "<p class='error'>Please select a valid sheet type and quantity.</p>";
    }
}

// Fetch department requests
$requests = $conn->query("SELECT * FROM requests WHERE department_name = '$dept' ORDER BY date_requested DESC");

// Fetch rejected requests with cause
$rejections = $conn->query("
    SELECT r.sheet_type, r.quantity, rj.rejection_cause, rj.rejected_by, rj.date_rejected
    FROM rejections rj
    JOIN requests r ON rj.request_id = r.id
    WHERE r.department_name = '$dept'
    ORDER BY rj.date_rejected DESC
");
?>

<div class="report-container">
    <h2>📤 Request Exam Sheets</h2>

    <div class="message-box"><?= $message ?></div>

    <!-- Request Form -->
    <form method="POST" class="auth-form">
        <div class="form-group">
            <label>Sheet Type:</label>
            <select name="sheet_type" required>
                <option value="">-- Select Sheet Type --</option>
                <?php while ($row = $types->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['sheet_type']) ?>">
                        <?= htmlspecialchars(ucwords($row['sheet_type'])) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Quantity:</label>
            <input type="number" name="quantity" min="1" required>
        </div>

        <button type="submit">Send Request</button>
    </form>

    <hr>

    <!-- Requests Table -->
    <h3>My Requests</h3>
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
            <?php if ($requests && $requests->num_rows > 0): ?>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords($row['sheet_type'])) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= ucfirst($row['status']) ?></td>
                        <td><?= $row['date_requested'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <!-- Rejections Table -->
    <h3>Rejections and Causes</h3>
    <table>
        <thead>
            <tr>
                <th>Sheet Type</th>
                <th>Quantity</th>
                <th>Rejection Cause</th>
                <th>Rejected By</th>
                <th>Date Rejected</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rejections && $rejections->num_rows > 0): ?>
                <?php while ($rej = $rejections->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords($rej['sheet_type'])) ?></td>
                        <td><?= $rej['quantity'] ?></td>
                        <td ><?= htmlspecialchars($rej['rejection_cause']) ?></td>
                        <td><?= htmlspecialchars($rej['rejected_by']) ?></td>
                        <td><?= $rej['date_rejected'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No rejections found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>



<?php include('../includes/footer.php'); ?>
