<?php
$pageTitle = 'Change Password';
include 'includes/header.php';

// अगर यूजर लॉगिन नहीं है तो लॉगिन पेज पर रीडायरेक्ट करें
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=change-password.php');
    exit;
}

$error = '';
$success = '';

// फॉर्म सबमिट होने पर
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill all fields';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters long';
    } else {
        // यूजर का करंट पासवर्ड चेक करें
        $user = $db->selectOne("SELECT password FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            // पासवर्ड अपडेट करें
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $db->update('users', [
                'password' => $hashedPassword
            ], ['id' => $_SESSION['user_id']]);
            
            $success = 'Password changed successfully!';
        }
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                        <h5 class="mt-2"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    </div>
                    <div class="list-group mt-3">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="add-listing.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Add New Listing
                        </a>
                        <a href="edit-profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-edit me-2"></i> Edit Profile
                        </a>
                        <a href="change-password.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i> Change Password</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 