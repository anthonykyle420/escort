<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// डेटाबेस कनेक्शन पैरामीटर्स
$host = '127.0.0.1';
$port = '8889';
$dbname = 'ankit';
$username = 'root';
$password = 'root';

try {
    // PDO इंस्टेंस बनाएं
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    
    // एरर मोड सेट करें
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully to database!";
    
    // डेटाबेस से कुछ डेटा निकालें
    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?> 