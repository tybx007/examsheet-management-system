<?php
session_start();
include('../config/db.php');
include('../includes/header.php');

// Restrict access to department users
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'department') {
    header('Location: ../login.php');
    exit;
}

$dept = $_SESSION['user']['department_name'];
$message = "";

// Fetch available sheet types for this department only (prepared statement)
$stmt_types = $conn->prepare("
    SELECT DISTINCT sheet_type 
    FROM inventory 
    WHERE location='department' 
      AND department_name = ?
      AND sheet_type != '' 
    ORDER BY sheet_type ASC
");
$stmt_types->bind_param("s", $dept);
$stmt_types->execute();
$sheet_types = $stmt_types->get_result();
$stmt_types->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sheet_type = strtolower(trim($_POST['sheet_type']));
    $quantity = intval($_POST['quantity']);

    // Check available quantity for this department and sheet type (prepared)
    $stmt = $conn->prepare("
        SELECT quantity 
        FROM inventory 
        WHERE LOWER(TRIM(sheet_type)) = LOWER(TRIM(?)) 
          AND location = 'department'
          AND department_name = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $sheet_type, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $available = $row ? intval($row['quantity']) : 0;
    $stmt->close();

    if ($available == 0) {
        $message = "<p class='error'>You don't have any '" . htmlspecialchars(ucwords($sheet_type)) . "' sheets to return!</p>";
    } elseif ($quantity > $available) {
        $message = "<p class='error'>You cannot return more than your available stock ($available)!</p>";
    } else {
        try {
            $conn->begin_transaction();

            // Reduce department stock (prepared)
            $stmt = $conn->prepare("
                UPDATE inventory 
                SET quantity = quantity - ? 
                WHERE LOWER(TRIM(sheet_type)) = LOWER(TRIM(?)) 
                  AND location = 'department'
                  AND department_name = ?
            ");
            $stmt->bind_param("iss", $quantity, $sheet_type, $dept);
            $stmt->execute();
            if ($stmt->affected_rows == 0) throw new Exception("Department inventory update failed");
            $stmt->close();

            // Update central inventory stock (prepared). If central doesn't have the type, insert it.
            $stmt = $conn->prepare("
                UPDATE inventory 
                SET quantity = quantity + ? 
                WHERE LOWER(TRIM(sheet_type)) = LOWER(TRIM(?)) 
                  AND location = 'central'
            ");
            $stmt->bind_param("is", $quantity, $sheet_type);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected == 0) {
                // central row for this sheet_type doesn't exist — insert it
                $stmtIns = $conn->prepare("
                    INSERT INTO inventory (sheet_type, quantity, location, department_name, date_added)
                    VALUES (?, ?, 'central', NULL, NOW())
                ");
                $stmtIns->bind_param("si", $sheet_type, $quantity);
                $stmtIns->execute();
                $stmtIns->close();
            }

            // Log the return transaction (prepared)
            $user_id = $_SESSION['user']['id'];
            $stmt = $conn->prepare("
                INSERT INTO return_history (sheet_type, quantity, department, user_id, return_date)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sisi", $sheet_type, $quantity, $dept, $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = "<p class='success'>Successfully returned $quantity '" . htmlspecialchars(ucwords($sheet_type)) . "' sheets to Central Inventory!</p>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<p class='error'>Error processing return: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<div class="report-container">
    <h2>↩️ Return Extra Sheets</h2>

    <div class="message-box"><?= $message ?></div>

    <form method="POST" class="auth-form" id="returnForm">
        <div class="form-group">
            <label>Sheet Type:</label>
            <select name="sheet_type" required>
                <option value="">Select Sheet Type</option>
                <?php while ($row = $sheet_types->fetch_assoc()): ?>
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

        <button type="submit">Return Sheets</button>
    </form>

</div>

<script>
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const quantity = parseInt(this.quantity.value);
    if (quantity <= 0) {
        e.preventDefault();
        alert('Quantity must be greater than 0');
    }
});
</script>

<?php include('../includes/footer.php'); ?>
