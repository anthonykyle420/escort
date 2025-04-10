<?php
// Basic error handling
error_reporting(0);
ini_set('display_errors', 0);

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get listings count
$stmt = $db->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalListings = $stmt->fetchColumn();

// Get active listings count
$stmt = $db->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$activeListings = $stmt->fetchColumn();

// Get recent listings
$stmt = $db->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$_SESSION['user_id']]);
$listings = $stmt->fetchAll();

// Check if mobile view
$isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/mobile|android|touch|tablet|ipad|iphone/i', $_SERVER['HTTP_USER_AGENT']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Escort Directory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #1a1a1a;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        /* Mobile Styles */
        .mobile-container {
            padding: 15px;
            padding-bottom: 80px;
        }

        .mobile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #2d2d2d;
            padding: 15px;
            border-radius: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info i {
            font-size: 24px;
            color: #dc3545;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box i {
            font-size: 24px;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #888;
            font-size: 14px;
        }

        .add-new-btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .listings-container {
            background: #2d2d2d;
            border-radius: 10px;
            padding: 15px;
        }

        .section-title {
            margin-bottom: 15px;
            font-size: 18px;
            color: #fff;
        }

        .listing-card {
            background: rgba(45, 45, 45, 0.8);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .listing-content {
            display: flex;
            gap: 15px;
            text-decoration: none;
            color: #fff;
        }

        .listing-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
        }

        .listing-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .listing-info {
            flex: 1;
        }

        .listing-title {
            font-size: 18px;
            margin: 0 0 8px 0;
            color: #fff;
            transition: color 0.3s ease;
        }

        .listing-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .listing-status.inactive {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .listing-actions {
            margin-top: 10px;
            text-align: right;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid rgba(0, 123, 255, 0.2);
            color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: rgba(0, 123, 255, 0.2);
            border-color: rgba(0, 123, 255, 0.3);
            transform: translateY(-2px);
        }

        .edit-btn i {
            margin-right: 6px;
        }

        /* Hover effects */
        .listing-card:hover {
            background: rgba(45, 45, 45, 0.9);
            transform: translateY(-2px);
        }

        .listing-content:hover .listing-title {
            color: #007bff;
        }

        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2d2d2d;
            display: flex;
            justify-content: space-around;
            padding: 10px 5px;
            border-top: 1px solid #3d3d3d;
            z-index: 1000;
        }

        .nav-item {
            color: #fff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
            padding: 5px;
        }

        .nav-item i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .nav-item.active {
            color: #dc3545;
        }

        .nav-item:hover {
            color: #dc3545;
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <div class="mobile-header">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            <i class="fas fa-bell"></i>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <i class="fas fa-list"></i>
                <div class="stat-number"><?php echo $totalListings; ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $activeListings; ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
        </div>

        <a href="add-listing.php" class="add-new-btn">
            <i class="fas fa-plus"></i>
            <span>Add New Listing</span>
        </a>

        <div class="listings-container">
            <h2 class="section-title">Recent Listings</h2>
            <?php foreach ($listings as $listing): ?>
            <div class="listing-card">
                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="listing-content">
                    <div class="listing-image">
                        <?php 
                        $stmt = $db->prepare("SELECT * FROM images WHERE listing_id = ? LIMIT 1");
                        $stmt->execute([$listing['id']]);
                        $image = $stmt->fetch();
                        
                        if ($image && !empty($image['image_path'])) {
                            $imagePath = '/directory/uploads/listings/' . basename($image['image_path']);
                            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Listing">';
                        } else {
                            echo '<i class="fas fa-image"></i>';
                        }
                        ?>
                    </div>
                    <div class="listing-info">
                        <h3 class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></h3>
                        <span class="listing-status <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $listing['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </a>
                <div class="listing-actions">
                    <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="edit-btn">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <nav class="mobile-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
        <a href="my-listings.php" class="nav-item">
            <i class="fas fa-list"></i>
            <span>Listings</span>
        </a>
        <a href="messages.php" class="nav-item">
            <i class="fas fa-envelope"></i>
            <span>Messages</span>
        </a>
    </nav>
</body>
</html> 