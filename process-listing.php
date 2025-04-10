<?php
// सभी PHP एरर्स को कैप्चर करें
ini_set('display_errors', 0);
error_reporting(E_ALL);

// एरर हैंडलर सेट करें
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

// फेटल एरर हैंडलर सेट करें
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal error occurred']);
        exit;
    }
});

// आउटपुट बफरिंग स्टार्ट करें
ob_start();

try {
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    
    // सेशन स्टार्ट करें अगर पहले से स्टार्ट नहीं है
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // चेक करें कि यूजर लॉगिन है या नहीं
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required');
    }
    
    // फॉर्म डेटा प्राप्त करें
    $title = isset($_POST['title']) ? trim(strip_tags($_POST['title'])) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $locationId = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $countryCode = isset($_POST['country_code']) ? trim($_POST['country_code']) : '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? null;
    $nationality = $_POST['nationality'] ?? '';
    $height = $_POST['height'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $bodyType = $_POST['body_type'] ?? null;
    
    // नए स्पेशल ऑफर फील्ड्स
    $discountType = $_POST['discount_type'] ?? '';
    $discountValue = $_POST['discount_value'] ?? null;
    $discountStart = $_POST['discount_start'] ?? null;
    $discountEnd = $_POST['discount_end'] ?? null;
    $clientType = $_POST['client_type'] ?? 'all';
    $specialOffer = $_POST['special_offer'] ?? '';
    $noDiscount = isset($_POST['no_discount']) && $_POST['no_discount'] == '1';
    
    // नेगोशिएबल प्राइस सेटिंग
    $negotiable = isset($_POST['negotiable']) && $_POST['negotiable'] == '1';
    
    // नए उपलब्धता फील्ड्स
    $availableAllTime = isset($_POST['available_all_time']) ? 1 : 0;
    $selectedDays = isset($_POST['selected_days']) ? $_POST['selected_days'] : '';
    $availableFrom = $_POST['available_from'] ?? null;
    $availableTill = $_POST['available_till'] ?? null;
    
    // डीबग लॉग - फॉर्म डेटा
    error_log("Form data received:");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Main image index: " . (isset($_POST['main_image']) ? $_POST['main_image'] : 'Not set'));
    
    // मेन इमेज इंडेक्स प्राप्त करें
    $mainImageIndex = isset($_POST['main_image']) ? (int)$_POST['main_image'] : 0;
    error_log("Main image index (processed): " . $mainImageIndex);
    
    // body_type वैलिडेशन - सिर्फ मान्य विकल्प ही स्वीकार करें
    $validBodyTypes = ['slim', 'athletic', 'average', 'curvy', 'bbw'];
    if (!empty($bodyType) && !in_array($bodyType, $validBodyTypes)) {
        $bodyType = null; // अमान्य मान होने पर null सेट करें
    }
    
    $hourlyRate = $_POST['hourly_rate'] ?? null;
    $nightRate = $_POST['night_rate'] ?? null;
    $incallCharge = $_POST['incall_charge'] ?? null;
    $currency = $_POST['currency'] ?? 'INR';
    $priceType = $_POST['price_type'] ?? 'per_hour';
    $selectedTags = isset($_POST['selected_tags']) ? $_POST['selected_tags'] : '';
    $whatsappNumber = $_POST['whatsapp_number'] ?? null;
    $whatsappCountryCode = $_POST['whatsapp_country_code'] ?? '+91';
    
    // सोशल मीडिया डेटा प्राप्त करें
    $socialMediaTypes = isset($_POST['social_media_type']) ? $_POST['social_media_type'] : [];
    $socialMediaInputTypes = isset($_POST['social_media_input_type']) ? $_POST['social_media_input_type'] : [];
    $socialMediaValues = isset($_POST['social_media_value']) ? $_POST['social_media_value'] : [];
    
    // वैलिडेशन
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if ($categoryId <= 0) {
        $errors[] = 'Please select a category';
    }
    
    if ($locationId <= 0) {
        $errors[] = 'Please select a location';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if ($age < 18) {
        $errors[] = 'Age must be at least 18';
    }
    
    // इमेज वैलिडेशन
    $hasImages = false;
    
    // चेक करें कि has_images फील्ड सेट है
    if (isset($_POST['has_images']) && $_POST['has_images'] == '1') {
        $hasImages = true;
        error_log("has_images field is set to 1");
    }
    // चेक करें कि base64 इमेज डेटा है
    else if (isset($_POST['image_data']) && is_array($_POST['image_data']) && !empty($_POST['image_data'])) {
        $hasImages = true;
        error_log("Found " . count($_POST['image_data']) . " base64 images in POST data");
    }
    // अगर नहीं तो $_FILES से चेक करें
    else if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            if (!empty($name) && $_FILES['images']['error'][$key] === 0) {
                $hasImages = true;
                error_log("Found valid image in FILES data: " . $name);
                break;
            }
        }
    }
    
    if (!$hasImages) {
        $errors[] = 'Please select at least one image';
        error_log("No valid images found in request");
    }
    
    // अगर एरर्स हैं तो रिटर्न करें
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: add-listing.php');
        exit;
    }
    
    // फाइल्स प्रोसेस करें
    $uploadedFiles = [];
    
    // अपलोड डायरेक्टरी चेक करें
    $uploadDir = 'uploads/listings/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // चेक करें कि इमेज डेटा प्राप्त हुआ है
    if (isset($_POST['image_data']) && is_array($_POST['image_data']) && !empty($_POST['image_data'])) {
        $imageData = $_POST['image_data'];
        $imageNames = $_POST['image_names'] ?? [];
        
        // डीबग लॉग
        error_log("Processing " . count($imageData) . " base64 images");
        error_log("Main image index from form: " . $mainImageIndex);
        
        for ($i = 0; $i < count($imageData); $i++) {
            $base64Data = $imageData[$i];
            $fileName = isset($imageNames[$i]) ? $imageNames[$i] : 'image_' . $i . '.jpg';
            
            // डीबग लॉग
            error_log("Processing image {$i}: {$fileName}");
            
            // base64 डेटा से फाइल एक्सटेंशन निकालें
            $fileExt = 'jpg'; // डिफॉल्ट
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
                $fileExt = strtolower($matches[1]);
                // base64 प्रीफिक्स हटाएं
                $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            }
            
            // यूनिक फाइलनेम बनाएं
            $newFileName = 'listing_' . uniqid() . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            
            // base64 डेटा को डिकोड करें और फाइल में सेव करें
            $decodedData = base64_decode($base64Data);
            
            if ($decodedData === false) {
                error_log("Failed to decode base64 data for image {$i}");
                continue;
            }
            
            if (file_put_contents($destination, $decodedData) === false) {
                error_log("Failed to save image to: {$destination}");
                continue;
            }
            
            error_log("Successfully saved image to: {$destination}");
            $uploadedFiles[] = [
                'name' => $fileName,
                'path' => $destination,
                'is_main' => ($i == $mainImageIndex) ? 1 : 0
            ];
            
            // डीबग लॉग
            error_log("Added image to uploadedFiles array. Is main: " . (($i == $mainImageIndex) ? "Yes" : "No"));
        }
    }
    // फॉलबैक: अगर इमेज डेटा नहीं मिला तो $_FILES से प्रोसेस करें
    else if (isset($_FILES['images'])) {
        $fileCount = count($_FILES['images']['name']);
        
        // डीबग लॉग
        error_log("Processing {$fileCount} images from FILES");
        error_log("Main image index from form: " . $mainImageIndex);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $_FILES['images']['name'][$i];
            $fileTmp = $_FILES['images']['tmp_name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileError = $_FILES['images']['error'][$i];
            
            // डीबग लॉग
            error_log("Processing image {$i}: {$fileName}, Size: {$fileSize}, Error: {$fileError}");
            
            // फाइल एरर चेक करें
            if ($fileError !== UPLOAD_ERR_OK) {
                error_log("Skipping image due to error: {$fileError}");
                continue;
            }
            
            // फाइल साइज चेक करें (5MB मैक्स)
            if ($fileSize > 5 * 1024 * 1024) {
                error_log("Skipping image due to size: {$fileSize}");
                continue;
            }
            
            // फाइल एक्सटेंशन चेक करें
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                error_log("Skipping image due to invalid extension: {$fileExt}");
                continue;
            }
            
            // यूनिक फाइलनेम बनाएं
            $newFileName = 'listing_' . uniqid() . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            
            error_log("Attempting to upload to: {$destination}");
            
            if (move_uploaded_file($fileTmp, $destination)) {
                error_log("Successfully uploaded image to: {$destination}");
                $uploadedFiles[] = [
                    'name' => $fileName,
                    'path' => $destination,
                    'is_main' => ($i == $mainImageIndex) ? 1 : 0
                ];
                
                // डीबग लॉग
                error_log("Added image to uploadedFiles array. Is main: " . (($i == $mainImageIndex) ? "Yes" : "No"));
            } else {
                error_log("Failed to upload image: " . error_get_last()['message']);
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        throw new Exception('Failed to upload images');
    }
    
    // डेटाबेस कनेक्शन प्राप्त करें
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database connection not available');
    }
    
    // Set connection charset to UTF-8
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET character_set_connection=utf8mb4");
    
    // डीबग लॉग
    error_log("Body type value: " . ($bodyType ?? 'null'));
    
    // लिस्टिंग डेटा इन्सर्ट करें
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("INSERT INTO listings (
            user_id, title, description, category_id, location_id, location,
            gender, age, nationality, height, weight, body_type,
            price, currency, price_type, hourly_rate, night_rate, incall_charge,
            contact_number, country_code, whatsapp_number, whatsapp_country_code,
            negotiable, no_discount,
            discount_type, discount_value, discount_start, discount_end, client_type, special_offer,
            available_all_time, available_days, available_from, available_till,
            tags, created_at, updated_at, is_active
        ) VALUES (
            :user_id, :title, :description, :category_id, :location_id, :location,
            :gender, :age, :nationality, :height, :weight, :body_type,
            :price, :currency, :price_type, :hourly_rate, :night_rate, :incall_charge,
            :contact_number, :country_code, :whatsapp_number, :whatsapp_country_code,
            :negotiable, :no_discount,
            :discount_type, :discount_value, :discount_start, :discount_end, :client_type, :special_offer,
            :available_all_time, :available_days, :available_from, :available_till,
            :tags, NOW(), NOW(), :is_active
        )");
        
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'location_id' => $locationId,
            'location' => $location,
            'gender' => $gender,
            'age' => $age,
            'nationality' => $nationality,
            'height' => $height,
            'weight' => $weight,
            'body_type' => $bodyType,
            'price' => $hourlyRate,
            'currency' => $currency,
            'price_type' => $priceType,
            'hourly_rate' => $hourlyRate,
            'night_rate' => $nightRate,
            'incall_charge' => $incallCharge,
            'contact_number' => $phone,
            'country_code' => $countryCode,
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_country_code' => $whatsappCountryCode,
            'negotiable' => $negotiable ? 1 : 0,
            'no_discount' => $noDiscount ? 1 : 0,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_start' => $discountStart,
            'discount_end' => $discountEnd,
            'client_type' => $clientType,
            'special_offer' => $specialOffer,
            'available_all_time' => $availableAllTime,
            'available_days' => $selectedDays,
            'available_from' => $availableFrom,
            'available_till' => $availableTill,
            'tags' => $selectedTags,
            'is_active' => 1
        ]);
        
        $listingId = $db->lastInsertId();
        
        // इमेजेज इन्सर्ट करें
        foreach ($uploadedFiles as $file) {
            $stmt = $db->prepare("INSERT INTO images (listing_id, image_path, is_main) VALUES (:listing_id, :image_path, :is_main)");
            $isMain = ($file['is_main'] == 1) ? 1 : 0;
            
            // डीबग लॉग
            error_log("Inserting image: Path={$file['path']}, IsMain=$isMain");
            
            $stmt->execute([
                'listing_id' => $listingId,
                'image_path' => $file['path'],
                'is_main' => $isMain
            ]);
            
            // डीबग लॉग
            error_log("Image inserted with ID: " . $db->lastInsertId());
        }
        
        // सिलेक्टेड टैग्स इन्सर्ट करें
        if (!empty($selectedTags)) {
            $tagIds = explode(',', $selectedTags);
            foreach ($tagIds as $tagId) {
                if (!empty($tagId)) {
                    $stmt = $db->prepare("INSERT INTO listing_tags (listing_id, tag_id) VALUES (:listing_id, :tag_id)");
                    $stmt->execute([
                        'listing_id' => $listingId,
                        'tag_id' => (int)$tagId
                    ]);
                }
            }
        }
        
        // सोशल मीडिया इन्सर्ट करें
        for ($i = 0; $i < count($socialMediaTypes); $i++) {
            if (!empty($socialMediaValues[$i])) {
                $stmt = $db->prepare("INSERT INTO social_media (listing_id, type, value) VALUES (:listing_id, :type, :value)");
                $stmt->execute([
                    'listing_id' => $listingId,
                    'type' => $socialMediaTypes[$i],
                    'value' => $socialMediaValues[$i]
                ]);
            }
        }
        
        // व्हाट्सएप नंबर इन्सर्ट करें (अगर है तो)
        if (isset($whatsappNumber) && !empty($whatsappNumber)) {
            $stmt = $db->prepare("INSERT INTO social_media (listing_id, type, value) VALUES (:listing_id, :type, :value)");
            $stmt->execute([
                'listing_id' => $listingId,
                'type' => 'whatsapp',
                'value' => $whatsappCountryCode . $whatsappNumber
            ]);
        }
        
        $db->commit();
        
        // सफलता पर
        $_SESSION['success'] = 'Listing added successfully!';
        header('Location: my-listings.php');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        throw new Exception('Database error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // एरर रिस्पॉन्स
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// आउटपुट बफर क्लीयर करें
ob_end_clean();

// JSON रिस्पॉन्स भेजें
header('Content-Type: application/json');
echo json_encode($response);
exit; 