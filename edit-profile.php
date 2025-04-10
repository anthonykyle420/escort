<?php
$pageTitle = 'Edit Profile';
include 'includes/header.php';

// अगर यूजर लॉगिन नहीं है तो लॉगिन पेज पर रीडायरेक्ट करें
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=edit-profile.php');
    exit;
}

// यूजर डेटा फेच करें
$user = $db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);

// यूजर की लिस्टिंग्स फेच करें
$listings = $db->query("
    SELECT l.*, c.name as category_name, loc.name as location_name, 
    (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
", [$_SESSION['user_id']])->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// फॉर्म सबमिट होने पर
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error = 'Please fill all required fields';
    } else {
        // चेक करें कि ईमेल पहले से मौजूद तो नहीं है (अगर ईमेल बदला गया है)
        if ($email !== $user['email']) {
            $existingUser = $db->selectOne("SELECT id FROM users WHERE email = :email AND id != :id", 
                ['email' => $email, 'id' => $_SESSION['user_id']]);
            
            if ($existingUser) {
                $error = 'Email already exists. Please use a different email.';
            }
        }
        
        if (empty($error)) {
            // यूजर अपडेट करें
            $db->update('users', [
                'name' => $name,
                'email' => $email
            ], ['id' => $_SESSION['user_id']]);
            
            // सेशन अपडेट करें
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $success = 'Profile updated successfully!';
            
            // यूजर डेटा रीफ्रेश करें
            $user = $db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);
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
                        <a href="my-listings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-list me-2"></i> My Listings
                        </a>
                        <a href="edit-profile.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-user-edit me-2"></i> Edit Profile
                        </a>
                        <a href="change-password.php" class="list-group-item list-group-item-action">
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
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i> Edit Profile</h5>
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
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- यूजर की लिस्टिंग्स दिखाएं -->
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> My Listings</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($listings)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You don't have any listings yet.
                            <a href="add-listing.php" class="alert-link">Add your first listing</a>.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($listings as $listing): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 bg-dark text-white">
                                        <div class="position-relative">
                                            <?php if ($listing['main_image']): ?>
                                                <a href="view-profile.php?id=<?php echo $listing['id']; ?>">
                                                    <img src="<?php echo $listing['main_image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>" style="height: 200px; object-fit: cover;">
                                                </a>
                                            <?php else: ?>
                                                <a href="view-profile.php?id=<?php echo $listing['id']; ?>">
                                                    <div class="bg-secondary text-center py-5" style="height: 200px;">
                                                        <i class="fas fa-image fa-3x text-white-50"></i>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <?php if ($listing['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($listing['is_verified']): ?>
                                                    <span class="badge bg-primary">Verified</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Edit and Delete buttons on image -->
                                            <div class="position-absolute bottom-0 end-0 p-2">
                                                <a href="insights.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-info me-1" title="View Insights">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-primary me-1" title="Edit Listing">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="my-listings.php?action=delete&id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this listing? This action cannot be undone.')" title="Delete Listing">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="view-profile.php?id=<?php echo $listing['id']; ?>" class="text-white text-decoration-none">
                                                    <?php echo htmlspecialchars($listing['title']); ?>
                                                </a>
                                            </h5>
                                            <p class="card-text">
                                                <small>
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($listing['location_name']); ?> &bull;
                                                    <i class="fas fa-folder me-1"></i> <?php echo htmlspecialchars($listing['category_name']); ?>
                                                </small>
                                            </p>
                                            <p class="card-text">
                                                <small>
                                                    <i class="fas fa-eye me-1"></i> <?php echo $listing['total_views']; ?> views &bull;
                                                    <i class="fas fa-phone me-1"></i> <?php echo $listing['contact_clicks']; ?> contacts
                                                </small>
                                            </p>
                                        </div>
                                        
                                        <div class="card-footer bg-dark border-secondary">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1"></i> Added: <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                                                </small>
                                                <a href="view-profile.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 