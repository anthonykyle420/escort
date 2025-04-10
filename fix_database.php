<?php
// Database connection
require_once 'includes/config.php';

// Start HTML output
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Fix Utility</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #121212; color: #f8f9fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background-color: #1e1e1e; border: 1px solid #333; margin-bottom: 20px; }
        .card-header { background-color: #007bff; color: white; }
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Database Fix Utility</h1>
        <div class='card'>
            <div class='card-header'>
                <h3>Fix Operations</h3>
            </div>
            <div class='card-body'>";

try {
    // Check if views column exists
    $checkStmt = $db->prepare("SHOW COLUMNS FROM listings LIKE 'views'");
    $checkStmt->execute();
    $viewsColumnExists = ($checkStmt->rowCount() > 0);
    
    if (!$viewsColumnExists) {
        // Add views column
        $db->exec("ALTER TABLE listings ADD COLUMN views INT DEFAULT 0 AFTER is_active");
        echo "<div class='alert alert-success'>
                <strong>Success:</strong> 'views' column has been added to the listings table.
              </div>";
    } else {
        echo "<div class='alert alert-info'>
                <strong>Info:</strong> 'views' column already exists in the listings table.
              </div>";
    }
    
    // Initialize views for all listings that have NULL values
    $db->exec("UPDATE listings SET views = 0 WHERE views IS NULL");
    echo "<div class='alert alert-success'>
            <strong>Success:</strong> All NULL view counts have been initialized to 0.
          </div>";
    
    // Check for other potential issues
    // For example, check if social_media table exists
    $checkStmt = $db->prepare("SHOW TABLES LIKE 'social_media'");
    $checkStmt->execute();
    $socialMediaTableExists = ($checkStmt->rowCount() > 0);
    
    if (!$socialMediaTableExists) {
        // Create social_media table if it doesn't exist
        $db->exec("CREATE TABLE social_media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            listing_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
        )");
        echo "<div class='alert alert-success'>
                <strong>Success:</strong> 'social_media' table has been created.
              </div>";
    } else {
        echo "<div class='alert alert-info'>
                <strong>Info:</strong> 'social_media' table already exists.
              </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>
            <strong>Error:</strong> " . $e->getMessage() . "
          </div>";
}

echo "      </div>
        </div>
        
        <div class='mt-4'>
            <a href='index.php' class='btn btn-primary'>Return to Homepage</a>
            <a href='check_database.php' class='btn btn-info ms-2'>Check Database Structure</a>
            <a href='listing.php?id=1' class='btn btn-success ms-2'>View a Listing</a>
        </div>
    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 