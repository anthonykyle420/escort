<?php
// डेटाबेस कनेक्शन
require_once 'includes/config.php';

// एरर रिपोर्टिंग सेट करें
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // images टेबल के कॉलम्स की जानकारी प्राप्त करें
    $stmt = $db->query("SHOW COLUMNS FROM images");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Images Table Columns</h2>";
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
    
    // टेबल के इंडेक्स की जानकारी प्राप्त करें
    $stmt = $db->query("SHOW INDEX FROM images");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Images Table Indexes</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Table</th><th>Non_unique</th><th>Key_name</th><th>Column_name</th><th>Seq_in_index</th></tr>";
    
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . $index['Table'] . "</td>";
        echo "<td>" . $index['Non_unique'] . "</td>";
        echo "<td>" . $index['Key_name'] . "</td>";
        echo "<td>" . $index['Column_name'] . "</td>";
        echo "<td>" . $index['Seq_in_index'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // टेबल में डेटा की जानकारी प्राप्त करें
    $stmt = $db->query("SELECT listing_id, COUNT(*) as image_count FROM images GROUP BY listing_id ORDER BY image_count DESC");
    $imageCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Image Counts by Listing</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Listing ID</th><th>Image Count</th></tr>";
    
    foreach ($imageCounts as $count) {
        echo "<tr>";
        echo "<td>" . $count['listing_id'] . "</td>";
        echo "<td>" . $count['image_count'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // डुप्लिकेट इमेज पाथ की जानकारी प्राप्त करें
    $stmt = $db->query("
        SELECT listing_id, image_path, COUNT(*) as count 
        FROM images 
        GROUP BY listing_id, image_path 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<h2>Duplicate Image Paths</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Listing ID</th><th>Image Path</th><th>Count</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>" . $dup['listing_id'] . "</td>";
            echo "<td>" . $dup['image_path'] . "</td>";
            echo "<td>" . $dup['count'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<h2>No Duplicate Image Paths Found</h2>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 