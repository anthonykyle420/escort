<?php
// Start session
session_start();

// Config and database connection
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=verify-listing.php');
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Get listing ID and verification code
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$verificationCode = isset($_GET['code']) ? $_GET['code'] : '';

// Check if listing and code are valid
$stmt = $db->prepare("
    SELECT l.*, c.name as category_name, loc.name as location_name,
    (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    WHERE l.id = ? AND l.user_id = ? AND l.verification_code = ?
");
$stmt->execute([$listingId, $userId, $verificationCode]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    header('Location: my-listings.php?error=invalid_verification');
    exit;
}

// Get previously uploaded verification photos
$stmt = $db->prepare("
    SELECT * FROM verification_photos 
    WHERE listing_id = ? AND user_id = ? 
    ORDER BY submitted_at DESC
");
$stmt->execute([$listingId, $userId]);
$verificationPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Success and error messages
$success_message = '';
$error_message = '';

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload verification photo
    if (isset($_FILES['verification_photo']) && $_FILES['verification_photo']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['verification_photo'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error_message = "Only JPG, JPEG and PNG images are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $error_message = "Image size should be less than 5MB.";
        } else {
            // Generate file name
            $fileName = 'verification_' . $listingId . '_' . time() . '_' . rand(1000, 9999) . '.jpg';
            $uploadPath = 'uploads/verification/' . $fileName;
            
            // Create upload directory if it doesn't exist
            if (!file_exists('uploads/verification/')) {
                mkdir('uploads/verification/', 0777, true);
            }
            
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Save verification photo to database
                $stmt = $db->prepare("
                    INSERT INTO verification_photos (listing_id, user_id, photo_path, submitted_at)
                    VALUES (?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$listingId, $userId, $uploadPath])) {
                    $success_message = "Verification photo uploaded successfully! Your profile will be verified by our team soon.";
                    
                    // Refresh verification photos list
                    $stmt = $db->prepare("
                        SELECT * FROM verification_photos 
                        WHERE listing_id = ? AND user_id = ? 
                        ORDER BY submitted_at DESC
                    ");
                    $stmt->execute([$listingId, $userId]);
                    $verificationPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Failed to save verification photo.";
                }
            } else {
                $error_message = "Failed to upload verification photo.";
            }
        }
    } else {
        $error_message = "Please select a verification photo.";
    }
}

// Set page title
$pageTitle = "Verify Your Profile";

// Add extra CSS for this page
$extraCss = '<style>
    :root {
        --primary-color: #8e44ad;
        --secondary-color: #3498db;
        --success-color: #2ecc71;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --dark-color: #121212;
        --card-bg: #1a1a1a;
        --card-border: #333;
    }
    
    body {
        background-color: var(--dark-color);
        color: #fff;
        padding-top: 0 !important;
    }
    
    .navbar, header {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        z-index: 1000 !important;
    }
    
    .verification-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 15px;
    }
    
    .verification-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .verification-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        border: 1px solid var(--card-border);
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .verification-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 15px 20px;
        font-weight: 600;
    }
    
    .card-header-success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
    }
    
    .card-header-info {
        background: linear-gradient(135deg, #2980b9, #3498db);
    }
    
    .card-header-warning {
        background: linear-gradient(135deg, #f39c12, #f1c40f);
    }
    
    .profile-image {
        height: 250px;
        object-fit: cover;
        width: 100%;
        border-bottom: 1px solid var(--card-border);
    }
    
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border-radius: 50%;
        margin-right: 15px;
        font-weight: bold;
        box-shadow: 0 3px 8px rgba(142, 68, 173, 0.3);
    }
    
    .step-content {
        background-color: rgba(52, 152, 219, 0.05);
        border-left: 3px solid var(--secondary-color);
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 20px;
        transition: transform 0.3s;
    }
    
    .step-content:hover {
        transform: translateX(5px);
    }
    
    .verification-code {
        background-color: rgba(142, 68, 173, 0.1);
        border: 1px solid var(--primary-color);
        border-radius: 5px;
        padding: 10px 15px;
        font-family: monospace;
        font-size: 24px;
        letter-spacing: 3px;
        color: var(--primary-color);
        font-weight: bold;
        text-align: center;
        box-shadow: 0 3px 10px rgba(142, 68, 173, 0.2);
        margin-top: 10px;
    }
    
    .upload-btn-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .upload-btn {
        border: 2px dashed #3498db;
        color: #3498db;
        background-color: rgba(52, 152, 219, 0.1);
        padding: 40px 20px;
        border-radius: 12px;
        font-size: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .upload-btn:hover {
        background-color: rgba(52, 152, 219, 0.2);
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(52, 152, 219, 0.2);
    }
    
    .upload-btn-wrapper input[type=file] {
        font-size: 100px;
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        border: none;
        color: white;
        padding: 15px 40px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 18px;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
        transition: all 0.3s;
        width: 100%;
    }
    
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(46, 204, 113, 0.5);
    }
    
    .photo-status {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        z-index: 10;
    }
    
    .status-pending {
        background-color: rgba(243, 156, 18, 0.9);
        color: white;
    }
    
    .status-approved {
        background-color: rgba(46, 204, 113, 0.9);
        color: white;
    }
    
    .status-rejected {
        background-color: rgba(231, 76, 60, 0.9);
        color: white;
    }
    
    .photo-timestamp {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background-color: rgba(0, 0, 0, 0.7);
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        color: white;
    }
    
    .photo-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .photo-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        height: 200px;
    }
    
    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .photo-item:hover img {
        transform: scale(1.05);
    }
    
    @media (max-width: 768px) {
        .verification-header {
            padding: 20px;
        }
        
        .photo-gallery {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
    
    /* Custom checkbox styling */
    .custom-checkbox-container {
        background-color: rgba(0,0,0,0.05);
        padding: 12px;
        border-radius: 5px;
        border: 1px solid #ddd;
        margin-top: 15px;
        margin-bottom: 15px;
    }
    
    .custom-checkbox-container .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid #666;
        margin-right: 10px;
    }
    
    .custom-checkbox-container .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .custom-checkbox-container .form-check-label {
        color: #333;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.4;
    }
    
    /* Dark mode checkbox for dark backgrounds */
    .dark-checkbox-container {
        background-color: rgba(255,255,255,0.1);
        padding: 12px;
        border-radius: 5px;
        border: 1px solid rgba(255,255,255,0.2);
        margin-top: 15px;
        margin-bottom: 15px;
    }
    
    .dark-checkbox-container .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255,255,255,0.5);
        margin-right: 10px;
    }
    
    .dark-checkbox-container .form-check-input:checked {
        background-color: #3498db;
        border-color: #3498db;
    }
    
    .dark-checkbox-container .form-check-label {
        color: #fff;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.4;
    }
