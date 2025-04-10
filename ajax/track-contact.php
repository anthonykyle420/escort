<?php
require_once '../includes/db.php';

// लिस्टिंग आईडी और कॉन्टैक्ट टाइप प्राप्त करें
$listingId = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if ($listingId && $type) {
    // कॉन्टैक्ट क्लिक रिकॉर्ड करें
    $db->insert('contact_clicks', [
        'listing_id' => $listingId,
        'contact_type' => $type,
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ]);
}

// कोई रेस्पॉन्स नहीं भेजें 