<?php
// डेटाबेस कनेक्शन
require_once 'includes/config.php';

// एरर रिपोर्टिंग सेट करें
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // body_type कॉलम की वर्तमान स्थिति देखें
    $stmt = $db->query("SHOW COLUMNS FROM listings WHERE Field = 'body_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Current body_type Column</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // अगर कॉलम ENUM है तो उसे VARCHAR में बदलें
    if (strpos($column['Type'], 'enum') === 0) {
        echo "<p>Converting body_type column from ENUM to VARCHAR(20)...</p>";
        
        $db->exec("ALTER TABLE listings MODIFY COLUMN body_type VARCHAR(20)");
        
        echo "<p>Column modified successfully!</p>";
    } else {
        echo "<p>body_type column is already of type: " . $column['Type'] . "</p>";
        
        // अगर कॉलम VARCHAR है लेकिन साइज़ छोटा है तो उसे बढ़ाएं
        if (strpos($column['Type'], 'varchar') === 0) {
            preg_match('/varchar\((\d+)\)/', $column['Type'], $matches);
            if (isset($matches[1]) && (int)$matches[1] < 20) {
                echo "<p>Increasing body_type column size to VARCHAR(20)...</p>";
                
                $db->exec("ALTER TABLE listings MODIFY COLUMN body_type VARCHAR(20)");
                
                echo "<p>Column size increased successfully!</p>";
            }
        }
    }
    
    // अपडेट के बाद कॉलम की स्थिति देखें
    $stmt = $db->query("SHOW COLUMNS FROM listings WHERE Field = 'body_type'");
    $updatedColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Updated body_type Column</h2>";
    echo "<pre>";
    print_r($updatedColumn);
    echo "</pre>";
    
    // मौजूदा डेटा को वैलिडेट करें
    $stmt = $db->query("SELECT id, body_type FROM listings WHERE body_type IS NOT NULL");
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current body_type Values</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>body_type</th></tr>";
    
    $validBodyTypes = ['slim', 'athletic', 'average', 'curvy', 'bbw'];
    $updatedCount = 0;
    
    foreach ($listings as $listing) {
        echo "<tr>";
        echo "<td>" . $listing['id'] . "</td>";
        echo "<td>" . $listing['body_type'] . "</td>";
        echo "</tr>";
        
        // अमान्य मान को NULL में बदलें
        if (!in_array($listing['body_type'], $validBodyTypes)) {
            $stmt = $db->prepare("UPDATE listings SET body_type = NULL WHERE id = ?");
            $stmt->execute([$listing['id']]);
            $updatedCount++;
        }
    }
    
    echo "</table>";
    
    if ($updatedCount > 0) {
        echo "<p>Updated $updatedCount listings with invalid body_type values to NULL.</p>";
    } else {
        echo "<p>All body_type values are valid.</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 