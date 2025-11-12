<?php
session_start();
include('includes/header.php');
include('config/db.php');


$message = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $res = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;

                if ($user['role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    header("Location: department/dashboard.php");
                    exit;
                }
            } else {
                $message = "<p class='error'>❌ Invalid password.</p>";
            }
        } else {
            $message = "<p class='error'>⚠️ User not found.</p>";
        }
    } else {
        $message = "<p class='error'>⚠️ Please fill in all fields.</p>";
    }
}
?>
<div class="report-container">
    <div class="report-box">
        <h2>Login</h2>

        <?= $message ?>

        <form method="POST" class="auth-form">
        <label>Email:</label>
        <input type="email" name="email" placeholder="Enter your email" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" placeholder="Enter your password" required><br><br>

        <button type="submit" name="submit">Login</button>
        </form>

        <p>Don’t have an account? <a href="signup.php">Sign up</a></p>
    </div>
</div>

<?php include('includes/footer.php'); ?>
