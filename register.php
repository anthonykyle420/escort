<?php
$pageTitle = 'Register';
include 'includes/header.php';

// अगर यूजर पहले से लॉगिन है तो डैशबोर्ड पर रीडायरेक्ट करें
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// रीडायरेक्ट URL चेक करें
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// रजिस्ट्रेशन प्रोसेस
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // वैलिडेशन
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // चेक करें कि ईमेल पहले से मौजूद तो नहीं है
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $error = 'Email already exists';
            } else {
                // यूजर को डेटाबेस में जोड़ें
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password, created_at, updated_at)
                    VALUES (:username, :email, :password, NOW(), NOW())
                ");
                
                $result = $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ]);
                
                if ($result) {
                    $success = 'Registration successful! You can now login.';
                    
                    // 3 सेकंड के बाद लॉगिन पेज पर रीडायरेक्ट करें
                    header("refresh:3;url=login.php?redirect=" . urlencode($redirect));
                } else {
                    $error = 'Registration failed. Database error: ' . implode(', ', $stmt->errorInfo());
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Error: ' . $e->getMessage();
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}
?>

<style>
.register-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.register-card {
    background-color: #1e1e1e;
    border: 1px solid #333;
    border-radius: 10px;
    width: 100%;
    max-width: 400px;
}

.register-card .card-body {
    padding: 2rem;
}

.register-title {
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

.btn-register {
    background-color: #dc3545;
    border: none;
    padding: 10px;
    font-size: 16px;
}

.btn-register:hover {
    background-color: #bb2d3b;
}

.register-links {
    text-align: center;
    margin-top: 1.5rem;
}

.register-links a {
    color: #dc3545;
    text-decoration: none;
}

.register-links a:hover {
    color: #bb2d3b;
    text-decoration: underline;
}
</style>

<div class="register-container">
    <div class="register-card">
        <div class="card-body">
            <h2 class="register-title">Register</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" name="register" class="btn btn-register btn-primary">Register</button>
                </div>
            </form>
            
            <div class="register-links">
                <p>Already have an account? <a href="login.php?redirect=<?php echo urlencode($redirect); ?>">Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 