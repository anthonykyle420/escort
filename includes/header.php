<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// यूजर की वर्तमान लोकेशन प्राप्त करें (आप इसे IP से या यूजर इनपुट से सेट कर सकते हैं)
$userLocation = isset($_SESSION['user_location']) ? $_SESSION['user_location'] : null;

// फ्लैश मैसेज प्राप्त करें
$flashMessage = getFlashMessage();

// सेशन स्टार्ट करें
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// डेटाबेस कनेक्शन शामिल करें
include_once 'includes/db.php';

// चेक करें कि यूजर लॉगिन है या नहीं
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Escort Directory'; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Find escorts in your area'; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <?php echo $extraCss; ?>
    <?php endif; ?>
    <script>
        // JavaScript वेरिएबल्स
        var siteUrl = '<?php echo SITE_URL; ?>';
    </script>
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <style>
        body {
            background-color: #121212;
            color: #f8f9fa;
        }
        .navbar {
            background-color: #1e1e1e !important;
        }
        .card {
            background-color: #1e1e1e;
            border-color: #333;
            color: #f8f9fa;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-weight: 600;
        }
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
            color: #f8f9fa;
        }
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 50px;
            height: 2px;
            background-color: #dc3545;
        }
        .swiper {
            padding-bottom: 50px;
        }
        .swiper-pagination-bullet {
            background: #dc3545;
        }
        .swiper-button-next, .swiper-button-prev {
            color: #dc3545;
        }
        .card-badges {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .listing-card {
            height: 100%;
        }
        .verified-badge {
            color: #007bff;
            margin-left: 5px;
        }
        .search-container {
            position: relative;
        }
        #locationResults {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-top: none;
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow-y: auto;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .hover-bg-secondary:hover {
            background-color: #2c2c2c;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">Escort Directory</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="listings.php">Escorts</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php">Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <?php if ($isLoggedIn): ?>
                            <a href="dashboard.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                            <a href="register.php" class="btn btn-danger">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="mobile-menu d-md-none">
        <div class="container">
            <ul>
                <li><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php">Register</a></li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="new-profile.php">New Profile</a>
                </li>
                <li>
                    <a href="#" id="mobile-dark-mode-toggle">
                        <i class="fas fa-moon"></i> Dark Mode
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <?php if ($flashMessage): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flashMessage['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <main class="site-main"> 