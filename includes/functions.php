<?php
require_once 'db.php';

// सैनिटाइज इनपुट
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// रैंडम स्ट्रिंग जनरेट करें
function generateRandomString($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// वेरिफिकेशन कोड जनरेट करें
function generateVerificationCode() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// इमेज कंप्रेस करें
function compressImage($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } else {
        return false;
    }
    
    // Save the compressed image
    return imagejpeg($image, $destination, $quality);
}

// स्लग जनरेट करें
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

// पेजिनेशन फंक्शन
function pagination($total, $page, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    
    $start = ($page - 1) * $perPage;
    
    return [
        'start' => $start,
        'perPage' => $perPage,
        'currentPage' => $page,
        'totalPages' => $totalPages
    ];
}

// यूजर लॉग्ड इन है या नहीं
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// यूजर एडमिन है या नहीं
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// यूजर की डिटेल्स प्राप्त करें
function getUserById($id) {
    global $db;
    return $db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $id]);
}

// लिस्टिंग की डिटेल्स प्राप्त करें
function getListingById($id) {
    global $db;
    return $db->selectOne("SELECT l.*, c.name as category_name, loc.name as location_name 
                          FROM listings l 
                          JOIN categories c ON l.category_id = c.id 
                          JOIN locations loc ON l.location_id = loc.id 
                          WHERE l.id = :id", ['id' => $id]);
}

// लिस्टिंग की इमेजेज प्राप्त करें
function getListingImages($listingId) {
    global $db;
    return $db->select("SELECT * FROM images WHERE listing_id = :listing_id ORDER BY is_main DESC", 
                      ['listing_id' => $listingId]);
}

// लिस्टिंग के सोशल मीडिया प्राप्त करें
function getListingSocialMedia($listingId) {
    global $db;
    return $db->select("SELECT * FROM social_media WHERE listing_id = :listing_id", 
                      ['listing_id' => $listingId]);
}

// लिस्टिंग के कॉन्टैक्ट नंबर्स प्राप्त करें
function getListingContactNumbers($listingId) {
    global $db;
    return $db->select("SELECT * FROM contact_numbers WHERE listing_id = :listing_id", 
                      ['listing_id' => $listingId]);
}

// लिस्टिंग के टैग्स प्राप्त करें
function getListingTags($listingId) {
    global $db;
    return $db->select("SELECT t.* FROM tags t 
                        JOIN listing_tags lt ON t.id = lt.tag_id 
                        WHERE lt.listing_id = :listing_id", 
                      ['listing_id' => $listingId]);
}

// सभी कैटेगरीज प्राप्त करें
function getAllCategories() {
    global $db;
    return $db->select("SELECT * FROM categories ORDER BY name");
}

// सभी लोकेशन्स प्राप्त करें
function getAllLocations() {
    global $db;
    return $db->select("SELECT * FROM locations WHERE is_active = 1 ORDER BY name");
}

// लोकेशन सर्च करें
function searchLocations($term) {
    global $db;
    return $db->select("SELECT * FROM locations WHERE name LIKE :term AND is_active = 1 ORDER BY name LIMIT 10", 
                      ['term' => "%$term%"]);
}

// व्यू काउंट अपडेट करें
function updateViewCount($listingId) {
    global $db;
    $db->query("UPDATE listings SET total_views = total_views + 1, today_views = today_views + 1 WHERE id = :id", 
              ['id' => $listingId]);
}

// कॉन्टैक्ट क्लिक काउंट अपडेट करें
function updateContactClickCount($listingId) {
    global $db;
    $db->query("UPDATE listings SET contact_clicks = contact_clicks + 1 WHERE id = :id", 
              ['id' => $listingId]);
}

// डेली व्यू काउंट रीसेट करें (क्रॉन जॉब के लिए)
function resetDailyViewCounts() {
    global $db;
    $db->query("UPDATE listings SET today_views = 0", []);
}

// फीचर्ड लिस्टिंग्स प्राप्त करें
function getFeaturedListings($locationId = null, $limit = 6) {
    global $db;
    
    $sql = "SELECT l.*, c.name as category_name, loc.name as location_name, 
            (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN locations loc ON l.location_id = loc.id 
            WHERE l.is_featured = 1 AND l.is_active = 1";
    
    $params = [];
    
    if ($locationId) {
        $sql .= " AND (l.location_id = :location_id OR EXISTS (SELECT 1 FROM featured_locations fl WHERE fl.listing_id = l.id AND fl.location_id = :fl_location_id))";
        $params['location_id'] = $locationId;
        $params['fl_location_id'] = $locationId;
    }
    
    $sql .= " ORDER BY l.is_verified DESC, l.created_at DESC LIMIT :limit";
    $params['limit'] = $limit;
    
    return $db->select($sql, $params);
}

// पॉपुलर लिस्टिंग्स प्राप्त करें
function getPopularListings($locationId = null, $limit = 6) {
    global $db;
    
    $sql = "SELECT l.*, c.name as category_name, loc.name as location_name, 
            (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN locations loc ON l.location_id = loc.id 
            WHERE l.is_popular = 1 AND l.is_active = 1";
    
    $params = [];
    
    if ($locationId) {
        $sql .= " AND l.location_id = :location_id";
        $params['location_id'] = $locationId;
    }
    
    $sql .= " ORDER BY l.total_views DESC, l.is_verified DESC LIMIT :limit";
    $params['limit'] = $limit;
    
    return $db->select($sql, $params);
}

// स्पेशल लिस्टिंग्स प्राप्त करें
function getSpecialListings($limit = 6) {
    global $db;
    
    $sql = "SELECT l.*, c.name as category_name, loc.name as location_name, 
            (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN locations loc ON l.location_id = loc.id 
            WHERE l.is_special = 1 AND l.is_active = 1
            ORDER BY l.is_verified DESC, l.created_at DESC LIMIT :limit";
    
    return $db->select($sql, ['limit' => $limit]);
}

// सर्च रिजल्ट्स प्राप्त करें
function searchListings($category = null, $location = null, $sexuality = null, $minPrice = null, $maxPrice = null, $minAge = null, $maxAge = null, $verifiedOnly = false, $page = 1) {
    global $db;
    
    $sql = "SELECT l.*, c.name as category_name, loc.name as location_name, 
            (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN locations loc ON l.location_id = loc.id 
            WHERE l.is_active = 1";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND c.slug = :category";
        $params['category'] = $category;
    }
    
    if ($location) {
        $sql .= " AND loc.id = :location";
        $params['location'] = $location;
    }
    
    if ($sexuality) {
        $sql .= " AND l.sexuality LIKE :sexuality";
        $params['sexuality'] = "%$sexuality%";
    }
    
    if ($minPrice) {
        $sql .= " AND l.price >= :min_price";
        $params['min_price'] = $minPrice;
    }
    
    if ($maxPrice) {
        $sql .= " AND l.price <= :max_price";
        $params['max_price'] = $maxPrice;
    }
    
    if ($minAge) {
        $sql .= " AND l.age >= :min_age";
        $params['min_age'] = $minAge;
    }
    
    if ($maxAge) {
        $sql .= " AND l.age <= :max_age";
        $params['max_age'] = $maxAge;
    }
    
    if ($verifiedOnly) {
        $sql .= " AND l.is_verified = 1";
    }
    
    // काउंट क्वेरी
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as count_table";
    $total = $db->selectOne($countSql, $params)['total'];
    
    // पेजिनेशन
    $paging = pagination($total, $page);
    
    $sql .= " ORDER BY l.is_verified DESC, l.is_featured DESC, l.is_popular DESC, l.created_at DESC 
              LIMIT {$paging['start']}, {$paging['perPage']}";
    
    $results = $db->select($sql, $params);
    
    return [
        'listings' => $results,
        'pagination' => $paging,
        'total' => $total
    ];
}

// यूजर की लिस्टिंग्स प्राप्त करें
function getUserListings($userId) {
    global $db;
    
    $sql = "SELECT l.*, c.name as category_name, loc.name as location_name, 
            (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            JOIN locations loc ON l.location_id = loc.id 
            WHERE l.user_id = :user_id
            ORDER BY l.created_at DESC";
    
    return $db->select($sql, ['user_id' => $userId]);
}

// रिडायरेक्ट फंक्शन
function redirect($url) {
    header("Location: $url");
    exit;
}

// फ्लैश मैसेज सेट करें
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// फ्लैश मैसेज प्राप्त करें और हटा दें
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// SEO फ्रेंडली URL जनरेट करें
function generateSeoUrl($type, $id, $slug) {
    return SITE_URL . "/$type/$id-" . urlencode($slug);
} 