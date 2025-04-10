<?php
// डेटाबेस कॉन्फिगरेशन
$dbHost = 'localhost';
$dbPort = '8889'; // MAMP का डिफॉल्ट MySQL पोर्ट
$dbName = 'ankit';
$dbUser = 'root';
$dbPass = 'root';

// एरर रिपोर्टिंग
error_reporting(E_ALL);
ini_set('display_errors', 1);

// डेटाबेस कनेक्शन फाइल इंक्लूड करें
require_once __DIR__ . '/db.php';

// टाइमज़ोन सेट करें
date_default_timezone_set('Asia/Kolkata');

// अन्य कॉन्फिगरेशन सेटिंग्स
$site_name = 'Escort Directory';
$site_url = 'http://localhost:8888/directory';
$admin_email = 'admin@example.com';

// सेशन स्टार्ट करें अगर पहले से स्टार्ट नहीं है
if (session_status() === PHP_SESSION_NONE) {
    // सेशन कॉन्फिगरेशन
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_secure', 0); // प्रोडक्शन में 1 सेट करें
    session_start();
}

// अपलोड पाथ
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// अन्य कॉन्फ़िगरेशन
define('SITE_URL', $site_url);

// साइट कॉन्फिगरेशन
define('SITE_NAME', $site_name);
define('ADMIN_EMAIL', $admin_email);
define('DB_PORT', 8889); // MAMP का डिफॉल्ट MySQL पोर्ट

// अपलोड URL
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('LISTING_IMG_PATH', UPLOAD_PATH . 'listings/');
define('VERIFICATION_IMG_PATH', UPLOAD_PATH . 'verifications/');
define('SCREENSHOT_IMG_PATH', UPLOAD_PATH . 'screenshots/');

define('LISTING_IMG_URL', UPLOAD_URL . 'listings/');
define('VERIFICATION_IMG_URL', UPLOAD_URL . 'verifications/');
define('SCREENSHOT_IMG_URL', UPLOAD_URL . 'screenshots/');

// पेजिनेशन सेटिंग्स
define('ITEMS_PER_PAGE', 10);

try {
    $db = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        )
    );
    
    // Set connection charset
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET character_set_connection=utf8mb4");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}