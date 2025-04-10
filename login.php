<?php
$pageTitle = 'Login';
include 'includes/header.php';

// अगर यूजर पहले से लॉगिन है तो डैशबोर्ड पर रीडायरेक्ट करें
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// रीडायरेक्ट URL चेक करें
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// लॉगिन प्रोसेस
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // यूजर को डेटाबेस से चेक करें
        $user = selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // लॉगिन सफल
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // लास्ट लॉगिन अपडेट करें
            $db->prepare("UPDATE users SET last_login = :last_login WHERE id = :id")
               ->execute([
                   'last_login' => date('Y-m-d H:i:s'),
                   'id' => $user['id']
               ]);
            
            // रीडायरेक्ट करें
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<style>
.login-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-card {
    background-color: #1e1e1e;
    border: 1px solid #333;
    border-radius: 10px;
    width: 100%;
    max-width: 400px;
}

.login-card .card-body {
    padding: 2rem;
}

.login-title {
    color: #fff;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 24px;
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

.btn-login {
    background-color: #dc3545;
    border: none;
    padding: 10px;
    font-size: 16px;
}

.btn-login:hover {
    background-color: #bb2d3b;
}

.login-links {
    text-align: center;
    margin-top: 1.5rem;
}

.login-links a {
    color: #dc3545;
    text-decoration: none;
}

.login-links a:hover {
    color: #bb2d3b;
    text-decoration: underline;
}
</style>

<div class="login-container">
    <div class="login-card">
        <div class="card-body">
            <h2 class="login-title">Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" name="login" class="btn btn-login btn-primary">Login</button>
                </div>
            </form>
            
            <div class="login-links">
                <p>Don't have an account? <a href="register.php?redirect=<?php echo urlencode($redirect); ?>">Register</a></p>
                <p><a href="forgot-password.php">Forgot Password?</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 