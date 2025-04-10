<?php
// एरर रिपोर्टिंग चालू करें
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS हेडर्स
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// रिस्पॉन्स हेडर
header('Content-Type: application/json');

// डीबग लॉग फंक्शन
function debug_log($message) {
    $logFile = 'upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

debug_log("Upload handler started");
debug_log("POST data: " . print_r($_POST, true));
debug_log("FILES data: " . print_r($_FILES, true));

// फंक्शन: फाइल एक्सटेंशन चेक करें
function isAllowedExtension($filename) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed);
}

// फंक्शन: फाइल साइज चेक करें (10MB मैक्स)
function isAllowedSize($filesize) {
    $maxSize = 10 * 1024 * 1024; // 10MB
    return $filesize <= $maxSize;
}

// POST रिक्वेस्ट चेक करें
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

try {
    // फाइल्स चेक करें
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        throw new Exception('No files uploaded');
    }
    
    // अपलोड डायरेक्टरी
    $uploadDir = 'uploads/listings/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // फाइल्स प्रोसेस करें
    $fileCount = count($_FILES['images']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $_FILES['images']['name'][$i];
        $fileTmp = $_FILES['images']['tmp_name'][$i];
        $fileSize = $_FILES['images']['size'][$i];
        $fileError = $_FILES['images']['error'][$i];
        
        debug_log("Processing file: $fileName");
        
        // अपलोड एरर चेक करें
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for file: $fileName";
            debug_log("Upload error: $fileError");
            continue;
        }
        
        // एक्सटेंशन चेक करें
        if (!isAllowedExtension($fileName)) {
            $errors[] = "Invalid file type: $fileName";
            debug_log("Invalid file type: $fileName");
            continue;
        }
        
        // साइज चेक करें
        if (!isAllowedSize($fileSize)) {
            $errors[] = "File too large: $fileName";
            debug_log("File too large: $fileName");
            continue;
        }
        
        // यूनिक फाइलनेम जनरेट करें
        $newFileName = uniqid() . '_' . $fileName;
        $destination = $uploadDir . $newFileName;
        
        debug_log("Moving file to: $destination");
        
        // फाइल मूव करें
        if (move_uploaded_file($fileTmp, $destination)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'saved_name' => $newFileName,
                'path' => $destination
            ];
            debug_log("File moved successfully");
        } else {
            $errors[] = "Failed to move file: $fileName";
            debug_log("Failed to move file");
        }
    }
    
    // मेन इमेज इंडेक्स
    $mainImageIndex = isset($_POST['main_image']) ? (int)$_POST['main_image'] : 0;
    debug_log("Main image index: $mainImageIndex");
    
    // अन्य फॉर्म डेटा
    $formData = $_POST;
    
    // डेटाबेस में लिस्टिंग सेव करें (यहां आपका कोड होगा)
    // ...
    
    // रिस्पॉन्स
    $response = [
        'success' => count($uploadedFiles) > 0,
        'files' => $uploadedFiles,
        'errors' => $errors,
        'form_data' => $formData,
        'message' => count($uploadedFiles) > 0 ? 'Files uploaded successfully' : 'Failed to upload files'
    ];
    
    debug_log("Response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    debug_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 