<?php
// डेटाबेस कनेक्शन
require_once '../includes/config.php';

// क्वेरी पैरामीटर प्राप्त करें
$query = isset($_GET['q']) ? $_GET['q'] : '';

// खाली रिस्पॉन्स
$response = [];

if (!empty($query)) {
    // लोकेशन्स सर्च करें
    $stmt = $db->prepare("SELECT id, name FROM locations WHERE name LIKE ? ORDER BY name LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// JSON रिस्पॉन्स भेजें
header('Content-Type: application/json');
echo json_encode($response);
?> 