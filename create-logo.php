<?php
require_once 'includes/config.php';

// अपलोड डायरेक्टरी बनाएं
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/directory/assets/images')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/directory/assets/images', 0755, true);
}

// सिंपल लोगो बनाएं
$image = imagecreatetruecolor(200, 60);
$bg = imagecolorallocate($image, 41, 128, 185); // ब्लू बैकग्राउंड
$text = imagecolorallocate($image, 255, 255, 255); // व्हाइट टेक्स्ट

// बैकग्राउंड फिल करें
imagefill($image, 0, 0, $bg);

// टेक्स्ट जोड़ें
imagestring($image, 5, 40, 20, "Directory Website", $text);

// इमेज सेव करें
imagepng($image, $_SERVER['DOCUMENT_ROOT'] . '/directory/assets/images/logo.png');
imagedestroy($image);

echo "Logo created successfully!"; 