<?php
$pageTitle = 'Reset Password';
include 'includes/header.php';

// अगर यूजर पहले से लॉगिन है तो डैशबोर्ड पर रीडायरेक्ट करें
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$validToken = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    $error = 'Invalid or missing reset token';
} else {
    // टोकन वैलिड है या नहीं चेक करें
    $user = $db->selectOne("
        SELECT id, name, email 
        FROM users 
        WHERE reset_token = :token AND reset_expires > NOW()
    ", ['token' => $token]);
    
    if (!$user) {
        $error = 'Invalid or expired reset token. Please request a new password reset link.';
    } else {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // पासवर्ड अपडेट करें
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $db->update('users', [
            'password' => $hashedPassword,
            'reset_token' => null,
            'reset_expires' => null
        ], ['id' => $user['id']]);
        
        $success = 'Your password has been reset successfully. You can now login with your new password.';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Reset Password</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <div class="text-center mt-3">
                            <a href="forgot-password.php" class="btn btn-outline-primary">Request New Reset Link</a>
                        </div>
                    <?php elseif (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Login</a>
                        </div>
                    <?php elseif ($validToken): ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 