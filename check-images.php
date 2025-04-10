<?php
// डेटाबेस कनेक्शन
require_once 'includes/config.php';

// एरर रिपोर्टिंग सेट करें
ini_set('display_errors', 1);
error_reporting(E_ALL);

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 10; // डिफॉल्ट आईडी 10

try {
    // इमेज डेटा प्राप्त करें
    $stmt = $db->prepare("SELECT * FROM images WHERE listing_id = ?");
    $stmt->execute([$listingId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Images for Listing ID: $listingId</h2>";
    echo "<p>Total images found: " . count($images) . "</p>";
    
    if (count($images) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Image Path</th><th>Is Main</th><th>Created At</th><th>Preview</th></tr>";
        
        foreach ($images as $image) {
            echo "<tr>";
            echo "<td>" . $image['id'] . "</td>";
            echo "<td>" . $image['image_path'] . "</td>";
            echo "<td>" . ($image['is_main'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($image['created_at'] ?? 'N/A') . "</td>";
            echo "<td><img src='" . $image['image_path'] . "' height='100'></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No images found for this listing.</p>";
    }
    
    // डेटाबेस में डुप्लिकेट इमेज पाथ चेक करें
    $stmt = $db->prepare("
        SELECT image_path, COUNT(*) as count 
        FROM images 
        WHERE listing_id = ? 
        GROUP BY image_path 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute([$listingId]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<h2>Duplicate Image Paths Found</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Image Path</th><th>Count</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>" . $dup['image_path'] . "</td>";
            echo "<td>" . $dup['count'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // listing.php फाइल में इमेज प्राप्त करने का कोड देखें
    echo "<h2>Image Retrieval Code in listing.php</h2>";
    echo "<pre>";
    $code = file_get_contents('listing.php');
    if ($code) {
        // इमेज प्राप्त करने वाले कोड को खोजें और दिखाएं
        if (preg_match('/\/\/ लिस्टिंग की इमेजेस प्राप्त करें(.*?)\/\/ सोशल मीडिया डेटा प्राप्त करें/s', $code, $matches)) {
            echo htmlspecialchars($matches[0]);
        } else {
            echo "Image retrieval code not found in listing.php";
        }
    } else {
        echo "Could not read listing.php file";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 