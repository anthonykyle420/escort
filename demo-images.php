<?php
// एरर रिपोर्टिंग ऑन करें
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    // अपलोड डायरेक्टरीज बनाएं
    $uploadPath = __DIR__ . '/uploads';
    $listingsPath = $uploadPath . '/listings';

    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            throw new Exception("Could not create uploads directory");
        }
        echo "Created uploads directory<br>";
    }

    if (!file_exists($listingsPath)) {
        if (!mkdir($listingsPath, 0755, true)) {
            throw new Exception("Could not create listings directory");
        }
        echo "Created listings directory<br>";
    }

    // सभी लिस्टिंग्स प्राप्त करें
    $listings = $db->select("SELECT * FROM listings");

    if (empty($listings)) {
        echo "No listings found in database.";
        exit;
    }

    // डेमो इमेज फाइल्स कॉपी करें
    $demoImages = [
        'demo1.jpg',
        'demo2.jpg',
        'demo3.jpg',
        'demo4.jpg'
    ];

    // डेमो इमेज फाइल्स बनाएं
    foreach ($demoImages as $index => $filename) {
        $image = imagecreatetruecolor(800, 600);
        
        // रैंडम बैकग्राउंड कलर
        $r = rand(100, 200);
        $g = rand(100, 200);
        $b = rand(100, 200);
        $bg_color = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $bg_color);
        
        // टेक्स्ट कलर
        $text_color = imagecolorallocate($image, 255, 255, 255);
        
        // टेक्स्ट लिखें
        imagestring($image, 5, 300, 280, "Demo Image " . ($index + 1), $text_color);
        
        // इमेज सेव करें
        $demoPath = $listingsPath . '/' . $filename;
        imagejpeg($image, $demoPath, 90);
        imagedestroy($image);
        
        echo "Created demo image: $filename<br>";
    }

    // हर लिस्टिंग के लिए डेमो इमेज असाइन करें
    foreach ($listings as $index => $listing) {
        // रैंडम डेमो इमेज चुनें
        $demoImage = $demoImages[$index % count($demoImages)];
        $imagePath = 'uploads/listings/' . $demoImage;
        
        // चेक करें कि इमेज एंट्री पहले से मौजूद है या नहीं
        $existingImage = $db->selectOne("SELECT * FROM images WHERE listing_id = :listing_id AND is_main = 1", 
                                      ['listing_id' => $listing['id']]);

        if ($existingImage) {
            // अपडेट एग्जिस्टिंग इमेज
            $db->update('images', 
                       ['image_path' => $imagePath], 
                       ['id' => $existingImage['id']]);
            echo "Updated image for listing ID: " . $listing['id'] . "<br>";
        } else {
            // इन्सर्ट न्यू इमेज
            $db->insert('images', [
                'listing_id' => $listing['id'],
                'image_path' => $imagePath,
                'is_main' => 1
            ]);
            echo "Created new image for listing ID: " . $listing['id'] . "<br>";
        }
    }

    echo "Demo images process completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 