<?php
session_start();
include('includes/header.php');
?>

<div class="report-container">
    <div class="report-box">
        <?php if (isset($_SESSION['user_id'])): ?>
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You are logged in to the <strong>Exam Sheet Management System</strong>.</p>
            <div class="button-group">
                <a href="dashboard.php" class="btn">Go to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        <?php else: ?>
            <h2>Welcome to the Exam Sheet Management System</h2>
            <p>Please log in or sign up to continue.</p>
            <div class="button-group">
                <a href="login.php" class="btn">Login</a><br>
                <a href="signup.php" class="btn btn-secondary">Sign Up</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>
