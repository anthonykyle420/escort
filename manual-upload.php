<!DOCTYPE html>
<html>
<head>
    <title>Manual Upload</title>
</head>
<body>
    <h1>Manual File Upload</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<h2>Debug Information:</h2>";
        echo "<pre>";
        print_r($_FILES);
        echo "</pre>";
        
        if (isset($_FILES['manual_image']) && $_FILES['manual_image']['error'] === 0) {
            echo "File uploaded successfully!";
            $uploadDir = 'uploads/manual/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destination = $uploadDir . $_FILES['manual_image']['name'];
            if (move_uploaded_file($_FILES['manual_image']['tmp_name'], $destination)) {
                echo "<br>File moved to: " . $destination;
            } else {
                echo "<br>Failed to move file!";
            }
        } else {
            echo "File upload failed!";
        }
    }
    ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <p>
            <input type="file" name="manual_image" accept="image/*">
        </p>
        <p>
            <button type="submit">Upload</button>
        </p>
    </form>
</body>
</html> 