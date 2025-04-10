<?php
// Add error reporting at the top
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database connection with your correct details
try {
    $host = '127.0.0.1:8889';
    $dbname = 'ankit';
    $username = 'root';
    $password = 'root';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch required data
try {
    $categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $locations = $db->query("SELECT * FROM locations")->fetchAll(PDO::FETCH_ASSOC);
    $tags = $db->query("SELECT * FROM tags")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Listing</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css" rel="stylesheet">
    
    <style>
        :root {
            --dark-bg: #1a1a1a;
            --card-bg: #2c2c2c;
            --input-bg: #333;
            --border-color: #444;
            --text-color: #fff;
            --primary-color: #007bff;
            --label-color: #ffffff;
            --placeholder-color: rgba(255, 255, 255, 0.7);
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-size: 14px;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        
        .form-control, .form-select {
            background-color: var(--input-bg);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 14px;
            height: 38px;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            border-color: #007bff;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .section {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Location Search */
        .location-dropdown {
            position: absolute;
            z-index: 1000;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .location-item {
            padding: 8px 12px;
            cursor: pointer;
            color: var(--text-color);
        }
        
        .location-item:hover {
            background: var(--primary-color);
        }
        
        /* Country Code Selector */
        .country-dropdown {
            position: absolute;
            z-index: 1000;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 300px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .country-search {
            padding: 8px;
            position: sticky;
            top: 0;
            background: var(--card-bg);
        }
        
        .country-item {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .country-item:hover {
            background: var(--primary-color);
        }
        
        .country-flag {
            font-size: 20px;
        }
        
        /* Image Upload */
        .image-upload {
            border: 2px dashed rgba(255, 255, 255, 0.4);
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }
        
        .image-upload:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
        }
        
        .image-upload p {
            color: white;
            font-weight: 500;
            margin-top: 10px;
            font-size: 15px;
        }
        
        .image-upload i {
            color: rgba(255, 255, 255, 0.8);
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .image-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
            filter: brightness(1.1);
        }
        
        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            background: rgba(255, 255, 255, 0.8);
            color: #000;
            border-radius: 50%;
            padding: 5px;
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 14px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }
        
        /* Tags Input */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 5px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            min-height: 38px;
        }
        
        .tag {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .tag-remove {
            cursor: pointer;
        }
        
        .tag-input {
            border: none;
            background: transparent;
            color: var(--text-color);
            outline: none;
            flex: 1;
            min-width: 60px;
        }
        
        .section-header {
            color: white;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        .main-image-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            z-index: 2;
        }
        
        .pricing-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .pricing-options .form-select {
            min-width: 90px;
            flex: 0 0 auto;
            padding-right: 25px;
        }
        
        .pricing-options .input-group > .form-control {
            flex: 1 1 auto;
        }
        
        /* Social Media Buttons */
        .social-media-btn {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 8px 15px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .social-media-btn i {
            font-size: 20px;
        }
        
        .social-media-btn:hover {
            background: var(--primary-color);
        }
        
        /* प्लेसहोल्डर कलर को लाइट करें */
        .form-control::placeholder,
        .form-select::placeholder,
        .tag-input::placeholder {
            color: var(--placeholder-color) !important;
            opacity: 1;
        }
        
        /* इनपुट फील्ड्स का टेक्स्ट कलर */
        .form-control,
        .form-select,
        .tag-input {
            color: var(--text-color) !important;
        }
        
        /* सेलेक्ट ऑप्शन्स का कलर */
        .form-select option {
            background-color: var(--input-bg);
            color: var(--text-color);
        }
        
        /* टेक्स्टएरिया का प्लेसहोल्डर */
        textarea.form-control::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
            opacity: 1;
        }
        
        /* टैग इनपुट ग्रुप की स्टाइलिंग */
        .tag-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        
        .tag-input {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            outline: none;
            padding: 6px 12px;
            border-radius: 4px;
            flex-grow: 1;
        }
        
        /* टैग्स की स्टाइलिंग */
        .tags-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
            min-height: 35px;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .tag-remove {
            margin-left: 6px;
            cursor: pointer;
            font-size: 16px;
            opacity: 0.8;
        }
        
        .tag-remove:hover {
            opacity: 1;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 5px;
            font-size: 14px;
        }
        
        .tag-delete {
            background: none;
            border: none;
            color: white;
            margin-left: 8px;
            cursor: pointer;
            font-size: 16px;
            padding: 0 5px;
        }
        
        .tag-delete:hover {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        
        .tags-wrapper {
            min-height: 40px;
            padding: 5px;
        }
        
        /* मेन इमेज के लिए स्टाइल */
        .preview-item.is-main {
            border: 2px solid var(--primary-color);
        }
        
        /* अवेलेबिलिटी सेक्शन के लिए स्टाइल */
        .day-selector .btn {
            margin-right: 3px;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            min-width: 45px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .day-selector .btn-check:checked + .btn-outline-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.5);
            transform: translateY(-1px);
        }
        
        .day-selector .btn-outline-primary {
            color: #ffffff;
            border-color: rgba(255,255,255,0.3);
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .day-selector .btn-outline-primary:hover {
            background-color: rgba(0,123,255,0.3);
            border-color: var(--primary-color);
            color: white;
        }
        
        /* स्पेशल ऑफर सेक्शन के लिए स्टाइल */
        .special-offer-container {
            border: 1px solid var(--border-color);
        }
        
        /* नेगोशिएबल चेकबॉक्स के लिए स्टाइल */
        .form-check {
            display: flex;
            align-items: center;
        }
        
        .form-check-input {
            margin-right: 8px;
        }
        
        /* टेक्स्टएरिया की हाइट बढ़ाएं */
        textarea.form-control[name="description"] {
            min-height: 180px;
        }
        
        /* स्पेशल ऑफर की हाइट बढ़ाएं */
        textarea.form-control[name="special_offer"] {
            min-height: 100px;
        }
        
        /* अवेलेबिलिटी दिनों का ग्रिड */
        .day-selector .btn-group {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .day-selector .btn {
            flex: none;
            margin: 0;
            padding: 8px 0;
            border-radius: 4px;
            font-weight: 600;
        }
        
        /* करेंसी सेलेक्ट फिक्स */
        .input-group > .form-select {
            width: auto;
            min-width: 90px;
        }
        
        /* सबमिट बटन स्टाइल */
        .submit-button-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-submit {
            min-width: 200px;
            padding: 12px 20px;
            font-weight: 600;
        }
        
        /* प्लेसहोल्डर कलर लाइट करें */
        .form-control::placeholder, 
        textarea::placeholder,
        select::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
            opacity: 1;
        }
        
        /* इनपुट्स टेक्स्ट कलर */
        input, textarea, select, .form-select, .btn {
            color: white !important;
            font-weight: 500;
        }
        
        /* फोकस पर बॉर्डर और ग्लो इफेक्ट */
        .form-control:focus, 
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* फॉर्म लेबल्स */
        .form-label {
            color: white;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 8px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-body">
                    <form id="listingForm" action="process-listing.php" method="post" enctype="multipart/form-data">
                        
                        <!-- Basic Information -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-info-circle"></i> Basic Information
                            </h3>
                            
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" 
                                       placeholder="Enter your listing title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="4" 
                                          placeholder="Describe your listing in detail..." required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-map-marker-alt"></i> Location
                            </h3>
                            
                            <div class="mb-3">
                                <input type="text" class="form-control" id="locationSearch" 
                                       placeholder="Search and select your location...">
                                <div id="locationDropdown" class="location-dropdown d-none"></div>
                            </div>
                            
                            <div id="selectedLocation" class="d-none">
                                <div class="alert alert-info">
                                    Selected: <span id="locationText"></span>
                                    <button type="button" class="btn-close float-end" id="clearLocation"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-phone"></i> Contact Information
                            </h3>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="phoneCountrySelect">
                                        <span class="country-flag">🇮🇳</span>
                                        <span class="country-code">+91</span>
                                    </button>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fab fa-whatsapp text-success"></i> WhatsApp Number
                                </label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="whatsappCountrySelect">
                                        <span class="country-flag">🇮🇳</span>
                                        <span class="country-code">+91</span>
                                    </button>
                                    <input type="tel" class="form-control" name="whatsapp">
                                </div>
                            </div>
                        </div>

                        <!-- Physical Attributes -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-user"></i> Physical Attributes
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Height</label>
                                    <select class="form-select" name="height">
                                        <option value="">Select Height</option>
                                        <?php
                                        for($feet = 4; $feet <= 7; $feet++) {
                                            for($inch = 0; $inch <= 11; $inch++) {
                                                $value = ($feet * 12) + $inch;
                                                echo "<option value='$value'>{$feet}'{$inch}\"</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" name="weight" min="30" max="150">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Body Type</label>
                                    <select class="form-select" name="body_type">
                                        <option value="">Select Body Type</option>
                                        <option value="slim">Slim</option>
                                        <option value="athletic">Athletic</option>
                                        <option value="average">Average</option>
                                        <option value="curvy">Curvy</option>
                                        <option value="plus_size">Plus Size</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-images"></i> Images
                            </h3>
                            
                            <div class="image-upload" id="imageUpload">
                                <i class="fas fa-cloud-upload-alt mb-2"></i>
                                <p class="mb-0">Drag & drop images here or <strong>click to select</strong></p>
                                <input type="file" id="imageInput" multiple accept="image/*" class="d-none">
                            </div>
                            
                            <div class="image-preview mt-3" id="imagePreview"></div>
                        </div>

                        <!-- Pricing -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-tag"></i> Pricing
                            </h3>
                            
                            <div class="pricing-options">
                                <div class="mb-3">
                                    <label class="form-label">Full Night Price</label>
                                    <div class="input-group">
                                        <select class="form-select" name="currency" style="width: 80px; flex: 0 0 80px;">
                                            <option value="INR">₹ INR</option>
                                            <option value="USD">$ USD</option>
                                            <option value="EUR">€ EUR</option>
                                            <option value="GBP">£ GBP</option>
                                        </select>
                                        <input type="number" class="form-control" name="full_night_price"
                                               placeholder="Enter full night price">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Per Hour Price</label>
                                    <input type="number" class="form-control" name="hourly_price"
                                           placeholder="Enter per hour price">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                                    <input class="form-check-input pricing-option" type="radio" name="price_option" id="negotiableCheck" value="negotiable">
                                    <label class="form-check-label" for="negotiableCheck" style="color: #fff;">Price Negotiable</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                                    <input class="form-check-input pricing-option" type="radio" name="price_option" id="fixedPriceCheck" value="fixed" checked>
                                    <label class="form-check-label" for="fixedPriceCheck" style="color: #fff;">Fixed Price (Non-negotiable)</label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="m-0" style="color: #fff; font-size: 16px; font-weight: 500;">Special Offer</h4>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="noDiscountCheck">
                                        <label class="form-check-label" for="noDiscountCheck" style="color: #fff;">No Discount</label>
                                    </div>
                                </div>
                                
                                <div class="special-offer-container p-3" style="background: rgba(255,255,255,0.05); border-radius: 8px;" id="discountSection">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Discount Type</label>
                                            <select class="form-select" name="discount_type">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Amount Discount</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Discount Value</label>
                                            <input type="number" class="form-control" name="discount_value" placeholder="Enter discount">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Valid From</label>
                                            <input type="datetime-local" class="form-control" name="discount_start">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Valid Till</label>
                                            <input type="datetime-local" class="form-control" name="discount_end">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #fff; font-weight: 500;">Client Type</label>
                                        <select class="form-select" name="client_type">
                                            <option value="all">All Clients</option>
                                            <option value="new">New Clients Only</option>
                                            <option value="regular">Regular Clients Only</option>
                                            <option value="vip">VIP Clients Only</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label" style="color: #fff; font-weight: 500;">Offer Description</label>
                                        <textarea class="form-control" name="special_offer" rows="2"
                                            placeholder="Add any additional details about your special offer"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-clock"></i> Availability
                            </h3>
                            
                            <div class="availability-container">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="form-check mb-2" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                                            <input class="form-check-input" type="checkbox" id="availableAllTime" name="available_all_time">
                                            <label class="form-check-label" for="availableAllTime" style="color: #fff; font-weight: 500;">
                                                Available 24/7
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="customAvailability">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Available Days</label>
                                            <div class="day-selector">
                                                <div class="btn-group" role="group">
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="mon" id="day-mon">
                                                    <label class="btn btn-outline-primary" for="day-mon">Mon</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="tue" id="day-tue">
                                                    <label class="btn btn-outline-primary" for="day-tue">Tue</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="wed" id="day-wed">
                                                    <label class="btn btn-outline-primary" for="day-wed">Wed</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="thu" id="day-thu">
                                                    <label class="btn btn-outline-primary" for="day-thu">Thu</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="fri" id="day-fri">
                                                    <label class="btn btn-outline-primary" for="day-fri">Fri</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="sat" id="day-sat">
                                                    <label class="btn btn-outline-primary" for="day-sat">Sat</label>
                                                    
                                                    <input type="checkbox" class="btn-check" name="available_days[]" value="sun" id="day-sun">
                                                    <label class="btn btn-outline-primary" for="day-sun">Sun</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Available From</label>
                                            <input type="time" class="form-control" name="available_from">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" style="color: #fff; font-weight: 500;">Available Till</label>
                                            <input type="time" class="form-control" name="available_till">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-share-alt"></i> Social Media
                            </h3>
                            
                            <div class="social-media-grid" id="socialMediaContainer">
                                <!-- Social media inputs will be added here -->
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addSocialMedia">
                                <i class="fas fa-plus"></i> Add Social Media
                            </button>
                        </div>

                        <!-- Tags -->
                        <div class="section mb-4">
                            <h3 class="section-header">
                                <i class="fas fa-tags"></i> Tags
                            </h3>
                            
                            <div class="tags-container" id="tagsContainer">
                                <div class="tags-wrapper" id="tagsWrapper"></div>
                                <div class="tag-input-group">
                                    <input type="text" class="tag-input" id="tagInput" placeholder="Enter your tag">
                                    <button type="button" class="btn btn-primary btn-sm" id="addTagBtn">
                                        <i class="fas fa-plus"></i> Add Tag
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="submit-button-container">
                            <button type="submit" class="btn btn-primary btn-submit">
                                <i class="fas fa-save me-2"></i> Submit Listing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Preview" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script src="assets/js/countries.js"></script>

<script>
$(document).ready(function() {
    // Location Search
    const locationSearch = document.getElementById('locationSearch');
    const locationDropdown = document.getElementById('locationDropdown');
    const selectedLocation = document.getElementById('selectedLocation');

    locationSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        if(searchTerm.length < 2) {
            locationDropdown.classList.add('d-none');
            return;
        }

        // Simulate location search (replace with your actual location data)
        const locations = [
            'New Delhi, India',
            'Mumbai, India',
            'Bangalore, India',
            'Chennai, India',
            'Kolkata, India',
            // Add more locations
        ];

        const filtered = locations.filter(loc => 
            loc.toLowerCase().includes(searchTerm)
        );

        if(filtered.length > 0) {
            locationDropdown.innerHTML = filtered.map(loc => `
                <div class="location-item" onclick="selectLocation('${loc}')">${loc}</div>
            `).join('');
            locationDropdown.classList.remove('d-none');
        } else {
            locationDropdown.classList.add('d-none');
        }
    });

    function selectLocation(location) {
        selectedLocation.value = location;
        locationSearch.value = location;
        locationDropdown.classList.add('d-none');
    }

    // Country Code Selector
    function createCountryDropdown(buttonId) {
        const button = document.getElementById(buttonId);
        const dropdown = document.createElement('div');
        dropdown.className = 'country-dropdown d-none';
        
        dropdown.innerHTML = `
            <div class="country-search">
                <input type="text" class="form-control" placeholder="Search country...">
            </div>
            <div class="country-list">
                ${countries.map(country => `
                    <div class="country-item" data-code="${country.dial_code}" data-flag="${country.flag}">
                        <span class="country-flag">${country.flag}</span>
                        <span>${country.name}</span>
                        <span class="ms-auto">${country.dial_code}</span>
                    </div>
                `).join('')}
            </div>
        `;
        
        document.body.appendChild(dropdown);
        
        const searchInput = dropdown.querySelector('input');
        const countryList = dropdown.querySelector('.country-list');
        
        button.addEventListener('click', () => {
            const rect = button.getBoundingClientRect();
            dropdown.style.top = `${rect.bottom + 5}px`;
            dropdown.style.left = `${rect.left}px`;
            dropdown.classList.remove('d-none');
        });
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const items = countryList.querySelectorAll('.country-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        dropdown.addEventListener('click', (e) => {
            const item = e.target.closest('.country-item');
            if(item) {
                const code = item.dataset.code;
                const flag = item.dataset.flag;
                button.querySelector('.country-flag').textContent = flag;
                button.querySelector('.country-code').textContent = code;
                dropdown.classList.add('d-none');
            }
        });
        
        document.addEventListener('click', (e) => {
            if(!button.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });
    }

    createCountryDropdown('phoneCountrySelect');
    createCountryDropdown('whatsappCountrySelect');

    // Image Upload
    const imageUpload = document.getElementById('imageUpload');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    imageUpload.addEventListener('click', () => imageInput.click());

    imageUpload.addEventListener('dragover', (e) => {
        e.preventDefault();
        imageUpload.style.borderColor = 'var(--primary-color)';
    });

    imageUpload.addEventListener('dragleave', () => {
        imageUpload.style.borderColor = 'var(--border-color)';
    });

    imageUpload.addEventListener('drop', (e) => {
        e.preventDefault();
        imageUpload.style.borderColor = 'var(--border-color)';
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    imageInput.addEventListener('change', () => {
        handleFiles(imageInput.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if(file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}">
                        <div class="remove-image" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </div>
                    `;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Social Media Platforms
    const socialPlatforms = [
        { name: 'Instagram', icon: 'fab fa-instagram', color: '#E1306C' },
        { name: 'Facebook', icon: 'fab fa-facebook', color: '#4267B2' },
        { name: 'Telegram', icon: 'fab fa-telegram', color: '#0088cc' },
        { name: 'Snapchat', icon: 'fab fa-snapchat', color: '#FFFC00' },
        { name: 'LinkedIn', icon: 'fab fa-linkedin', color: '#0077b5' },
        { name: 'TikTok', icon: 'fab fa-tiktok', color: '#000000' },
        { name: 'OnlyFans', icon: 'fas fa-heart', color: '#00AFF0' },
        { name: 'YouTube', icon: 'fab fa-youtube', color: '#FF0000' },
        { name: 'Other', icon: 'fas fa-link', color: '#808080' }
    ];

    document.getElementById('addSocialMedia').addEventListener('click', function() {
        const container = document.getElementById('socialMediaContainer');
        
        socialPlatforms.forEach(platform => {
            if (!container.querySelector(`[data-platform="${platform.name}"]`)) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'social-media-btn';
                button.dataset.platform = platform.name;
                button.innerHTML = `
                    <i class="${platform.icon}" style="color: ${platform.color}"></i>
                    Add ${platform.name}
                `;
                
                button.addEventListener('click', function() {
                    const input = document.createElement('div');
                    input.className = 'input-group mb-3';
                    input.innerHTML = `
                        <span class="input-group-text">
                            <i class="${platform.icon}" style="color: ${platform.color}"></i>
                        </span>
                        <input type="text" class="form-control" name="social_media[${platform.name}]" 
                               placeholder="${platform.name} username or link">
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    this.replaceWith(input);
                });
                
                container.appendChild(button);
            }
        });
    });

    // Tags System
    const tagInput = document.getElementById('tagInput');
    const tagsWrapper = document.getElementById('tagsWrapper');
    const addTagBtn = document.getElementById('addTagBtn');
    
    // टैग एड करने का फंक्शन
    function addNewTag() {
        const tagText = tagInput.value.trim();
        if (!tagText) return;
        
        // Create tag element
        const tagElement = document.createElement('div');
        tagElement.className = 'tag';
        
        // Add tag text
        const textSpan = document.createElement('span');
        textSpan.textContent = tagText;
        tagElement.appendChild(textSpan);
        
        // Add delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'tag-delete';
        deleteBtn.textContent = '×';
        deleteBtn.onclick = function() {
            tagElement.remove();
        };
        tagElement.appendChild(deleteBtn);
        
        // Add to wrapper
        tagsWrapper.appendChild(tagElement);
        
        // Clear input
        tagInput.value = '';
    }
    
    // Add tag button click
    addTagBtn.addEventListener('click', addNewTag);
    
    // Optional: Add tag on Enter key
    tagInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addNewTag();
        }
    });

    // Special Offer discount type handling
    $('select[name="discount_type"]').on('change', function() {
        const discountType = $(this).val();
        const valueInput = $('input[name="discount_value"]');
        
        if(discountType === 'percentage') {
            valueInput.attr('placeholder', 'Enter percentage (e.g. 20)');
            valueInput.attr('max', '100');
        } else {
            valueInput.attr('placeholder', 'Enter amount');
            valueInput.removeAttr('max');
        }
    });
    
    // Availability toggle
    $('#availableAllTime').on('change', function() {
        if($(this).is(':checked')) {
            $('#customAvailability').addClass('d-none');
        } else {
            $('#customAvailability').removeClass('d-none');
        }
    });
    
    // Initialize datetime pickers for discount period
    const datePickers = $('input[name="discount_start"], input[name="discount_end"]');
    if(datePickers.length) {
        datePickers.flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today"
        });
    }

    // No Discount checkbox toggle
    $('#noDiscountCheck').on('change', function() {
        if($(this).is(':checked')) {
            $('#discountSection').slideUp();
            // Clear discount values
            $('select[name="discount_type"]').val('percentage');
            $('input[name="discount_value"]').val('');
            $('input[name="discount_start"]').val('');
            $('input[name="discount_end"]').val('');
            $('select[name="client_type"]').val('all');
            $('textarea[name="special_offer"]').val('');
        } else {
            $('#discountSection').slideDown();
        }
    });
    
    // Form Submission
    document.getElementById('listingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Get selected price option (negotiable or fixed)
        const priceOption = $('input[name="price_option"]:checked').val();
        formData.append('negotiable', priceOption === 'negotiable' ? '1' : '0');
        
        // Add no discount flag
        formData.append('no_discount', $('#noDiscountCheck').is(':checked') ? '1' : '0');
        
        // Get phone country codes
        const phoneCode = document.querySelector('#phoneCountrySelect .country-code').textContent;
        const whatsappCode = document.querySelector('#whatsappCountrySelect .country-code').textContent;
        formData.append('country_code', phoneCode);
        formData.append('whatsapp_country_code', whatsappCode);
        
        // Get selected days
        const selectedDays = [];
        document.querySelectorAll('input[name="available_days[]"]:checked').forEach(checkbox => {
            selectedDays.push(checkbox.value);
        });
        formData.append('selected_days', selectedDays.join(','));
        
        // Handle image uploads
        const previewItems = document.querySelectorAll('.preview-item');
        if(previewItems.length > 0) {
            // Tell the server we have images
            formData.append('has_images', '1');
            
            // Get main image index (default to first image)
            let mainImageIndex = 0;
            if(document.querySelector('.preview-item.is-main')) {
                const mainItem = document.querySelector('.preview-item.is-main');
                mainImageIndex = Array.from(previewItems).indexOf(mainItem);
            }
            formData.append('main_image', mainImageIndex);
            
            // Append image data
            previewItems.forEach((item, index) => {
                const img = item.querySelector('img');
                if(img && img.src.startsWith('data:image')) {
                    formData.append('image_data[]', img.src);
                    formData.append('image_names[]', `image_${index}.jpg`);
                }
            });
        }
        
        // Submit the form
        fetch('process-listing.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Listing added successfully!');
                window.location.href = 'my-listings.php';
            } else {
                alert(data.message || 'Failed to add listing');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
    
    // Add JS to handle making an image the main image
    $(document).on('click', '.preview-item img', function() {
        $('.preview-item').removeClass('is-main');
        $(this).parent().addClass('is-main');
        
        // Update UI to show which image is main
        $('.main-image-badge').remove();
        $(this).parent().append('<div class="main-image-badge">Main</div>');
    });
});

// Make selectLocation available globally
function selectLocation(location) {
    const selectedLocation = document.getElementById('selectedLocation');
    const locationText = document.getElementById('locationText');
    const locationSearch = document.getElementById('locationSearch');
    const locationDropdown = document.getElementById('locationDropdown');
    
    // Set values
    locationSearch.value = location;
    locationText.textContent = location;
    
    // Show selected location box
    selectedLocation.classList.remove('d-none');
    
    // Hide dropdown
    locationDropdown.classList.add('d-none');
    
    // Also store in a hidden field for form submission
    let hiddenLocationField = document.querySelector('input[name="location"]');
    if(!hiddenLocationField) {
        hiddenLocationField = document.createElement('input');
        hiddenLocationField.type = 'hidden';
        hiddenLocationField.name = 'location';
        document.getElementById('listingForm').appendChild(hiddenLocationField);
    }
    hiddenLocationField.value = location;
}
</script>
</body>
</html>

