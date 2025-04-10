<?php
// डेटाबेस कनेक्शन
require_once 'includes/config.php';

// एरर रिपोर्टिंग सेट करें
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // listings टेबल के कॉलम्स की जानकारी प्राप्त करें
    $stmt = $db->query("SHOW COLUMNS FROM listings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Listings Table Columns</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // body_type कॉलम के लिए विशेष जानकारी
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $stmt = $db->query("SHOW FULL COLUMNS FROM listings WHERE Field = 'body_type'");
        $bodyTypeColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bodyTypeColumn) {
            echo "<h2>Body Type Column Details</h2>";
            echo "<pre>";
            print_r($bodyTypeColumn);
            echo "</pre>";
            
            // अगर ENUM है तो उसके विकल्प दिखाएं
            if (strpos($bodyTypeColumn['Type'], 'enum') === 0) {
                preg_match("/^enum\((.*)\)$/", $bodyTypeColumn['Type'], $matches);
                if (isset($matches[1])) {
                    $enumValues = str_getcsv($matches[1], ',', "'");
                    echo "<h3>Valid Body Type Values:</h3>";
                    echo "<ul>";
                    foreach ($enumValues as $value) {
                        echo "<li>" . htmlspecialchars($value) . "</li>";
                    }
                    echo "</ul>";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 