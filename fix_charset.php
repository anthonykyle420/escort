<?php
// डेटाबेस कॉन्फिगरेशन
$dbHost = 'localhost';
$dbPort = '8889'; // MAMP का डिफॉल्ट MySQL पोर्ट
$dbName = 'ankit';
$dbUser = 'root';
$dbPass = 'root';

try {
    // डेटाबेस से कनेक्ट करें
    $db = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    
    // डेटाबेस कैरेक्टर सेट अपडेट करें
    $db->exec("ALTER DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // सभी टेबल्स का कैरेक्टर सेट अपडेट करें
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Database Character Set Update</h2>";
    echo "<p>Updating database: <strong>$dbName</strong> to utf8mb4...</p>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        // टेबल का कैरेक्टर सेट बदलें
        $db->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<li>Table <strong>$table</strong> converted to utf8mb4</li>";
        
        // टेबल के कॉलम्स का कैरेक्टर सेट बदलें
        $columns = $db->query("SHOW FULL COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if (strpos($column['Type'], 'varchar') !== false || 
                strpos($column['Type'], 'text') !== false ||
                strpos($column['Type'], 'char') !== false) {
                    
                $db->exec("ALTER TABLE `$table` MODIFY `{$column['Field']}` {$column['Type']} 
                          CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }
    
    echo "</ul>";
    echo "<p style='color:green;font-weight:bold;'>Database character set successfully updated to utf8mb4!</p>";
    echo "<p>You can now use emojis and special characters in your listings.</p>";
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color:red;font-weight:bold;'>Error updating character set: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings:</p>";
    echo "<ul>";
    echo "<li>Host: $dbHost</li>";
    echo "<li>Port: $dbPort</li>";
    echo "<li>Database: $dbName</li>";
    echo "<li>Username: $dbUser</li>";
    echo "</ul>";
}
?> 