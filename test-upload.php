<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information:</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

if (isset($_FILES['test_file']) && !empty($_FILES['test_file']['name'][0])) {
    echo "Files received!<br>";
    
    $uploadDir = 'uploads/test/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    for ($i = 0; $i < count($_FILES['test_file']['name']); $i++) {
        $fileName = $_FILES['test_file']['name'][$i];
        $fileTmp = $_FILES['test_file']['tmp_name'][$i];
        $fileError = $_FILES['test_file']['error'][$i];
        
        if ($fileError === 0) {
            $destination = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                echo "File uploaded: " . $fileName . "<br>";
            } else {
                echo "Failed to move file: " . $fileName . "<br>";
            }
        } else {
            echo "Error uploading file: " . $fileName . " (Error code: " . $fileError . ")<br>";
        }
    }
} else {
    echo "No files received!";
}
?> 