<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $host = '127.0.0.1:8889';
    $dbname = 'ankit';
    $username = 'root';
    $password = 'root';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully to database!";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?> 