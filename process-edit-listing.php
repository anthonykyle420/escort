<?php
// सेशन स्टार्ट
session_start();

// कॉन्फिग और डेटाबेस कनेक्शन
require_once 'includes/config.php';

// चेक करें कि यूजर लॉगिन है या नहीं
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// यूजर आईडी प्राप्त करें
$userId = $_SESSION['user_id'];

// चेक करें कि फॉर्म सबमिट हुआ है
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-listings.php');
    exit;
}

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

// चेक करें कि लिस्टिंग इस यूजर की है
$stmt = $db->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$listingId, $userId]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    header('Location: my-listings.php?error=invalid_listing');
    exit;
}

// फॉर्म डेटा प्राप्त करें
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$categoryId = (int)$_POST['category_id'];
$locationId = (int)$_POST['location_id'];
$contactNumber = trim($_POST['contact_number']);
$countryCode = trim($_POST['country_code']);
$price = (float)$_POST['price'];
$currency = trim($_POST['currency']);
$priceType = trim($_POST['price_type']);
$age = (int)$_POST['age'];
$height = isset($_POST['height']) ? (int)$_POST['height'] : null;
$weight = isset($_POST['weight']) ? (int)$_POST['weight'] : null;
$bodyType = isset($_POST['body_type']) ? trim($_POST['body_type']) : null;

// body_type वैलिडेशन - सिर्फ मान्य विकल्प ही स्वीकार करें
$validBodyTypes = ['slim', 'athletic', 'average', 'curvy', 'bbw'];
if (!empty($bodyType) && !in_array($bodyType, $validBodyTypes)) {
    $bodyType = null; // अमान्य मान होने पर null सेट करें
}

// सोशल मीडिया डेटा प्राप्त करें
$socialMediaTypes = isset($_POST['social_media_type']) ? $_POST['social_media_type'] : [];
$socialMediaInputTypes = isset($_POST['social_media_input_type']) ? $_POST['social_media_input_type'] : [];
$socialMediaValues = isset($_POST['social_media_value']) ? $_POST['social_media_value'] : [];

// वैलिडेशन
if (empty($title) || empty($description) || $categoryId <= 0 || $locationId <= 0 || empty($contactNumber)) {
    header('Location: edit-listing.php?id=' . $listingId . '&error=missing_fields');
    exit;
}

