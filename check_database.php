<?php
// Database connection
require_once 'includes/config.php';

// Start HTML output
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Structure Check</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #121212; color: #f8f9fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background-color: #1e1e1e; border: 1px solid #333; margin-bottom: 20px; }
        .card-header { background-color: #007bff; color: white; }
        .table { color: #f8f9fa; }
        .table-dark { background-color: #2c2c2c; }
        .btn-fix { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Database Structure Check</h1>";

try {
    // Get all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='card'>
            <div class='card-header'>
                <h3>Database Tables</h3>
            </div>
            <div class='card-body'>
                <p>Found " . count($tables) . " tables in the database.</p>
                <ul>";
    
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    
    echo "</ul>
            </div>
        </div>";
    
    // Check listings table structure
    if (in_array('listings', $tables)) {
        $columns = $db->query("SHOW COLUMNS FROM listings")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='card'>
                <div class='card-header'>
                    <h3>Listings Table Structure</h3>
                </div>
                <div class='card-body'>
                    <p>Found " . count($columns) . " columns in the listings table.</p>
                    <table class='table table-dark table-striped'>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>";
        
        $hasViewsColumn = false;
        foreach ($columns as $column) {
            echo "<tr>
                    <td>" . $column['Field'] . "</td>
                    <td>" . $column['Type'] . "</td>
                    <td>" . $column['Null'] . "</td>
                    <td>" . $column['Key'] . "</td>
                    <td>" . ($column['Default'] === null ? 'NULL' : $column['Default']) . "</td>
                    <td>" . $column['Extra'] . "</td>
                  </tr>";
            
            if ($column['Field'] === 'views') {
                $hasViewsColumn = true;
            }
        }
        
        echo "</tbody>
                    </table>";
        
        if (!$hasViewsColumn) {
            echo "<div class='alert alert-warning'>
                    <strong>Warning:</strong> The 'views' column is missing from the listings table.
                    <a href='fix_database.php' class='btn btn-fix btn-sm ms-3'>Fix Now</a>
                  </div>";
        } else {
            echo "<div class='alert alert-success'>
                    <strong>Good!</strong> The 'views' column exists in the listings table.
                  </div>";
        }
        
        echo "</div>
            </div>";
    } else {
        echo "<div class='alert alert-danger'>
                <strong>Error:</strong> The 'listings' table does not exist in the database.
              </div>";
    }
    
    // Navigation links
    echo "<div class='mt-4'>
            <a href='index.php' class='btn btn-primary'>Return to Homepage</a>
            <a href='fix_database.php' class='btn btn-success ms-2'>Run Database Fix</a>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>
            <strong>Database Error:</strong> " . $e->getMessage() . "
          </div>";
}

echo "</div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 