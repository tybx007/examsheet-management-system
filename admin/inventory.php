<?php
include('../includes/auth.php');
include('../config/db.php');
include('../includes/header.php');

if ($_SESSION['user']['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = "";

// Handle Add/Update
if (isset($_POST['add'])) {
    $sheet_type = trim($_POST['sheet_type']);
    $custom_type = trim($_POST['custom_sheet_type']);
    $qty = intval($_POST['quantity']);

    $type = !empty($custom_type) ? strtolower($custom_type) : strtolower($sheet_type);

    if (empty($type)) {
        $message = "<p class='msg error'>Please select or enter a valid sheet type.</p>";
    } elseif ($qty <= 0) {
        $message = "<p class='msg error'>Quantity must be greater than 0.</p>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE sheet_type=? AND location='central'");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $conn->query("UPDATE inventory SET quantity = quantity + $qty WHERE sheet_type='$type' AND location='central'");
            $message = "<p class='msg success'>Updated <strong>" . ucfirst($type) . "</strong> stock (+$qty added).</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO inventory (sheet_type, quantity, location) VALUES (?, ?, 'central')");
            $stmt->bind_param("si", $type, $qty);
            $stmt->execute();
            $message = "<p class='msg success'>Added new sheet type <strong>" . ucfirst($type) . "</strong> with quantity $qty.</p>";
        }
        $stmt->close();
    }
}

// Fetch all sheet types
$types = $conn->query("SELECT DISTINCT sheet_type FROM inventory WHERE location='central' AND sheet_type!='' ORDER BY sheet_type ASC");

// Fetch inventory list
$res = $conn->query("SELECT * FROM inventory WHERE location='central' AND sheet_type!='' ORDER BY sheet_type ASC");
?>

<div class="report-container">
    <div class="report-box centered-box">
        <h2>Central Inventory Management</h2>

        <?= $message ?>

        <!-- Centered Form -->
        <form method="POST" class="auth-form">
            <label>Select Existing Type:</label>
            <select name="sheet_type">
                <option value="">-- Choose existing type --</option>
                <?php while ($row = $types->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['sheet_type']) ?>">
                        <?= htmlspecialchars(ucwords($row['sheet_type'])) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br><br>

            <label>Or Add New Type:</label>
            <input type="text" name="custom_sheet_type" placeholder="e.g. Lab Sheet, Re-exam Sheet">

            <br><br>

            <label>Quantity:</label>
            <input type="number" name="quantity" min="1" required>

            <br><br>
            <button type="submit" name="add" class="btn-primary">Add / Update Stock</button>
        </form>

        <hr>

        <!-- Centered Table -->
        <h2>Current Central Inventory</h2>
        <div class="table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Sheet Type</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($res->num_rows > 0): ?>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucwords($r['sheet_type'])) ?></td>
                            <td><?= $r['quantity'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align:center;">No stock available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<?php include('../includes/footer.php'); ?>
