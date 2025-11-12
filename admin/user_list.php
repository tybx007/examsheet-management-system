<?php
include('../includes/auth.php');
include('../config/db.php');
include('../includes/header.php');

// Only admins
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

$message = "";

// Handle delete
if(isset($_GET['delete'])){
    $user_id = intval($_GET['delete']);
    $checkRole = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $checkRole->bind_param("i", $user_id);
    $checkRole->execute();
    $checkRole->store_result();
    $checkRole->bind_result($targetRole);
    $checkRole->fetch();

    if($checkRole->num_rows > 0){
        if($targetRole === 'admin'){
            $message = "<p class='msg error'>❌ You cannot delete an admin account!</p>";
        } elseif($user_id == $_SESSION['user']['id']){
            $message = "<p class='msg error'>❌ You cannot delete your own account!</p>";
        } else {
            $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete->bind_param("i", $user_id);
            if($delete->execute()){
                $message = "<p class='msg success'>✅ Department user deleted successfully.</p>";
            } else {
                $message = "<p class='msg error'>❌ Error deleting user!</p>";
            }
        }
    }
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>

<div class="report-container">
    <h2>👥 User List</h2>

    <?= $message ?>

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= ucfirst($row['role']) ?></td>
                <td><?= $row['department_name'] ?: '-' ?></td>
                <td>
                    <?php if($row['role'] === 'department'): ?>
                        <a href="user_list.php?delete=<?= $row['id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this department user?')"
                           class="btn-primary">
                            Delete
                        </a>
                    <?php elseif($row['id'] == $_SESSION['user']['id']): ?>
                        <span style="color:gray;">(You)</span>
                    <?php else: ?>
                        <span style="color:gray;">Not allowed</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


<?php include('../includes/footer.php'); ?>