</style>';

// Include header
include 'includes/header.php';
?>

<style>
/* Additional mobile-specific styles */
@media (max-width: 768px) {
    .upload-btn {
        padding: 20px 15px;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    .upload-btn i {
        font-size: 2.5em !important;
    }
    
    .upload-btn h5 {
        font-size: 1.1rem;
    }
    
    .upload-btn p {
        font-size: 0.9rem;
    }
    
    .submit-btn {
        padding: 12px 20px;
        font-size: 16px;
        white-space: normal;
        word-wrap: break-word;
    }
    
    .step-content {
        padding: 10px;
    }
    
    .verification-code {
        font-size: 18px;
        letter-spacing: 2px;
        padding: 8px 10px;
    }
    
    .alert .d-flex {
        flex-direction: column;
    }
    
    .alert .me-3 {
        margin-right: 0 !important;
        margin-bottom: 10px;
    }
    
    .card-body ul {
        padding-left: 1rem !important;
    }
    
    /* Mobile Upload Form Styles */
    .mobile-upload-form {
        display: block !important; /* Always show on mobile */
        background-color: var(--card-bg);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid var(--card-border);
    }
    
    .mobile-upload-form .btn-primary {
        background: linear-gradient(135deg, #3498db, #2980b9);
        border: none;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
    }
    
    .mobile-upload-form .btn-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        border: none;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
    }
    
    .mobile-upload-form .card {
        margin-bottom: 15px;
    }
    
    .mobile-upload-form .card-body {
        padding: 10px;
    }
    
    .mobile-upload-form ul {
        margin-bottom: 0;
    }
    
    .mobile-upload-form li {
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .mobile-upload-form .form-check-label {
        font-size: 14px;
    }
    
    #mobile-preview .card {
        margin-bottom: 15px;
        border: 1px solid #3498db;
    }
    
    .upload-container {
        background-color: rgba(52, 152, 219, 0.1);
        border-radius: 10px;
        border: 2px dashed #3498db !important;
        padding: 20px !important;
        transition: all 0.3s;
    }
    
    .upload-container:hover {
        background-color: rgba(52, 152, 219, 0.15);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
    }
    
    #mobile-camera-btn {
        padding: 10px;
        display: block !important;
    }
    
    #submit-verification-mobile:disabled {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        opacity: 0.7;
    }
    
    .mobile-upload-form .alert-info {
        background-color: rgba(52, 152, 219, 0.1);
        border-color: #3498db;
        color: #3498db;
    }
    
    /* New mobile-specific fixes */
    .mobile-upload-form h5, 
    .mobile-upload-form h6 {
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .mobile-upload-form p, 
    .mobile-upload-form li {
        font-size: 14px;
        line-height: 1.4;
    }
    
    .mobile-upload-form .form-check {
        margin: 15px 0;
    }
    
    .mobile-upload-form .btn {
        margin-bottom: 15px;
        font-size: 16px;
        padding: 12px 15px;
        display: block !important;
    }
    
    .mobile-upload-form .alert {
        margin-top: 20px;
        padding: 10px;
    }
    
    /* Fix for elements that might be hidden */
    .mobile-upload-form * {
        overflow: visible;
        word-wrap: break-word;
        white-space: normal;
    }
    
    /* Desktop form should be hidden on mobile */
    .desktop-upload-form {
        display: flex;
    }
    
    /* Mobile-only section */
    .mobile-only-section {
        display: none;
    }
    
    @media (max-width: 768px) {
        .desktop-upload-form {
            display: none !important;
        }
        
        .mobile-only-section {
            display: block !important;
        }
    }
    
    /* Fix for the order of sections on mobile */
    .mobile-section-order {
        display: flex;
        flex-direction: column;
    }
    
    .mobile-section-order .privacy-notice {
        order: 1;
        margin-bottom: 15px !important;
        margin-top: 15px !important;
    }
    
    .mobile-section-order .verification-timeline {
        order: 2;
        margin-top: 15px !important;
        margin-bottom: 15px !important;
    }
    
    /* Make sure alerts are properly displayed */
    .alert {
        width: 100%;
        margin-bottom: 15px;
    }
    
    /* Ensure proper spacing between sections */
    .verification-card {
        margin-bottom: 20px !important;
    }
    
    /* Fix for the mobile form display */
    .mobile-only-section {
        display: none;
    }
    
    @media (max-width: 768px) {
        .desktop-upload-form {
            display: none !important;
        }
        
        .mobile-only-section {
            display: block !important;
        }
        
        /* Force correct order of sections */
        .mobile-section-order {
            display: flex !important;
            flex-direction: column !important;
        }
        
        .mobile-section-order .privacy-notice {
            order: 1 !important;
        }
        
        .mobile-section-order .verification-timeline {
            order: 2 !important;
        }
    }
    
    /* Custom styles for benefits section on mobile */
    .mobile-benefits-section {
        background: linear-gradient(135deg, #1a2a6c, #2a3a7c);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #3498db;
    }
    
    .mobile-benefits-section .benefits-icon {
        font-size: 40px;
        color: #3498db;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-benefits-section h4 {
        color: #3498db;
        font-size: 18px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-benefits-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    .mobile-benefits-section li {
        color: #fff;
        margin-bottom: 10px;
        font-size: 14px;
        position: relative;
        list-style-type: none;
        padding-left: 25px;
    }
    
    .mobile-benefits-section li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #2ecc71;
        font-weight: bold;
    }
    
    /* Custom styles for privacy notice on mobile */
    .mobile-privacy-section {
        background: linear-gradient(135deg, #6b2e5f, #8e44ad);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #9b59b6;
    }
    
    .mobile-privacy-section .privacy-icon {
        font-size: 40px;
        color: #9b59b6;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-privacy-section h4 {
        color: #ecf0f1;
        font-size: 18px;
        margin-bottom: 10px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-privacy-section p {
        color: #ecf0f1;
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }
    
    /* Custom styles for timeline section on mobile */
    .mobile-timeline-section {
        background: linear-gradient(135deg, #136a8a, #267871);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #2ecc71;
    }
    
    .mobile-timeline-section .timeline-step {
        display: flex;
        margin-bottom: 10px;
    }
    
    .mobile-timeline-section .step-number {
        background-color: #007bff;
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    .mobile-timeline-section .step-content {
        flex-grow: 1;
    }
    
    .mobile-timeline-section .step-title {
        font-weight: bold;
        margin-bottom: 2px;
    }
    
    /* Upload Section Styling */
    .mobile-upload-section {
        background-color: #f0f8ff; /* Light blue background */
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border: 1px solid #d1e7ff;
    }
    
    .mobile-upload-section h4 {
        color: #333;
        margin-bottom: 10px;
        font-size: 18px;
        display: flex;
        align-items: center;
    }
    
    .upload-icon {
        margin-right: 10px;
        color: #007bff;
        font-size: 20px;
    }
    
    .upload-instruction {
        color: #0056b3;
        font-weight: 500;
        background-color: rgba(0,123,255,0.1);
        padding: 8px;
        border-radius: 5px;
        border-left: 3px solid #007bff;
    }
    
    .btn-upload {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-upload:hover {
        background-color: #0069d9;
    }
    
    #submit-verification-mobile {
        width: 100%;
        margin-top: 15px;
        padding: 10px;
        font-weight: bold;
    }
    
    #submit-verification-mobile:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        opacity: 0.65;
    }
    
    #submit-verification-mobile:not(:disabled) {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .preview-container {
        background-color: #fff;
        border-radius: 8px;
        padding: 10px;
        border: 1px solid #ddd;
    }
    
    .preview-container h5 {
        color: #333;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
    }
    
    /* Mobile Requirements Section Styling */
    .mobile-requirements-section {
        background: linear-gradient(135deg, #c31432, #240b36);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #e74c3c;
    }
    
    .mobile-requirements-section .requirements-icon {
        font-size: 40px;
        color: #e74c3c;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-requirements-section h4 {
        color: #ecf0f1;
        font-size: 18px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-requirements-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    .mobile-requirements-section li {
        color: #ecf0f1;
        margin-bottom: 10px;
        font-size: 14px;
        position: relative;
        list-style-type: none;
        padding-left: 25px;
    }
    
    .mobile-requirements-section li:before {
        content: "!";
        position: absolute;
        left: 0;
        color: #f39c12;
        font-weight: bold;
    }
    
    /* Mobile Verification Code Styling */
    .verification-code {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
        padding: 10px;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 2px;
        color: #3498db;
        text-align: center;
        margin: 10px 0;
        border: 1px dashed #3498db;
    }
    
    /* Mobile Form Responsive Fixes */
    @media (max-width: 768px) {
        .verification-container {
            padding: 15px 10px;
        }
        
        .verification-header {
            padding: 20px 15px;
        }
        
        .verification-header h1 {
            font-size: 24px;
        }
        
        .verification-header p {
            font-size: 14px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .mobile-upload-form {
            margin-top: 0;
        }
        
        .mobile-requirements-section,
        .mobile-privacy-section,
        .mobile-benefits-section,
        .mobile-timeline-section,
        .mobile-upload-section {
            margin-top: 15px;
            margin-bottom: 15px;
            padding: 12px;
        }
        
        .mobile-requirements-section h4,
        .mobile-privacy-section h4,
        .mobile-benefits-section h4,
        .mobile-timeline-section h4,
        .mobile-upload-section h4 {
            font-size: 16px;
        }
        
        .mobile-requirements-section li,
        .mobile-benefits-section li {
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .mobile-privacy-section p {
            font-size: 13px;
        }
        
        .timeline-step {
            margin-bottom: 8px;
        }
        
        .step-title {
            font-size: 14px;
        }
        
        .step-description {
            font-size: 12px;
        }
    }
    
    /* Mobile Styling Improvements */
    .mobile-privacy-section, .mobile-benefits-section, .mobile-timeline-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .mobile-privacy-section h4, .mobile-benefits-section h4, .mobile-timeline-section h4 {
        color: #333;
        margin-bottom: 10px;
        font-size: 18px;
        display: flex;
        align-items: center;
    }
    
    .privacy-icon, .benefits-icon, .timeline-icon {
        margin-right: 10px;
        color: #007bff;
        font-size: 20px;
    }
    
    .mobile-benefits-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    .mobile-benefits-section li {
        margin-bottom: 5px;
        color: #555;
    }
    
    .mobile-timeline-section .timeline-step {
        display: flex;
        margin-bottom: 10px;
    }
    
    .mobile-timeline-section .step-number {
        background-color: #007bff;
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    .mobile-timeline-section .step-content {
        flex-grow: 1;
    }
    
    .mobile-timeline-section .step-title {
        font-weight: bold;
        margin-bottom: 2px;
    }
    
    /* Upload Section Styling */
    .mobile-upload-section {
        background-color: #f0f8ff; /* Light blue background */
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border: 1px solid #d1e7ff;
    }
    
    .mobile-upload-section h4 {
        color: #333;
        margin-bottom: 10px;
        font-size: 18px;
        display: flex;
        align-items: center;
    }
    
    .upload-icon {
        margin-right: 10px;
        color: #007bff;
        font-size: 20px;
    }
    
    .upload-instruction {
        color: #0056b3;
        font-weight: 500;
        background-color: rgba(0,123,255,0.1);
        padding: 8px;
        border-radius: 5px;
        border-left: 3px solid #007bff;
    }
    
    .btn-upload {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-upload:hover {
        background-color: #0069d9;
    }
    
    #submit-verification-mobile {
        width: 100%;
        margin-top: 15px;
        padding: 10px;
        font-weight: bold;
    }
    
    #submit-verification-mobile:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        opacity: 0.65;
    }
    
    #submit-verification-mobile:not(:disabled) {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .preview-container {
        background-color: #fff;
        border-radius: 8px;
        padding: 10px;
        border: 1px solid #ddd;
    }
    
    .preview-container h5 {
        color: #333;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
    }
    
    /* Mobile Requirements Section Styling */
    .mobile-requirements-section {
        background: linear-gradient(135deg, #c31432, #240b36);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #e74c3c;
    }
    
    .mobile-requirements-section .requirements-icon {
        font-size: 40px;
        color: #e74c3c;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-requirements-section h4 {
        color: #ecf0f1;
        font-size: 18px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-requirements-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    .mobile-requirements-section li {
        color: #ecf0f1;
        margin-bottom: 10px;
        font-size: 14px;
        position: relative;
        list-style-type: none;
        padding-left: 25px;
    }
    
    .mobile-requirements-section li:before {
        content: "!";
        position: absolute;
        left: 0;
        color: #f39c12;
        font-weight: bold;
    }
    
    /* Mobile Verification Code Styling */
    .verification-code {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
        padding: 10px;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 2px;
        color: #3498db;
        text-align: center;
        margin: 10px 0;
        border: 1px dashed #3498db;
    }
    
    /* Custom styles for privacy notice on mobile */
    .mobile-privacy-section {
        background: linear-gradient(135deg, #6b2e5f, #8e44ad);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #9b59b6;
    }
    
    .mobile-privacy-section .privacy-icon {
        font-size: 40px;
        color: #9b59b6;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-privacy-section h4 {
        color: #ecf0f1;
        font-size: 18px;
        margin-bottom: 10px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-privacy-section p {
        color: #ecf0f1;
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }
    
    /* Custom styles for benefits section on mobile */
    .mobile-benefits-section {
        background: linear-gradient(135deg, #1a2a6c, #2a3a7c);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #3498db;
    }
    
    .mobile-benefits-section .benefits-icon {
        font-size: 40px;
        color: #3498db;
        margin-bottom: 15px;
        text-align: center;
        display: block;
    }
    
    .mobile-benefits-section h4 {
        color: #3498db;
        font-size: 18px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: bold;
    }
    
    .mobile-benefits-section ul {
        padding-left: 20px;
        margin-bottom: 0;
    }
    
    .mobile-benefits-section li {
        color: #fff;
        margin-bottom: 10px;
        font-size: 14px;
        position: relative;
        list-style-type: none;
        padding-left: 25px;
    }
    
    .mobile-benefits-section li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #2ecc71;
        font-weight: bold;
    }
    
    /* Custom styles for timeline section on mobile */
    .mobile-timeline-section {
        background: linear-gradient(135deg, #136a8a, #267871);
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        border: 1px solid #2ecc71;
    }
    
    /* Mobile Form Responsive Fixes */
    @media (max-width: 768px) {
        .verification-container {
            padding: 15px 10px;
        }
        
        .verification-header {
            padding: 20px 15px;
        }
        
        .verification-header h1 {
            font-size: 24px;
        }
        
        .verification-header p {
            font-size: 14px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .mobile-upload-form {
            margin-top: 0;
            display: block !important;
        }
        
        .mobile-requirements-section,
        .mobile-privacy-section,
        .mobile-benefits-section,
        .mobile-timeline-section,
        .mobile-upload-section {
            margin-top: 15px;
            margin-bottom: 15px;
            padding: 12px;
        }
        
        .mobile-requirements-section h4,
        .mobile-privacy-section h4,
        .mobile-benefits-section h4,
        .mobile-timeline-section h4,
        .mobile-upload-section h4 {
            font-size: 16px;
        }
        
        .mobile-requirements-section li,
        .mobile-benefits-section li {
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .mobile-privacy-section p {
            font-size: 13px;
        }
        
        .timeline-step {
            margin-bottom: 8px;
        }
        
        .step-title {
            font-size: 14px;
        }
        
        .step-description {
            font-size: 12px;
        }
    }
}
</style>

<div class="verification-container">
    <!-- Verification Header -->
    <div class="verification-header">
        <div class="row align-items-center">
        <div class="col-md-8">
                <h1 class="mb-2"><i class="fas fa-shield-alt me-2"></i> Verify Your Profile</h1>
                <p class="lead mb-0">Complete the verification process to gain trust and increase your visibility.</p>
        </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="my-listings.php" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-left me-2"></i> Back to My Listings
                </a>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Details Card -->
        <div class="col-lg-4 mb-4">
            <div class="verification-card">
                <div class="card-header-gradient">
                    <i class="fas fa-user me-2"></i> Profile Details
                </div>
                
                    <?php if ($listing['main_image']): ?>
                    <img src="<?php echo $listing['main_image']; ?>" class="profile-image" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    <?php else: ?>
                    <div class="profile-image d-flex align-items-center justify-content-center bg-secondary">
                            <i class="fas fa-image fa-3x text-white-50"></i>
                        </div>
                    <?php endif; ?>
                    
                <div class="p-4">
                    <h4 class="mb-3"><?php echo htmlspecialchars($listing['title']); ?></h4>
                    
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-map-marker-alt text-secondary me-3"></i>
                        <span><?php echo htmlspecialchars($listing['location_name']); ?></span>
                    </div>
                    
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-folder text-secondary me-3"></i>
                        <span><?php echo htmlspecialchars($listing['category_name']); ?></span>
                </div>
                    
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-alt text-secondary me-3"></i>
                        <span>Added: <?php echo date('M d, Y', strtotime($listing['created_at'])); ?></span>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-key fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Your Verification Code</h6>
                                <div class="verification-code"><?php echo $verificationCode; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="row">
                <!-- Verification Instructions Card -->
                <div class="col-12 mb-4">
                    <div class="verification-card">
                        <div class="card-header-gradient card-header-info">
                            <i class="fas fa-info-circle me-2"></i> Verification Instructions
                        </div>
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="mb-4 text-info">Follow these steps to verify your profile:</h5>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex align-items-start">
                                            <div class="step-number">1</div>
                                            <div class="step-content flex-grow-1">
                                                <h6 class="mb-3">Write down your verification code on a piece of paper:</h6>
                                                <div class="verification-code"><?php echo $verificationCode; ?></div>
                                    </div>
                                </div>
                                        </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex align-items-start">
                                            <div class="step-number">2</div>
                                            <div class="step-content flex-grow-1">
                                                <h6>Take a clear selfie while holding the paper with the verification code.</h6>
                                        </div>
                                    </div>
                                </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex align-items-start">
                                            <div class="step-number">3</div>
                                            <div class="step-content flex-grow-1">
                                                <h6>Make sure your face is clearly visible in the photo.</h6>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex align-items-start">
                                            <div class="step-number">4</div>
                                            <div class="step-content flex-grow-1">
                                                <h6>Upload the selfie using the form below.</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Form Card -->
                <div class="col-12 mb-4">
                    <div class="verification-card">
                        <div class="card-header-gradient card-header-success">
                            <i class="fas fa-camera me-2"></i> Upload Verification Photo
                        </div>
                        <div class="p-4">
                            <form method="post" enctype="multipart/form-data" id="verification-form">
                                <input type="hidden" name="listing_id" value="<?php echo $listingId; ?>">
                                <input type="hidden" name="verification_code" value="<?php echo $verificationCode; ?>">
                                
                                <!-- Desktop Upload Form (will be hidden on mobile) -->
                                <div class="row desktop-upload-form">
                                    <div class="col-md-7 mb-4">
                                        <div class="upload-btn-wrapper mb-4">
                                            <div class="upload-btn">
                                                <i class="fas fa-cloud-upload-alt fa-4x mb-3 text-primary"></i>
                                                <h5 class="mb-3">Drag & Drop or Click to Upload</h5>
                                                <p class="mb-0 text-muted">Max file size: 5MB. Accepted formats: JPG, JPEG, PNG.</p>
                                            </div>
                                            <input type="file" name="verification_photo" id="verification_photo" accept="image/jpeg,image/png,image/jpg" required>
                                    </div>
                                    
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                            <label class="form-check-label" for="agree_terms">
                                                I confirm that the information provided is accurate and I agree to the verification process.
                                            </label>
                                        </div>
                                        
                                        <button type="button" class="submit-btn">
                                            <i class="fas fa-upload me-2"></i> Upload Verification Photo
                                        </button>
                                </div>
                                
                                    <div class="col-md-5 mt-3 mt-md-0">
                                    <div class="card bg-dark border-warning h-100">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Important Requirements</h6>
                                            </div>
                                        <div class="card-body">
                                                <ul class="ps-3">
                                                    <li class="mb-3">Your face must be clearly visible</li>
                                                    <li class="mb-3">The verification code must be readable</li>
                                                    <li class="mb-3">The photo must be clear and well-lit</li>
                                                    <li>Hold the paper so the code is visible</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mobile Upload Form -->
                                <div class="mobile-only-section" style="display: none;">
                                    <div class="mobile-upload-form" style="display: block;">
                                        <!-- Verification Code Reminder -->
                                        <div class="card bg-dark border-primary mb-4">
                                            <div class="card-body text-center py-3">
                                                <h6 class="mb-2 text-primary"><i class="fas fa-key me-2"></i> Your Verification Code</h6>
                                                <div class="verification-code mb-0"><?php echo $verificationCode; ?></div>
                                                <small class="text-muted d-block mt-2">Write this code on paper and hold it in your photo</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Requirements Section for Mobile - New Styled Version -->
                                        <div class="mobile-requirements-section">
                                            <i class="fas fa-exclamation-triangle requirements-icon"></i>
                                            <h4>Photo Requirements</h4>
                                            <ul>
                                                <li>Your face must be clearly visible</li>
                                                <li>Verification code must be readable</li>
                                                <li>Photo must be clear and well-lit</li>
                                                <li>Hold paper with code visible</li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Upload Section for Mobile - New Styled Version -->
                                        <div class="mobile-upload-section">
                                            <h4><i class="fas fa-camera upload-icon"></i>Upload Verification Photo</h4>
                                            <p class="upload-instruction" style="color: #0056b3; font-weight: 500; background-color: rgba(0,123,255,0.1); padding: 8px; border-radius: 5px; border-left: 3px solid #007bff;">Please upload a clear photo of yourself holding your code.</p>
                                            
                                            <div class="custom-file-upload">
                                                <label for="verification-photo-mobile" class="btn btn-primary btn-upload" style="background: linear-gradient(135deg, #3498db, #2980b9); border: none; padding: 10px 20px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%;">
                                                    <i class="fas fa-camera"></i> Select Photo
                                                </label>
                                                <input type="file" id="verification-photo-mobile" name="verification_photo" accept="image/*" style="display: none;">
                                    </div>
                                            
                                            <div class="preview-container" style="display: none; margin-top: 15px;">
                                                <h5>Preview:</h5>
                                                <img id="photo-preview-mobile" src="#" alt="Preview" style="max-width: 100%; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                                
                                            <div class="custom-checkbox-container" style="margin-top: 15px;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="terms-agreement-mobile" name="agree_terms">
                                                    <label class="form-check-label" for="terms-agreement-mobile">
                                                        I agree that this photo is of me
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <button type="button" id="submit-verification-mobile" class="btn btn-success mt-3" disabled style="background: linear-gradient(135deg, #2ecc71, #27ae60); border: none; font-weight: bold; padding: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                                <i class="fas fa-check-circle"></i> Submit for Verification
                                    </button>
                                        </div>
                                        
                                        <!-- Privacy Notice for Mobile - New Styled Version -->
                                        <div class="mobile-privacy-section">
                                            <i class="fas fa-shield-alt privacy-icon"></i>
                                            <h4>Privacy Notice</h4>
                                            <p>Your verification photo will be kept private and will only be used for verification purposes. We respect your privacy and data security.</p>
                                        </div>
                                        
                                        <!-- Benefits Section for Mobile - New Styled Version -->
                                        <div class="mobile-benefits-section">
                                            <i class="fas fa-user-shield benefits-icon"></i>
                                            <h4>Benefits of Verification:</h4>
                                            <ul>
                                                <li>Gain trust from potential clients</li>
                                                <li>Get a verified badge on your profile</li>
                                                <li>Appear higher in search results</li>
                                                <li>Increase your booking chances</li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Timeline Section for Mobile - New Styled Version -->
                                        <div class="mobile-timeline-section">
                                            <i class="fas fa-clock timeline-icon"></i>
                                            <h4>Verification Timeline:</h4>
                                            <div class="timeline-step">
                                                <div class="step-number">1</div>
                                                <div class="step-content">
                                                    <div class="step-title">Upload Photo</div>
                                                    <div class="step-description">Upload a clear photo of yourself holding your code</div>
                                                </div>
                                            </div>
                                            <div class="timeline-step">
                                                <div class="step-number">2</div>
                                                <div class="step-content">
                                                    <div class="step-title">Review Process</div>
                                                    <div class="step-description">Our team reviews your verification (24-48 hours)</div>
                                                </div>
                                            </div>
                                            <div class="timeline-step">
                                                <div class="step-number">3</div>
                                                <div class="step-content">
                                                    <div class="step-title">Get Verified</div>
                                                    <div class="step-description">Receive your verified badge and enjoy the benefits</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Previously Uploaded Photos Card -->
                <?php if (!empty($verificationPhotos)): ?>
                <div class="col-12">
                    <div class="verification-card">
                        <div class="card-header-gradient card-header-warning">
                            <i class="fas fa-history me-2"></i> Previously Uploaded Verification Photos
                        </div>
                        <div class="p-4">
                            <div class="photo-gallery">
                                <?php foreach ($verificationPhotos as $photo): ?>
                                    <div class="photo-item">
                                        <img src="<?php echo $photo['photo_path']; ?>" alt="Verification Photo">
                                        
                                        <?php if ($photo['status'] == 'approved'): ?>
                                            <div class="photo-status status-approved">
                                                <i class="fas fa-check-circle me-1"></i> Approved
                                            </div>
                                        <?php elseif ($photo['status'] == 'rejected'): ?>
                                            <div class="photo-status status-rejected">
                                                <i class="fas fa-times-circle me-1"></i> Rejected
                                            </div>
                                                <?php else: ?>
                                            <div class="photo-status status-pending">
                                                <i class="fas fa-clock me-1"></i> Pending
                                            </div>
                                                <?php endif; ?>
                                        
                                        <div class="photo-timestamp">
                                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($photo['submitted_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Ensure the page is properly displayed
    document.addEventListener('DOMContentLoaded', function() {
        // Force header to be visible
        const header = document.querySelector('header');
        if (header) {
            header.style.display = 'block';
            header.style.visibility = 'visible';
            header.style.opacity = '1';
        }
        
        // Force navbar to be visible
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.style.display = 'block';
            navbar.style.visibility = 'visible';
            navbar.style.opacity = '1';
        }
        
        // Simple function to show mobile form on mobile devices
        function showMobileFormOnMobileDevices() {
            if (window.innerWidth <= 768) {
                // Hide desktop form
                const desktopForm = document.querySelector('.desktop-upload-form');
                if (desktopForm) {
                    desktopForm.style.display = 'none';
                }
                
                // Show mobile section
                const mobileSection = document.querySelector('.mobile-only-section');
                if (mobileSection) {
                    mobileSection.style.display = 'block';
                }
                
                // Show mobile form
                const mobileForm = document.querySelector('.mobile-upload-form');
                if (mobileForm) {
                    mobileForm.style.display = 'block';
                }
                
                // Ensure all mobile sections are visible
                const mobileSections = document.querySelectorAll('.mobile-privacy-section, .mobile-benefits-section, .mobile-timeline-section, .mobile-upload-section, .mobile-requirements-section');
                mobileSections.forEach(section => {
                    section.style.display = 'block';
                    section.style.marginBottom = '20px';
                });
            } else {
                // Show desktop form
                const desktopForm = document.querySelector('.desktop-upload-form');
                if (desktopForm) {
                    desktopForm.style.display = 'flex';
                }
                
                // Hide mobile section
                const mobileSection = document.querySelector('.mobile-only-section');
                if (mobileSection) {
                    mobileSection.style.display = 'none';
                }
            }
        }
        
        // Run on page load
        showMobileFormOnMobileDevices();
        
        // Run on window resize
        window.addEventListener('resize', showMobileFormOnMobileDevices);
        
        // Desktop file upload preview
        const fileInput = document.getElementById('verification_photo');
        const uploadBtn = document.querySelector('.upload-btn');
        
        if (fileInput && uploadBtn) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        uploadBtn.innerHTML = `
                            <img src="${e.target.result}" style="max-height: 150px; max-width: 100%;" class="mb-3">
                            <h5>File Selected</h5>
                            <p class="mb-0 text-success">Click the upload button below to submit</p>
                        `;
                        
                        // Apply additional styles to make sure text is visible
                        uploadBtn.style.lineHeight = '1.3';
                        uploadBtn.style.display = 'flex';
                        uploadBtn.style.flexDirection = 'column';
                        uploadBtn.style.alignItems = 'center';
                        uploadBtn.style.justifyContent = 'center';
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        // Mobile file upload functionality
        const mobileFileInput = document.getElementById('verification-photo-mobile');
        const mobilePreviewContainer = document.querySelector('.preview-container');
        const mobilePhotoPreview = document.getElementById('photo-preview-mobile');
        const mobileTermsAgreement = document.getElementById('terms-agreement-mobile');
        const mobileSubmitButton = document.getElementById('submit-verification-mobile');
        
        if (mobileFileInput) {
            mobileFileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        mobilePhotoPreview.src = e.target.result;
                        mobilePreviewContainer.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
        
        if (mobileTermsAgreement) {
            mobileTermsAgreement.addEventListener('change', function() {
                mobileSubmitButton.disabled = !this.checked;
            });
        }
        
        // Form submission handling
        const desktopSubmitButton = document.querySelector('.submit-btn');
        const verificationForm = document.querySelector('form');
        
        // Desktop submit button handler
        if (desktopSubmitButton && verificationForm) {
            desktopSubmitButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the file from desktop input
                const fileInput = document.getElementById('verification_photo');
                if (fileInput && fileInput.files.length > 0) {
                    // Check if terms are agreed
                    const termsCheckbox = document.getElementById('agree_terms');
                    if (termsCheckbox && termsCheckbox.checked) {
                        // Submit the form
                        verificationForm.submit();
                    } else {
                        alert('Please agree to the terms and conditions.');
                    }
                } else {
                    alert('Please select a verification photo.');
                }
            });
        }
        
        // Mobile submit button handler
        if (mobileSubmitButton && verificationForm) {
            mobileSubmitButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the file from mobile input
                const mobileFileInput = document.getElementById('verification-photo-mobile');
                if (mobileFileInput && mobileFileInput.files.length > 0) {
                    // Check if terms are agreed
                    const mobileTermsCheckbox = document.getElementById('terms-agreement-mobile');
                    if (mobileTermsCheckbox && mobileTermsCheckbox.checked) {
                        // Set the file to the main form input
                        const mainFileInput = document.getElementById('verification_photo');
                        if (mainFileInput) {
                            // Create a DataTransfer object and add the file
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(mobileFileInput.files[0]);
                            mainFileInput.files = dataTransfer.files;
                        }
                        
                        // Submit the form
                        verificationForm.submit();
                    } else {
                        alert('Please agree to the terms and conditions.');
                    }
                } else {
                    alert('Please select a verification photo.');
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 