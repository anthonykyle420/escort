<?php
require_once 'includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contact_type = isset($_GET['type']) ? $_GET['type'] : 'phone';

if ($id) {
    try {
        // कॉन्टैक्ट क्लिक्स अपडेट करें
        $db->prepare("
            UPDATE listings 
            SET contact_clicks = contact_clicks + 1 
            WHERE id = :id
        ")->execute(['id' => $id]);
        
        // कॉन्टैक्ट क्लिक्स लॉग करें
        $db->prepare("
            INSERT INTO contact_clicks (listing_id, contact_type, clicked_at, ip_address, user_agent)
            VALUES (:listing_id, :contact_type, NOW(), :ip_address, :user_agent)
        ")->execute([
            'listing_id' => $id,
            'contact_type' => $contact_type,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?> 