try {
    // डेटाबेस ट्रांजैक्शन शुरू करें
    $db->beginTransaction();
    
    // लिस्टिंग अपडेट करें
    $stmt = $db->prepare("
        UPDATE listings SET 
            title = ?,
            description = ?,
            category_id = ?,
            location_id = ?,
            contact_number = ?,
            country_code = ?,
            price = ?,
            currency = ?,
            price_type = ?,
            age = ?,
            height = ?,
            weight = ?,
            body_type = ?,
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([
        $title,
        $description,
        $categoryId,
        $locationId,
        $contactNumber,
        $countryCode,
        $price,
        $currency,
        $priceType,
        $age,
        $height,
        $weight,
        $bodyType,
        $listingId,
        $userId
    ]);
    
    // मेन इमेज अपडेट करें अगर चुनी गई है
    if (isset($_POST['main_image'])) {
        $mainImageId = (int)$_POST['main_image'];
        
        // पहले सभी इमेजेस को नॉन-मेन बनाएं
        $stmt = $db->prepare("UPDATE images SET is_main = 0 WHERE listing_id = ?");
        $stmt->execute([$listingId]);
        
        // चुनी गई इमेज को मेन बनाएं
        $stmt = $db->prepare("UPDATE images SET is_main = 1 WHERE id = ? AND listing_id = ?");
        $stmt->execute([$mainImageId, $listingId]);
    }
    
    // इमेजेस डिलीट करें अगर चुनी गई हैं
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $imageId) {
            $imageId = (int)$imageId;
            
            // इमेज का पाथ प्राप्त करें
            $stmt = $db->prepare("SELECT image_path FROM images WHERE id = ? AND listing_id = ?");
            $stmt->execute([$imageId, $listingId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                // फाइल डिलीट करें
                if (file_exists($image['image_path'])) {
                    unlink($image['image_path']);
                }
                
                // डेटाबेस से इमेज डिलीट करें
                $stmt = $db->prepare("DELETE FROM images WHERE id = ?");
                $stmt->execute([$imageId]);
            }
        }
    }
    
    // नई इमेजेस अपलोड करें
    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
        $uploadDir = 'uploads/listings/';
        
        // अपलोड डायरेक्टरी बनाएं अगर नहीं है
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // चेक करें कि कोई मेन इमेज है या नहीं
        $stmt = $db->prepare("SELECT COUNT(*) FROM images WHERE listing_id = ? AND is_main = 1");
        $stmt->execute([$listingId]);
        $hasMainImage = $stmt->fetchColumn() > 0;
        
        for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
            if ($_FILES['new_images']['error'][$i] === 0) {
                $originalFileName = $_FILES['new_images']['name'][$i];
                $fileExt = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
                
                // सिर्फ इमेज फाइल्स ही अपलोड करें
                if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                    continue;
                }
                
                // यूनिक फाइलनेम बनाएं
                $fileName = 'listing_' . $listingId . '_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;
                
                // डीबग लॉग
                error_log("Uploading image: Original name: $originalFileName, New path: $filePath");
                
                // इमेज अपलोड करें
                if (move_uploaded_file($_FILES['new_images']['tmp_name'][$i], $filePath)) {
                    // इमेज डेटाबेस में सेव करें
                    $isMain = (!$hasMainImage && $i === 0) ? 1 : 0;
                    
                    $stmt = $db->prepare("
                        INSERT INTO images (listing_id, image_path, is_main, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([$listingId, $filePath, $isMain]);
                    $newImageId = $db->lastInsertId();
                    
                    // डीबग लॉग
                    error_log("Image uploaded successfully. ID: $newImageId, Path: $filePath, Is Main: $isMain");
                    
                    // अगर यह पहली इमेज है और कोई मेन इमेज नहीं है, तो इसे मेन बना दें
                    if ($isMain) {
                        $hasMainImage = true;
                    }
                } else {
                    // अपलोड एरर लॉग करें
                    error_log("Failed to upload image: " . $_FILES['new_images']['error'][$i]);
                }
            }
        }
    }
    
    // सोशल मीडिया अपडेट करें
    // पहले सभी मौजूदा सोशल मीडिया डिलीट करें
    $stmt = $db->prepare("DELETE FROM social_media WHERE listing_id = ?");
    $stmt->execute([$listingId]);
    
    // नए सोशल मीडिया डेटा इन्सर्ट करें
    if (!empty($socialMediaTypes)) {
        for ($i = 0; $i < count($socialMediaTypes); $i++) {
            if (!empty($socialMediaTypes[$i]) && !empty($socialMediaValues[$i])) {
                $type = $socialMediaTypes[$i];
                $value = $socialMediaValues[$i];
                
                // WhatsApp नंबर के लिए फॉर्मेटिंग
                if ($type === 'whatsapp' && isset($socialMediaInputTypes[$i]) && $socialMediaInputTypes[$i] === 'number') {
                    // नंबर से सभी नॉन-न्यूमेरिक कैरेक्टर्स हटाएं
                    $value = preg_replace('/[^0-9]/', '', $value);
                }
                
                $stmt = $db->prepare("INSERT INTO social_media (listing_id, type, value) VALUES (?, ?, ?)");
                $stmt->execute([$listingId, $type, $value]);
            }
        }
    }
    
    // ट्रांजैक्शन कमिट करें
    $db->commit();
    
    // सक्सेस मैसेज के साथ रीडायरेक्ट करें
    header('Location: my-listings.php?success=listing_updated');
    exit;
    
} catch (Exception $e) {
    // एरर होने पर ट्रांजैक्शन रोलबैक करें
    $db->rollBack();
    
    // एरर मैसेज के साथ रीडायरेक्ट करें
    header('Location: edit-listing.php?id=' . $listingId . '&error=update_failed');
    exit;
} 