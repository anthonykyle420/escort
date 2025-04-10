<?php
// Database connection
require_once 'includes/config.php';

try {
    // Check if views column exists
    $checkStmt = $db->prepare("SHOW COLUMNS FROM listings LIKE 'views'");
    $checkStmt->execute();
    $viewsColumnExists = ($checkStmt->rowCount() > 0);
    
    if (!$viewsColumnExists) {
        // Add views column
        $db->exec("ALTER TABLE listings ADD COLUMN views INT DEFAULT 0 AFTER is_active");
        echo "Success: 'views' column has been added to the listings table.";
    } else {
        echo "Info: 'views' column already exists in the listings table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 