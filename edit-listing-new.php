<?php
// सेशन स्टार्ट
session_start();

// कॉन्फिग और डेटाबेस कनेक्शन
require_once 'includes/config.php';

// चेक करें कि यूजर लॉगिन है या नहीं
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=edit-listing-new.php');
    exit;
}

// यूजर आईडी प्राप्त करें
$userId = $_SESSION['user_id'];

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// चेक करें कि लिस्टिंग इस यूजर की है
$stmt = $db->prepare("
    SELECT l.*, c.name as category_name, loc.name as location_name,
    (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    WHERE l.id = ? AND l.user_id = ?
");
$stmt->execute([$listingId, $userId]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    header('Location: my-listings.php?error=invalid_listing');
    exit;
}

// लिस्टिंग की इमेजेस प्राप्त करें
$stmt = $db->prepare("SELECT * FROM images WHERE listing_id = ? ORDER BY is_main DESC");
$stmt->execute([$listingId]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// सभी कैटेगरीज प्राप्त करें
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// सभी लोकेशन्स प्राप्त करें
$locations = $db->query("SELECT * FROM locations WHERE is_active = 1 ORDER BY name")->fetchAll();

// सोशल मीडिया डेटा प्राप्त करें
$stmt = $db->prepare("SELECT * FROM social_media WHERE listing_id = ? ORDER BY id");
$stmt->execute([$listingId]);
$socialMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// पेज टाइटल सेट करें
$pageTitle = "Edit Listing - " . htmlspecialchars($listing['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #f8f9fa;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-label {
            color: #f8f9fa;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            background-color: #2c2c2c;
            border: 1px solid #444;
            color: #f8f9fa;
            padding: 10px;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: #333;
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            color: #f8f9fa;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .btn-outline-light {
            color: #f8f9fa;
            border-color: #f8f9fa;
        }
        
        .btn-outline-light:hover {
            background-color: #f8f9fa;
            color: #121212;
        }
        
        .btn-outline-secondary {
            color: #adb5bd;
            border-color: #adb5bd;
        }
        
        .btn-outline-secondary:hover {
            background-color: #adb5bd;
            color: #121212;
        }
        
        .text-primary {
            color: #007bff !important;
        }
        
        .text-danger {
            color: #dc3545 !important;
        }
        
        .text-muted {
            color: #adb5bd !important;
        }
        
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        
        .form-check-input {
            background-color: #2c2c2c;
            border: 1px solid #444;
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .form-check-label {
            color: #f8f9fa;
        }
        
        .navbar {
            background-color: #1e1e1e;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        
        .navbar-brand {
            color: #f8f9fa;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .navbar-nav .nav-link {
            color: #adb5bd;
        }
        
        .navbar-nav .nav-link:hover {
            color: #f8f9fa;
        }
        
        .footer {
            background-color: #1e1e1e;
            color: #adb5bd;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .footer h5 {
            color: #f8f9fa;
        }
        
        .footer a {
            color: #adb5bd;
            text-decoration: none;
        }
        
        .footer a:hover {
            color: #f8f9fa;
            text-decoration: underline;
        }
        
        @media (max-width: 767px) {
            .container {
                padding: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .btn + .btn {
                margin-left: 0 !important;
            }
        }
        
        /* Image Preview Styles */
        #image-preview-container .card {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        #image-preview-container .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        #image-preview-container .card img {
            transition: transform 0.3s ease;
        }
        
        #image-preview-container .card:hover img {
            transform: scale(1.05);
        }
        
        #new-images-input {
            padding: 10px;
            background-color: #2c2c2c;
            color: #f8f9fa;
            border: 1px dashed #444;
            border-radius: 8px;
            cursor: pointer;
        }
        
        #new-images-input:hover {
            background-color: #333;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <!-- नेविगेशन बार -->
    <nav class="navbar navbar-expand-lg">
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
                        <a class="nav-link" href="escorts.php">Escorts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="dashboard.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="text-white">Edit Listing</h1>
                <p class="text-muted">Update your listing information</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="my-listings.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i> Back to My Listings
                </a>
            </div>
        </div>
        
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                $error = $_GET['error'];
                if ($error === 'missing_fields') {
                    echo 'Please fill all required fields.';
                } elseif ($error === 'update_failed') {
                    echo 'Failed to update listing. Please try again.';
                } else {
                    echo htmlspecialchars($error);
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Listing updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Edit Listing Details</h5>
            </div>
            <div class="card-body">
                <form method="post" action="process-edit-listing.php" enctype="multipart/form-data">
                    <input type="hidden" name="listing_id" value="<?php echo $listingId; ?>">
                    
                    <div class="row">
                        <!-- टाइटल फील्ड -->
                        <div class="col-12 mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
                        </div>
                        
                        <!-- डिस्क्रिप्शन फील्ड -->
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
                        </div>
                        
                        <!-- कैटेगरी और लोकेशन फील्ड्स -->
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $listing['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- लोकेशन फील्ड -->
                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label">Location</label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['id']; ?>" <?php echo ($location['id'] == $listing['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- फोन नंबर फील्ड -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <select class="form-select" name="country_code" style="max-width: 120px;">
                                    <option value="+91" <?php echo ($listing['country_code'] == '+91') ? 'selected' : ''; ?>>+91 (India)</option>
                                    <option value="+1" <?php echo ($listing['country_code'] == '+1') ? 'selected' : ''; ?>>+1 (USA)</option>
                                    <option value="+44" <?php echo ($listing['country_code'] == '+44') ? 'selected' : ''; ?>>+44 (UK)</option>
                                    <option value="+61" <?php echo ($listing['country_code'] == '+61') ? 'selected' : ''; ?>>+61 (Australia)</option>
                                </select>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($listing['contact_number']); ?>" required>
                            </div>
                        </div>
                        
                        <!-- प्राइस फील्ड -->
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <select class="form-select" name="currency" style="max-width: 100px;">
                                    <option value="INR" <?php echo ($listing['currency'] == 'INR') ? 'selected' : ''; ?>>₹ INR</option>
                                    <option value="USD" <?php echo ($listing['currency'] == 'USD') ? 'selected' : ''; ?>>$ USD</option>
                                    <option value="EUR" <?php echo ($listing['currency'] == 'EUR') ? 'selected' : ''; ?>>€ EUR</option>
                                    <option value="GBP" <?php echo ($listing['currency'] == 'GBP') ? 'selected' : ''; ?>>£ GBP</option>
                                </select>
                                <input type="number" class="form-control" id="price" name="price" value="<?php echo $listing['price']; ?>" min="0" required>
                                <select class="form-select" name="price_type" style="max-width: 150px;">
                                    <option value="per_hour" <?php echo ($listing['price_type'] == 'per_hour') ? 'selected' : ''; ?>>Per Hour</option>
                                    <option value="full_night" <?php echo ($listing['price_type'] == 'full_night') ? 'selected' : ''; ?>>Full Night</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- पर्सनल डिटेल्स -->
                        <div class="col-md-4 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" value="<?php echo $listing['age']; ?>" min="18" max="99" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" id="height" name="height" value="<?php echo $listing['height']; ?>" min="140" max="220">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" value="<?php echo $listing['weight']; ?>" min="40" max="150">
                        </div>
                        
                        <!-- सोशल मीडिया सेक्शन -->
                        <div class="col-12 mt-4">
                            <h4 class="text-primary">Social Media Profiles</h4>
                            <p class="text-muted">Add your social media profiles to connect with clients</p>
                            
                            <div id="social-media-container">
                                <?php if (empty($socialMedia)): ?>
                                <!-- डिफॉल्ट सोशल मीडिया रो -->
                                <div class="row mb-3 social-media-row align-items-center">
                                    <div class="col-md-4">
                                        <select class="form-select social-media-type" name="social_media_type[]">
                                            <option value="">Select Platform</option>
                                            <option value="whatsapp">WhatsApp</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="telegram">Telegram</option>
                                            <option value="facebook">Facebook</option>
                                            <option value="snapchat">Snapchat</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="onlyfans">OnlyFans</option>
                                            <option value="twitter">Twitter (X)</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select social-media-input-type" name="social_media_input_type[]">
                                            <option value="username">Username</option>
                                            <option value="link">Link</option>
                                            <option value="number" class="whatsapp-only" style="display:none;">Number</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text social-icon-container">
                                                <i class="fab fa-question social-icon"></i>
                                            </span>
                                            <input type="text" class="form-control" name="social_media_value[]" placeholder="Username/Number/Link">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger remove-social-media">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($socialMedia as $social): ?>
                                    <div class="row mb-3 social-media-row align-items-center">
                                        <div class="col-md-4">
                                            <select class="form-select social-media-type" name="social_media_type[]">
                                                <option value="">Select Platform</option>
                                                <option value="whatsapp" <?php echo ($social['type'] == 'whatsapp') ? 'selected' : ''; ?>>WhatsApp</option>
                                                <option value="instagram" <?php echo ($social['type'] == 'instagram') ? 'selected' : ''; ?>>Instagram</option>
                                                <option value="telegram" <?php echo ($social['type'] == 'telegram') ? 'selected' : ''; ?>>Telegram</option>
                                                <option value="facebook" <?php echo ($social['type'] == 'facebook') ? 'selected' : ''; ?>>Facebook</option>
                                                <option value="snapchat" <?php echo ($social['type'] == 'snapchat') ? 'selected' : ''; ?>>Snapchat</option>
                                                <option value="tiktok" <?php echo ($social['type'] == 'tiktok') ? 'selected' : ''; ?>>TikTok</option>
                                                <option value="onlyfans" <?php echo ($social['type'] == 'onlyfans') ? 'selected' : ''; ?>>OnlyFans</option>
                                                <option value="twitter" <?php echo ($social['type'] == 'twitter') ? 'selected' : ''; ?>>Twitter (X)</option>
                                                <option value="other" <?php echo ($social['type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <?php 
                                            // Determine input type based on value format
                                            $inputType = 'username';
                                            if (strpos($social['value'], 'http') === 0) {
                                                $inputType = 'link';
                                            } elseif ($social['type'] == 'whatsapp' && is_numeric(preg_replace('/[^0-9]/', '', $social['value']))) {
                                                $inputType = 'number';
                                            }
                                            ?>
                                            <select class="form-select social-media-input-type" name="social_media_input_type[]">
                                                <option value="username" <?php echo ($inputType == 'username') ? 'selected' : ''; ?>>Username</option>
                                                <option value="link" <?php echo ($inputType == 'link') ? 'selected' : ''; ?>>Link</option>
                                                <option value="number" class="whatsapp-only" <?php echo ($inputType == 'number') ? 'selected' : ''; ?> <?php echo ($social['type'] != 'whatsapp') ? 'style="display:none;"' : ''; ?>>Number</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text social-icon-container">
                                                    <i class="fab fa-<?php echo $social['type'] == 'twitter' ? 'twitter' : ($social['type'] == 'other' ? 'question' : $social['type']); ?> social-icon"></i>
                                                </span>
                                                <input type="text" class="form-control" name="social_media_value[]" placeholder="Username/Number/Link" value="<?php echo htmlspecialchars($social['value']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger remove-social-media">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary" id="add-social-media">
                                    <i class="fas fa-plus me-1"></i> Add Social Media
                                </button>
                            </div>
                        </div>
                        
                        <!-- इमेजेस सेक्शन -->
                        <div class="col-12 mt-4">
                            <h4 class="text-primary">Current Images</h4>
                            <div class="row">
                                <?php foreach ($images as $image): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <img src="<?php echo $image['image_path']; ?>" class="card-img-top" alt="Listing Image">
                                            <div class="card-body p-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="main_image" value="<?php echo $image['id']; ?>" <?php echo ($image['is_main']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Main Image</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                                                    <label class="form-check-label text-danger">Delete</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <h4 class="text-primary">Add New Images</h4>
                            <div class="mb-3">
                                <input type="file" class="form-control" name="new_images[]" id="new-images-input" accept="image/*" multiple>
                                <small class="text-muted">You can select multiple images. Max file size: 5MB each.</small>
                            </div>
                            
                            <!-- Image Preview Section -->
                            <div id="image-preview-container" class="row mt-3">
                                <!-- Preview images will be added here dynamically -->
                            </div>
                        </div>
                        
                        <!-- सबमिट बटन -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                            <a href="my-listings.php" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- फुटर -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Escort Directory</h5>
                    <p>Your one-stop solution for finding the best escort services.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="escorts.php">Escorts</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2023 Escort Directory. All rights reserved. | 18+ Only</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // फॉर्म वैलिडेशन
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // बेसिक वैलिडेशन
                    const title = document.getElementById('title').value.trim();
                    const description = document.getElementById('description').value.trim();
                    const categoryId = document.getElementById('category_id').value;
                    const locationId = document.getElementById('location_id').value;
                    
                    if (!title || !description || !categoryId || !locationId) {
                        e.preventDefault();
                        alert('Please fill all required fields');
                    }
                });
            }
            
            // सोशल मीडिया फंक्शनैलिटी
            const socialMediaContainer = document.getElementById('social-media-container');
            const addSocialMediaBtn = document.getElementById('add-social-media');
            
            // सोशल मीडिया आइकन अपडेट करने का फंक्शन
            function updateSocialMediaIcon(selectElement) {
                const platform = selectElement.value;
                const row = selectElement.closest('.social-media-row');
                const iconElement = row.querySelector('.social-icon');
                const inputTypeSelect = row.querySelector('.social-media-input-type');
                const numberOption = inputTypeSelect.querySelector('.whatsapp-only');
                
                // आइकन अपडेट करें
                let iconClass = 'fab fa-question';
                
                switch (platform) {
                    case 'facebook':
                        iconClass = 'fab fa-facebook';
                        break;
                    case 'twitter':
                        iconClass = 'fab fa-twitter';
                        break;
                    case 'instagram':
                        iconClass = 'fab fa-instagram';
                        break;
                    case 'telegram':
                        iconClass = 'fab fa-telegram';
                        break;
                    case 'whatsapp':
                        iconClass = 'fab fa-whatsapp';
                        break;
                    case 'snapchat':
                        iconClass = 'fab fa-snapchat';
                        break;
                    case 'tiktok':
                        iconClass = 'fab fa-tiktok';
                        break;
                    case 'onlyfans':
                        iconClass = 'fas fa-heart'; // OnlyFans का कोई आधिकारिक आइकन नहीं है
                        break;
                    default:
                        iconClass = 'fab fa-question';
                }
                
                // आइकन क्लास अपडेट करें
                iconElement.className = iconClass + ' social-icon';
                
                // WhatsApp के लिए नंबर ऑप्शन दिखाएं या छिपाएं
                if (platform === 'whatsapp') {
                    numberOption.style.display = '';
                } else {
                    numberOption.style.display = 'none';
                    // अगर WhatsApp नहीं है और नंबर सेलेक्ट है, तो यूजरनेम पर सेट करें
                    if (inputTypeSelect.value === 'number') {
                        inputTypeSelect.value = 'username';
                    }
                }
            }
            
            // सोशल मीडिया रो जोड़ने का फंक्शन
            function addSocialMediaRow() {
                const socialMediaRow = document.createElement('div');
                socialMediaRow.className = 'row mb-3 social-media-row align-items-center';
                
                socialMediaRow.innerHTML = `
                    <div class="col-md-4">
                        <select class="form-select social-media-type" name="social_media_type[]">
                            <option value="">Select Platform</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="instagram">Instagram</option>
                            <option value="telegram">Telegram</option>
                            <option value="facebook">Facebook</option>
                            <option value="snapchat">Snapchat</option>
                            <option value="tiktok">TikTok</option>
                            <option value="onlyfans">OnlyFans</option>
                            <option value="twitter">Twitter (X)</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select social-media-input-type" name="social_media_input_type[]">
                            <option value="username">Username</option>
                            <option value="link">Link</option>
                            <option value="number" class="whatsapp-only" style="display:none;">Number</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text social-icon-container">
                                <i class="fab fa-question social-icon"></i>
                            </span>
                            <input type="text" class="form-control" name="social_media_value[]" placeholder="Username/Number/Link">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger remove-social-media">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                
                socialMediaContainer.appendChild(socialMediaRow);
                
                // नए रो के लिए इवेंट लिसनर्स जोड़ें
                const selectElement = socialMediaRow.querySelector('.social-media-type');
                selectElement.addEventListener('change', function() {
                    updateSocialMediaIcon(this);
                });
                
                const removeButton = socialMediaRow.querySelector('.remove-social-media');
                removeButton.addEventListener('click', function() {
                    socialMediaRow.remove();
                });
            }
            
            // "Add Social Media" बटन पर क्लिक इवेंट
            if (addSocialMediaBtn) {
                addSocialMediaBtn.addEventListener('click', addSocialMediaRow);
            }
            
            // मौजूदा सोशल मीडिया रोज़ के लिए इवेंट लिसनर्स
            const existingSocialMediaTypes = document.querySelectorAll('.social-media-type');
            existingSocialMediaTypes.forEach(function(select) {
                select.addEventListener('change', function() {
                    updateSocialMediaIcon(this);
                });
                
                // पेज लोड होने पर आइकन अपडेट करें
                updateSocialMediaIcon(select);
            });
            
            // मौजूदा रिमूव बटन्स के लिए इवेंट लिसनर्स
            const existingRemoveButtons = document.querySelectorAll('.remove-social-media');
            existingRemoveButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    this.closest('.social-media-row').remove();
                });
            });
            
            // इमेज प्रीव्यू फंक्शनैलिटी
            const newImagesInput = document.getElementById('new-images-input');
            const imagePreviewContainer = document.getElementById('image-preview-container');
            let selectedFiles = []; // चुनी गई फाइल्स का ट्रैक रखने के लिए
            
            // फाइल इनपुट से फाइल्स को अपडेट करने का फंक्शन
            function updateFileInput() {
                // फाइल इनपुट को रीसेट करने के लिए एक नया FileList नहीं बना सकते
                // इसलिए हम एक नया DataTransfer ऑब्जेक्ट बनाते हैं
                const dataTransfer = new DataTransfer();
                
                // सभी चुनी गई फाइल्स को जोड़ें
                selectedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                
                // फाइल इनपुट को अपडेट करें
                newImagesInput.files = dataTransfer.files;
            }
            
            // इमेज प्रीव्यू अपडेट करने का फंक्शन
            function updateImagePreviews() {
                // पहले मौजूदा प्रीव्यू साफ करें
                imagePreviewContainer.innerHTML = '';
                
                // चुनी गई फाइल्स के लिए प्रीव्यू बनाएं
                if (selectedFiles.length > 0) {
                    selectedFiles.forEach((file, index) => {
                        // चेक करें कि फाइल इमेज है
                        if (!file.type.startsWith('image/')) {
                            return;
                        }
                        
                        // प्रीव्यू कार्ड बनाएं
                        const previewCol = document.createElement('div');
                        previewCol.className = 'col-md-3 mb-3';
                        
                        const previewCard = document.createElement('div');
                        previewCard.className = 'card bg-dark';
                        
                        // फाइल रीडर बनाएं
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewCard.innerHTML = `
                                <div class="position-relative">
                                    <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-image" data-index="${index}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="card-body p-2 text-center">
                                    <small class="text-truncate d-block">${file.name}</small>
                                    <small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                                </div>
                            `;
                            
                            // रिमूव बटन के लिए इवेंट लिसनर जोड़ें
                            const removeBtn = previewCard.querySelector('.remove-image');
                            removeBtn.addEventListener('click', function() {
                                const imageIndex = parseInt(this.getAttribute('data-index'));
                                // फाइल को हटाएं
                                selectedFiles.splice(imageIndex, 1);
                                // फाइल इनपुट और प्रीव्यू अपडेट करें
                                updateFileInput();
                                updateImagePreviews();
                            });
                        };
                        
                        // फाइल पढ़ें
                        reader.readAsDataURL(file);
                        
                        // कार्ड को कंटेनर में जोड़ें
                        previewCol.appendChild(previewCard);
                        imagePreviewContainer.appendChild(previewCol);
                    });
                }
            }
            
            if (newImagesInput) {
                newImagesInput.addEventListener('change', function() {
                    // नई फाइल्स को सेलेक्टेड फाइल्स में जोड़ें
                    if (this.files && this.files.length > 0) {
                        for (let i = 0; i < this.files.length; i++) {
                            // चेक करें कि फाइल पहले से सेलेक्ट नहीं है
                            const isDuplicate = selectedFiles.some(file => 
                                file.name === this.files[i].name && 
                                file.size === this.files[i].size && 
                                file.type === this.files[i].type
                            );
                            
                            if (!isDuplicate) {
                                selectedFiles.push(this.files[i]);
                            }
                        }
                    }
                    
                    // फाइल इनपुट और प्रीव्यू अपडेट करें
                    updateFileInput();
                    updateImagePreviews();
                });
            }
        });
    </script>
</body>
</html> 