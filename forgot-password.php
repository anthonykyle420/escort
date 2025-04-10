<?php
$pageTitle = 'Forgot Password';
include 'includes/header.php';

// अगर यूजर पहले से लॉगिन है तो डैशबोर्ड पर रीडायरेक्ट करें
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // चेक करें कि ईमेल मौजूद है या नहीं
        $user = selectOne("SELECT id, username FROM users WHERE email = :email", ['email' => $email]);
        
        if ($user) {
            // रैंडम टोकन जनरेट करें
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            try {
                // टोकन को डेटाबेस में सेव करें
                $db->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at, created_at)
                    VALUES (:user_id, :token, :expires_at, NOW())
                ")->execute([
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expiry
                ]);
                
                // रीसेट लिंक भेजें (यहां मेल भेजने का कोड जोड़ें)
                // TODO: Implement actual email sending
                
                $success = 'Password reset instructions have been sent to your email address.';
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
            }
        } else {
            // सुरक्षा के लिए यही मैसेज दिखाएं, भले ही ईमेल न मिले
            $success = 'If your email exists in our system, you will receive password reset instructions.';
        }
    }
}
?>

<style>
.forgot-password-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.forgot-password-card {
    background-color: #1e1e1e;
    border: 1px solid #333;
    border-radius: 10px;
    width: 100%;
    max-width: 400px;
}

.forgot-password-card .card-body {
    padding: 2rem;
}

.forgot-password-title {
    color: #fff;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 24px;
}

.forgot-password-text {
    color: #ccc;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 14px;
}

.form-label {
    color: #fff;
}

.form-control {
    background-color: #2c2c2c;
    border-color: #444;
    color: #fff;
}

.form-control:focus {
    background-color: #2c2c2c;
    border-color: #666;
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.btn-reset {
    background-color: #dc3545;
    border: none;
    padding: 10px;
    font-size: 16px;
}

.btn-reset:hover {
    background-color: #bb2d3b;
}

.forgot-password-links {
    text-align: center;
    margin-top: 1.5rem;
}

.forgot-password-links a {
    color: #dc3545;
    text-decoration: none;
}

.forgot-password-links a:hover {
    color: #bb2d3b;
    text-decoration: underline;
}
</style>

<div class="forgot-password-container">
    <div class="forgot-password-card">
        <div class="card-body">
            <h2 class="forgot-password-title">Forgot Password</h2>
            <p class="forgot-password-text">Enter your email address and we'll send you instructions to reset your password.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="d-grid">
                    <button type="submit" name="reset" class="btn btn-reset btn-primary">Send Reset Link</button>
                </div>
            </form>
            
            <div class="forgot-password-links">
                <p>Remember your password? <a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>