<?php
// सेशन स्टार्ट
session_start();

// कॉन्फिग और डेटाबेस कनेक्शन
require_once 'includes/config.php';

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($listingId <= 0) {
    header('Location: index.php');
    exit;
}

// लिस्टिंग डिटेल्स प्राप्त करें
$stmt = $db->prepare("
    SELECT l.*, c.name as category_name, loc.name as location_name, u.username as username
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.id = ? AND l.is_active = 1
");
$stmt->execute([$listingId]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    header('Location: index.php?error=listing_not_found');
    exit;
}

// लिस्टिंग की इमेजेस प्राप्त करें
$images = [];
try {
    // सिंपल क्वेरी का उपयोग करें - सभी इमेज प्राप्त करें
    $stmt = $db->prepare("SELECT * FROM images WHERE listing_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$listingId]);
    $allImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // डीबग के लिए इमेज डेटा लॉग करें
    error_log("Total images found for listing $listingId: " . count($allImages));
    foreach ($allImages as $index => $img) {
        error_log("Image $index: ID={$img['id']}, Path={$img['image_path']}, Main={$img['is_main']}");
    }
    
    // सभी इमेज को सीधे उपयोग करें, डुप्लिकेट चेक न करें
    $images = $allImages;
    
    // अगर कोई इमेज नहीं है तो डिफॉल्ट इमेज दिखाएं
    if (empty($images)) {
        error_log("No images found for listing $listingId, using placeholder");
        $images = [['image_path' => 'assets/images/placeholder.jpg', 'is_main' => 1]];
    }
} catch (Exception $e) {
    // एरर हैंडलिंग
    error_log("Error fetching images: " . $e->getMessage());
    // डिफॉल्ट इमेज सेट करें
    $images = [['image_path' => 'assets/images/placeholder.jpg', 'is_main' => 1]];
}

// सोशल मीडिया डेटा प्राप्त करें
$stmt = $db->prepare("SELECT * FROM social_media WHERE listing_id = ? ORDER BY type");
$stmt->execute([$listingId]);
$socialMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// पेज टाइटल सेट करें
$pageTitle = htmlspecialchars($listing['title']) . " - Escort Directory";

// Check if user has bookmarked this listing
$bookmarkedListings = [];
if (isset($_SESSION['user_id'])) {
    try {
        $bookmarkStmt = $db->prepare("SELECT listing_id FROM bookmarks WHERE user_id = ?");
        $bookmarkStmt->execute([$_SESSION['user_id']]);
        $bookmarks = $bookmarkStmt->fetchAll(PDO::FETCH_ASSOC);
        $bookmarkedListings = array_column($bookmarks, 'listing_id');
    } catch (PDOException $e) {
        // Silently handle the error
        error_log("Error fetching bookmarks: " . $e->getMessage());
    }
}

// विजिट काउंट बढ़ाएं
try {
    $stmt = $db->prepare("UPDATE listings SET views = COALESCE(views, 0) + 1 WHERE id = ?");
    $stmt->execute([$listingId]);
} catch (PDOException $e) {
    // Silently handle the error
}

// Get previous and next listings
$prevListing = null;
$nextListing = null;

try {
    // Get previous listing
    $prevStmt = $db->prepare("
        SELECT l.id, l.title 
        FROM listings l 
        WHERE l.id < ? AND l.is_active = 1 
        ORDER BY l.id DESC 
        LIMIT 1
    ");
    $prevStmt->execute([$listingId]);
    $prevListing = $prevStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get next listing
    $nextStmt = $db->prepare("
        SELECT l.id, l.title 
        FROM listings l 
        WHERE l.id > ? AND l.is_active = 1 
        ORDER BY l.id ASC 
        LIMIT 1
    ");
    $nextStmt->execute([$listingId]);
    $nextListing = $nextStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently handle the error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --dark-bg: #121212;
            --dark-bg-lighter: #1e1e1e;
            --text-light: #f5f5f5;
            --primary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-light);
            padding-bottom: 0; /* Remove bottom padding */
            padding-top: 0; /* Remove top padding */
        }
        
        a {
            color: var(--primary);
        }
        
        a:hover {
            color: #2980b9;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .card {
            background-color: var(--dark-bg-lighter);
            border: 1px solid #333;
        }
        
        .social-links {
            position: relative;
            top: auto;
            left: auto;
            right: auto;
            z-index: 100;
            background-color: var(--dark-bg-lighter);
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            border-radius: 8px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            transition: transform 0.3s, opacity 0.3s;
        }
        
        .social-links a:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            transition: transform 0.3s;
        }
        
        .social-link:hover {
            transform: scale(1.1);
        }
        
        .whatsapp, .social-links .whatsapp {
            background-color: #25D366;
        }
        
        .telegram, .social-links .telegram {
            background-color: #0088cc;
        }
        
        .facebook, .social-links .facebook {
            background-color: #3b5998;
        }
        
        .twitter, .social-links .twitter {
            background-color: #1DA1F2;
        }
        
        .instagram, .social-links .instagram {
            background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D);
        }
        
        .snapchat, .social-links .snapchat {
            background-color: #FFFC00;
            color: black;
        }
        
        .tiktok, .social-links .tiktok {
            background-color: #000000;
        }
        
        .onlyfans, .social-links .onlyfans {
            background-color: #00AFF0;
        }
        
        .action-buttons {
            position: relative;
            bottom: auto;
            top: auto;
            left: auto;
            right: auto;
            background-color: var(--dark-bg-lighter);
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 5px 15px;
            position: relative;
        }
        
        .action-btn i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .btn-call {
            color: var(--success);
        }
        
        .btn-share {
            color: var(--primary);
        }
        
        .btn-report {
            color: var(--danger);
        }
        
        .share-dropdown {
            display: none;
            position: absolute;
            bottom: auto;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--dark-bg-lighter);
            padding: 15px;
            margin-top: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 999;
        }
        
        .btn-share.active .share-dropdown {
            display: flex;
            gap: 15px;
        }
        
        .share-option {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            transition: transform 0.3s;
        }
        
        .share-option:hover {
            transform: scale(1.1);
        }
        
        .share-option.facebook {
            background-color: #3b5998;
        }
        
        .share-option.twitter {
            background-color: #1DA1F2;
        }
        
        .share-option.whatsapp {
            background-color: #25D366;
        }
        
        .share-option.telegram {
            background-color: #0088cc;
        }
        
        .image-slider {
            width: 100%;
            margin: 0 auto;
            position: relative;
        }
        
        .swiper-container {
            width: 100%;
            height: 100%;
        }
        
        .swiper-slide {
            text-align: center;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .swiper-slide img {
            width: 100%;
            height: auto;
            max-height: 70vh;
            object-fit: contain;
        }
        
        .swiper-pagination {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
        }
        
        .swiper-button-next, .swiper-button-prev {
            color: white;
            background: rgba(0,0,0,0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            z-index: 20;
        }
        
        .swiper-button-next:after, .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }
        
        .swiper-pagination-bullet {
            background: white;
            opacity: 0.6;
        }
        
        .swiper-pagination-bullet-active {
            background: var(--primary);
            opacity: 1;
        }
        
        .profile-card, .listing-details, .description-box, .review-section {
            background-color: var(--dark-bg-lighter);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .profile-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-light);
        }
        
        .price-tag {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .modal-content {
            background-color: var(--dark-bg-lighter);
            color: var(--text-light);
        }
        
        .modal-header {
            border-bottom: 1px solid #333;
        }
        
        .modal-footer {
            border-top: 1px solid #333;
        }
        
        .form-control, .form-select {
            background-color: var(--dark-bg);
            border: 1px solid #333;
            color: var(--text-light);
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--dark-bg);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .bookmark-btn {
            background: none;
            border: none;
            color: var(--warning);
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .bookmark-btn:hover {
            transform: scale(1.1);
        }
        
        .bookmark-btn.active {
            color: var(--warning);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-item i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }
        
        .review-item {
            background-color: var(--dark-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-rating {
            color: var(--warning);
            margin-bottom: 10px;
        }
        
        .profile-navigation {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .nav-button {
            padding: 10px 15px;
            background-color: var(--dark-bg-lighter);
            color: var(--text-light);
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-button:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .nav-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-light);
            border-bottom: 2px solid var(--dark-bg-lighter);
            padding-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            body {
                padding-bottom: 0;
            }
            
            .social-links {
                padding: 5px;
                gap: 5px;
            }
            
            .social-links a, .social-link {
                width: 35px;
                height: 35px;
            }
            
            .action-buttons {
                padding: 10px 0;
            }
            
            .action-btn {
                padding: 5px 10px;
            }
            
            .full-image-link::after {
                opacity: 1;
                width: 35px;
                height: 35px;
                bottom: 10px;
                right: 10px;
            }
            
            .swiper-container {
                touch-action: pan-y;
            }
            
            .popup-prev,
            .popup-next {
                width: 30px;
                height: 30px;
                font-size: 16px;
            }
        }
        
        /* लाइटबॉक्स कस्टम स्टाइल */
        .lb-outerContainer {
            background-color: var(--dark-bg);
            border-radius: 8px;
        }
        
        .lb-dataContainer {
            background-color: var(--dark-bg);
            padding: 10px;
            border-radius: 0 0 8px 8px;
        }
        
        .lb-data .lb-caption {
            color: var(--text-light);
            font-size: 16px;
        }
        
        .lb-data .lb-number {
            color: var(--text-light);
            opacity: 0.8;
        }
        
        .lb-closeContainer .lb-close {
            filter: invert(1);
        }
        
        .lb-nav a.lb-prev, .lb-nav a.lb-next {
            opacity: 0.8;
        }
        
        .lb-cancel {
            background-color: var(--dark-bg);
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .swiper-slide:hover .image-overlay {
            opacity: 1;
        }
        
        .zoom-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .zoom-icon i {
            color: #000;
            font-size: 20px;
        }
        
        /* मोबाइल स्वाइप के लिए */
        @media (max-width: 768px) {
            .swiper-slide {
                touch-action: pan-y pinch-zoom;
            }
            
            .swiper-container {
                touch-action: pan-y;
            }
        }
        
        /* पॉपअप स्टाइल्स */
        .image-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .image-popup.active {
            display: flex;
            opacity: 1;
        }
        
        .popup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .popup-content {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1;
        }
        
        #popupImage {
            max-width: 98%;
            max-height: 92vh;
            object-fit: contain;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(0.95);
            opacity: 0;
        }
        
        .image-popup.active #popupImage {
            transform: scale(1);
            opacity: 1;
        }
        
        /* नेविगेशन बटन्स */
        .popup-close,
        .popup-prev,
        .popup-next {
            position: absolute;
            background: rgba(255, 255, 255, 0.98);
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 10;
            opacity: 0;
            transform: scale(0.9);
        }
        
        .image-popup.active .popup-close,
        .image-popup.active .popup-prev,
        .image-popup.active .popup-next {
            opacity: 1;
            transform: scale(1);
        }
        
        .popup-close {
            top: 20px;
            right: 20px;
        }
        
        .popup-close i {
            font-size: 22px;
            color: #333;
        }
        
        .popup-prev,
        .popup-next {
            top: 50%;
            transform: translateY(-50%);
            width: 54px;
            height: 54px;
        }
        
        .popup-prev {
            left: 20px;
        }
        
        .popup-next {
            right: 20px;
        }
        
        .popup-prev i,
        .popup-next i {
            font-size: 26px;
            color: #333;
        }
        
        /* होवर इफेक्ट्स */
        .popup-close:hover,
        .popup-prev:hover,
        .popup-next:hover {
            background: #fff;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }
        
        .popup-prev:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        .popup-next:hover {
            transform: translateY(-50%) scale(1.1);
        }
        
        /* काउंटर स्टाइल */
        .popup-counter {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.98);
            color: #333;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
        }
        
        .image-popup.active .popup-counter {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        /* मोबाइल ऑप्टिमाइजेशन */
        @media (max-width: 768px) {
            .popup-content {
                padding: 15px;
            }
            
            #popupImage {
                max-width: 100%;
                max-height: 88vh;
                border-radius: 12px;
            }
            
            .popup-close {
                top: 15px;
                right: 15px;
                width: 42px;
                height: 42px;
            }
            
            .popup-prev,
            .popup-next {
                width: 46px;
                height: 46px;
            }
            
            .popup-prev {
                left: 15px;
            }
            
            .popup-next {
                right: 15px;
            }
            
            .popup-counter {
                bottom: 20px;
                padding: 8px 20px;
                font-size: 14px;
            }
        }
        
        /* एनिमेशन्स */
        @keyframes zoomIn {
            from {
                transform: scale(0.3);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .image-popup.active #popupImage {
            animation: zoomIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* स्वाइपर स्टाइल्स अपडेट */
        .swiper-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .swiper-slide {
            overflow: hidden;
        }
        
        .swiper-slide img {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .swiper-slide:hover img {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- नेविगेशन बार -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4 mb-5" style="margin-top: 0 !important;">
        <!-- ब्रेडक्रम्ब्स -->
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="escorts.php?category=<?php echo $listing['category_id']; ?>"><?php echo htmlspecialchars($listing['category_name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($listing['title']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- प्रोफाइल नेविगेशन बटन्स -->
        <div class="profile-navigation">
            <?php if ($prevListing): ?>
            <a href="listing.php?id=<?php echo $prevListing['id']; ?>" class="nav-button">
                <i class="fas fa-chevron-left"></i> Previous Profile
            </a>
            <?php else: ?>
            <span class="nav-button disabled">
                <i class="fas fa-chevron-left"></i> Previous Profile
            </span>
            <?php endif; ?>
            
            <?php if ($nextListing): ?>
            <a href="listing.php?id=<?php echo $nextListing['id']; ?>" class="nav-button">
                Next Profile <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="nav-button disabled">
                Next Profile <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <!-- मेन कंटेंट -->
            <div class="col-lg-8">
                <!-- सोशल मीडिया लिंक्स - इमेज के ऊपर -->
                <?php if (!empty($socialMedia)): ?>
                <div class="social-links">
                    <?php 
                    foreach ($socialMedia as $social): 
                        $type = $social['type'];
                        $url = $social['value'];
                        $icon = '';
                        $class = '';
                        
                        switch ($type) {
                            case 'whatsapp':
                                $icon = 'fa-whatsapp';
                                $class = 'whatsapp';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $url);
                                }
                                break;
                            case 'instagram':
                                $icon = 'fa-instagram';
                                $class = 'instagram';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://instagram.com/' . $url;
                                }
                                break;
                            case 'telegram':
                                $icon = 'fa-telegram';
                                $class = 'telegram';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://t.me/' . $url;
                                }
                                break;
                            case 'facebook':
                                $icon = 'fa-facebook';
                                $class = 'facebook';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://facebook.com/' . $url;
                                }
                                break;
                            case 'snapchat':
                                $icon = 'fa-snapchat';
                                $class = 'snapchat';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://snapchat.com/add/' . $url;
                                }
                                break;
                            case 'tiktok':
                                $icon = 'fa-tiktok';
                                $class = 'tiktok';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://tiktok.com/@' . $url;
                                }
                                break;
                            case 'twitter':
                                $icon = 'fa-twitter';
                                $class = 'twitter';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://twitter.com/' . $url;
                                }
                                break;
                            case 'onlyfans':
                                $icon = 'fa-heart';
                                $class = 'onlyfans';
                                if (!preg_match('/^https?:\/\//', $url)) {
                                    $url = 'https://onlyfans.com/' . $url;
                                }
                                break;
                        }
                        if ($icon):
                    ?>
                        <a href="<?php echo htmlspecialchars($url); ?>" class="social-link <?php echo $class; ?>" target="_blank">
                            <i class="fab <?php echo $icon; ?>"></i>
                        </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>

                <!-- मेन कंटेनर -->
                <div class="listing-container">
                    <!-- स्वाइपर कंटेनर -->
                    <div class="swiper-container main-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="Listing Image <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- स्वाइपर नेविगेशन -->
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>

                    <!-- इमेज पॉपअप -->
                    <div class="image-popup" id="imagePopup">
                        <div class="popup-overlay"></div>
                        <div class="popup-content">
                            <img src="" alt="Full size image" id="popupImage">
                            <button class="popup-close">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="popup-prev">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="popup-next">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <div class="popup-counter" id="popupCounter">1 / 1</div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
                <!-- डीबग इन्फॉर्मेशन -->
                <div class="card mb-4 bg-dark text-white">
                    <div class="card-header">Debug Information</div>
                    <div class="card-body">
                        <h5>Images Data (<?php echo count($images); ?> images):</h5>
                        <pre><?php print_r($images); ?></pre>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- प्रोफाइल इन्फो -->
                <div class="profile-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="profile-title mb-0"><?php echo htmlspecialchars($listing['title']); ?></h1>
                        <button class="bookmark-btn" id="bookmarkBtn" data-listing-id="<?php echo $listingId; ?>">
                            <i class="<?php echo isset($_SESSION['user_id']) && isset($bookmarkedListings) && in_array($listingId, $bookmarkedListings) ? 'fas' : 'far'; ?> fa-bookmark"></i>
                        </button>
                    </div>
                    
                    <div class="price-tag">
                        <?php 
                        $currency = '₹';
                        switch ($listing['currency']) {
                            case 'USD': $currency = '$'; break;
                            case 'EUR': $currency = '€'; break;
                            case 'GBP': $currency = '£'; break;
                        }
                        echo $currency . number_format($listing['price']);
                        echo ' <small>(' . ($listing['price_type'] == 'full_night' ? 'full night' : 'per hour') . ')</small>';
                        ?>
                    </div>
                    
                    <!-- डिस्क्रिप्शन -->
                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                    </div>

                    <!-- डिटेल्स -->
                    <div class="details-grid">
                        <?php if (!empty($listing['age'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span><?php echo $listing['age']; ?> years</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['height'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-ruler-vertical"></i>
                            <span><?php echo $listing['height']; ?> cm</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['weight'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-weight"></i>
                            <span><?php echo $listing['weight']; ?> kg</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['location_name'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($listing['location_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['category_name'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($listing['category_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['nationality'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-globe"></i>
                            <span><?php echo htmlspecialchars($listing['nationality']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['body_type'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-female"></i>
                            <span><?php echo htmlspecialchars($listing['body_type']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($listing['gender'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-venus-mars"></i>
                            <span><?php echo htmlspecialchars($listing['gender']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- एक्शन बटन्स -->
                <div class="action-buttons">
                    <a href="tel:<?php echo $listing['country_code'] . $listing['contact_number']; ?>" class="action-btn btn-call">
                        <i class="fas fa-phone"></i> Call Now
                    </a>
                    <a href="javascript:void(0);" class="action-btn btn-share" id="shareButton">
                        <i class="fas fa-share-alt"></i> Share
                        <div class="share-dropdown">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-option facebook" target="_blank">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($listing['title']); ?>" class="share-option twitter" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode($listing['title'] . ' - ' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-option whatsapp" target="_blank">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://telegram.me/share/url?url=<?php echo urlencode((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($listing['title']); ?>" class="share-option telegram" target="_blank">
                                <i class="fab fa-telegram-plane"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode($listing['title']); ?>&body=<?php echo urlencode('Check out this profile: ' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-option" style="background: #d44638;">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="action-btn btn-report" id="reportButton">
                        <i class="fas fa-flag"></i> Report
                    </a>
                </div>

                <!-- रिव्यू सेक्शन -->
                <div class="review-section">
                    <h3 class="section-title">Reviews</h3>
                    
                    <!-- रिव्यू फॉर्म -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="review-form mb-4">
                        <form action="add-review.php" method="post">
                            <input type="hidden" name="listing_id" value="<?php echo $listingId; ?>">
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating" name="rating" required>
                                    <option value="5">5 Stars - Excellent</option>
                                    <option value="4">4 Stars - Very Good</option>
                                    <option value="3">3 Stars - Good</option>
                                    <option value="2">2 Stars - Fair</option>
                                    <option value="1">1 Star - Poor</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="review" class="form-label">Your Review</label>
                                <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <a href="login.php">Login</a> to leave a review.
                    </div>
                    <?php endif; ?>
                    
                    <!-- रिव्यू लिस्ट -->
                    <div class="review-list">
                        <!-- यहां PHP से रिव्यू लिस्ट लोड की जाएगी -->
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author">John Doe</div>
                                <div class="review-date">2 days ago</div>
                            </div>
                            <div class="review-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                            <div class="review-content">
                                Great service, highly recommended!
                            </div>
                        </div>
                        
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author">Jane Smith</div>
                                <div class="review-date">1 week ago</div>
                            </div>
                            <div class="review-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="review-content">
                                Excellent experience, will definitely come back!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- साइडबार -->
            <div class="col-lg-4">
                <!-- यहां साइडबार कंटेंट रहेगा -->
            </div>
        </div>
    </div>
    
    <!-- फुटर -->
    <?php include 'includes/footer.php'; ?>

    <!-- रिपोर्ट मोडल -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Report Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm" action="report-listing.php" method="post">
                        <input type="hidden" name="listing_id" value="<?php echo $listingId; ?>">
                        <div class="mb-3">
                            <label for="reportReason" class="form-label">Reason</label>
                            <select class="form-select" id="reportReason" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="fake">Fake listing</option>
                                <option value="inappropriate">Inappropriate content</option>
                                <option value="spam">Spam</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reportDetails" class="form-label">Details</label>
                            <textarea class="form-control" id="reportDetails" name="details" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="submitReport">Submit Report</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // स्वाइपर इनिशियलाइज़ेशन
        let mainSwiper = new Swiper('.main-swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            }
        });

        // पॉपअप एलिमेंट्स
        const popup = document.getElementById('imagePopup');
        const popupImage = document.getElementById('popupImage');
        const popupCounter = document.getElementById('popupCounter');
        const slides = document.querySelectorAll('.swiper-slide');
        let currentIndex = 0;

        // स्लाइड्स पर क्लिक इवेंट
        slides.forEach((slide, index) => {
            slide.addEventListener('click', function(e) {
                if (!e.target.closest('.swiper-button-next') && 
                    !e.target.closest('.swiper-button-prev') && 
                    !e.target.closest('.swiper-pagination')) {
                    openPopup(index);
                }
            });
        });

        // पॉपअप खोलें
        function openPopup(index) {
            const slide = slides[index];
            const img = slide.querySelector('img');
            if (img) {
                currentIndex = index;
                popupImage.src = img.src;
                popup.classList.add('active');
                document.body.style.overflow = 'hidden';
                mainSwiper.autoplay.stop();
                updateCounter();
            }
        }

        // पॉपअप बंद करें
        document.querySelector('.popup-close').addEventListener('click', () => {
            popup.classList.remove('active');
            document.body.style.overflow = '';
            mainSwiper.autoplay.start();
        });

        // नेविगेशन बटन्स
        document.querySelector('.popup-prev').addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            const img = slides[currentIndex].querySelector('img');
            if (img) {
                popupImage.src = img.src;
                updateCounter();
            }
        });

        document.querySelector('.popup-next').addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % slides.length;
            const img = slides[currentIndex].querySelector('img');
            if (img) {
                popupImage.src = img.src;
                updateCounter();
            }
        });

        // काउंटर अपडेट
        function updateCounter() {
            popupCounter.textContent = `${currentIndex + 1} / ${slides.length}`;
        }

        // कीबोर्ड नेविगेशन
        document.addEventListener('keydown', (e) => {
            if (!popup.classList.contains('active')) return;
            
            if (e.key === 'Escape') {
                popup.classList.remove('active');
                document.body.style.overflow = '';
                mainSwiper.autoplay.start();
            } else if (e.key === 'ArrowLeft') {
                document.querySelector('.popup-prev').click();
            } else if (e.key === 'ArrowRight') {
                document.querySelector('.popup-next').click();
            }
        });

        // टच इवेंट्स
        let touchStartX = 0;
        popup.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });

        popup.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchEndX - touchStartX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    document.querySelector('.popup-prev').click();
                } else {
                    document.querySelector('.popup-next').click();
                }
            }
        }, { passive: true });

        // Bookmark functionality
        const bookmarkBtn = document.getElementById('bookmarkBtn');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', function() {
                this.classList.toggle('active');
                
                const listingId = this.dataset.listingId;
                const isBookmarked = this.classList.contains('active');
                const icon = this.querySelector('i');
                
                if (icon) {
                    if (isBookmarked) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                }
                
                // Send AJAX request to bookmark/unbookmark
                fetch('bookmark-listing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `listing_id=${listingId}&action=${isBookmarked ? 'add' : 'remove'}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(isBookmarked ? 'Listing bookmarked!' : 'Listing removed from bookmarks!');
                    } else {
                        // Show error message
                        alert(data.message || 'An error occurred');
                        // Revert the button state
                        this.classList.toggle('active');
                        if (icon) {
                            icon.classList.toggle('far');
                            icon.classList.toggle('fas');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    // Revert the button state
                    this.classList.toggle('active');
                    if (icon) {
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                    }
                });
            });
        }
        
        // Share button functionality
        const shareButton = document.getElementById('shareButton');
        if (shareButton) {
            shareButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!shareButton.contains(e.target)) {
                    shareButton.classList.remove('active');
                }
            });
        }
        
        // Report button functionality
        const reportButton = document.getElementById('reportButton');
        const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
        
        if (reportButton) {
            reportButton.addEventListener('click', function() {
                reportModal.show();
            });
        }
        
        // Submit report
        const submitReportBtn = document.getElementById('submitReport');
        if (submitReportBtn) {
            submitReportBtn.addEventListener('click', function() {
                const form = document.getElementById('reportForm');
                if (form.checkValidity()) {
                    form.submit();
                } else {
                    form.reportValidity();
                }
            });
        }
    });
    </script>
</body>
</html> 