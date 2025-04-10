<?php
// एरर रिपोर्टिंग ऑन करें
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

try {
    // कुछ प्रोफाइल्स को वेरिफाइड मार्क करें
    $db->update('listings', ['is_verified' => 1], ['id' => 1]);
    $db->update('listings', ['is_verified' => 1], ['id' => 3]);
    $db->update('listings', ['is_verified' => 1], ['id' => 5]);
    
    echo "Successfully marked some profiles as verified!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 