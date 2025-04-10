<!DOCTYPE html>
<html>
<head>
    <title>Test Form</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('test-form');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const fileInput = document.getElementById('test-file');
                
                if (fileInput.files.length > 0) {
                    console.log('Files found:', fileInput.files.length);
                    
                    for (let i = 0; i < fileInput.files.length; i++) {
                        console.log('File:', fileInput.files[i].name);
                    }
                    
                    fetch('test-upload.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('result').innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                } else {
                    alert('Please select a file');
                }
            });
        });
    </script>
</head>
<body>
    <h1>Test Form</h1>
    
    <form id="test-form" method="POST" action="test-upload.php" enctype="multipart/form-data">
        <p>
            <input type="file" name="test_file[]" id="test-file" multiple>
        </p>
        <p>
            <button type="submit">Upload</button>
        </p>
    </form>
    
    <div id="result"></div>
</body>
</html> 