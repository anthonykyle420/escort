<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Uploader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .image-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }
        .main-btn {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            margin: 0 auto;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 2px 5px;
            font-size: 10px;
            cursor: pointer;
        }
        .main-btn.active {
            background-color: #28a745;
        }
        .btn {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .counter {
            margin-left: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        #file-input {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Image Uploader</h1>
    
    <div class="form-group">
        <label for="file-input">
            Select Images (Min 1, Max 10)
            <span class="counter" id="image-counter">0/10</span>
        </label>
        <input type="file" id="file-input" accept="image/jpeg,image/png,image/gif" style="display:none;">
        <button type="button" id="add-image" class="btn">Add Image</button>
    </div>
    
    <div class="image-preview-container" id="preview-container"></div>
    <input type="hidden" id="main-image" value="0">
    
    <div class="form-group" style="margin-top: 20px;">
        <button type="button" id="upload-button" class="btn">Upload Images</button>
    </div>
    
    <div id="result" class="result" style="display: none;"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // एलिमेंट्स प्राप्त करें
            const fileInput = document.getElementById('file-input');
            const addImageBtn = document.getElementById('add-image');
            const previewContainer = document.getElementById('preview-container');
            const mainImageInput = document.getElementById('main-image');
            const uploadButton = document.getElementById('upload-button');
            const resultDiv = document.getElementById('result');
            const imageCounter = document.getElementById('image-counter');
            
            // चुनी गई फाइल्स
            const selectedFiles = [];
            
            // काउंटर अपडेट फंक्शन
            function updateCounter() {
                imageCounter.textContent = selectedFiles.length + '/10';
            }
            
            // एड इमेज बटन क्लिक इवेंट
            addImageBtn.addEventListener('click', function() {
                if (selectedFiles.length >= 10) {
                    alert('You can only upload up to 10 images');
                    return;
                }
                fileInput.click();
            });
            
            // फाइल इनपुट चेंज इवेंट
            fileInput.addEventListener('change', function() {
                if (!this.files || !this.files[0]) return;
                
                const newFile = this.files[0];
                
                // फाइल टाइप चेक करें
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(newFile.type)) {
                    alert('Only JPG, PNG and GIF images are allowed');
                    return;
                }
                
                // फाइल साइज चेक करें (10MB मैक्स)
                if (newFile.size > 10 * 1024 * 1024) {
                    alert('File size should not exceed 10MB');
                    return;
                }
                
                // फाइल एरे में जोड़ें
                selectedFiles.push(newFile);
                
                // इंडेक्स
                const index = selectedFiles.length - 1;
                
                // फाइल प्रीव्यू
                const reader = new FileReader();
                reader.onload = function(e) {
                    // इमेज कंटेनर
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'image-preview-item';
                    imgContainer.dataset.index = index;
                    
                    // इमेज
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    
                    // रिमूव बटन
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-btn';
                    removeBtn.innerHTML = '×';
                    
                    removeBtn.addEventListener('click', function() {
                        // इमेज कंटेनर हटाएं
                        imgContainer.remove();
                        
                        // फाइल एरे से हटाएं
                        selectedFiles.splice(index, 1);
                        
                        // इंडेक्स अपडेट करें
                        document.querySelectorAll('.image-preview-item').forEach((item, i) => {
                            item.dataset.index = i;
                        });
                        
                        // मेन इमेज अपडेट करें
                        if (parseInt(mainImageInput.value) === index) {
                            mainImageInput.value = 0;
                            if (document.querySelector('.image-preview-item')) {
                                document.querySelector('.main-btn').click();
                            }
                        } else if (parseInt(mainImageInput.value) > index) {
                            mainImageInput.value = parseInt(mainImageInput.value) - 1;
                        }
                        
                        updateCounter();
                    });
                    
                    // मेन इमेज बटन
                    const mainBtn = document.createElement('button');
                    mainBtn.className = 'main-btn';
                    mainBtn.innerHTML = 'Main Image';
                    mainBtn.dataset.index = index;
                    
                    mainBtn.addEventListener('click', function() {
                        // सभी बटन्स रीसेट करें
                        document.querySelectorAll('.main-btn').forEach(btn => {
                            btn.classList.remove('active');
                            btn.style.backgroundColor = '#007bff';
                        });
                        
                        // इस बटन को हाइलाइट करें
                        mainBtn.classList.add('active');
                        mainBtn.style.backgroundColor = '#28a745';
                        
                        // मेन इमेज इंडेक्स अपडेट करें
                        mainImageInput.value = index;
                    });
                    
                    // एलिमेंट्स जोड़ें
                    imgContainer.appendChild(img);
                    imgContainer.appendChild(removeBtn);
                    imgContainer.appendChild(mainBtn);
                    
                    // प्रीव्यू कंटेनर में जोड़ें
                    previewContainer.appendChild(imgContainer);
                    
                    // पहली इमेज को मेन इमेज के रूप में सेट करें
                    if (selectedFiles.length === 1) {
                        mainBtn.click();
                    }
                    
                    updateCounter();
                };
                
                reader.readAsDataURL(newFile);
                
                // फाइल इनपुट रीसेट करें
                fileInput.value = '';
            });
            
            // अपलोड बटन क्लिक इवेंट
            uploadButton.addEventListener('click', function() {
                if (selectedFiles.length === 0) {
                    alert('Please select at least one image');
                    return;
                }
                
                // फॉर्म डेटा
                const formData = new FormData();
                
                // फाइल्स जोड़ें
                selectedFiles.forEach((file, i) => {
                    formData.append('images[]', file);
                });
                
                // मेन इमेज इंडेक्स जोड़ें
                formData.append('main_image', mainImageInput.value);
                
                // लोडिंग मैसेज
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = 'Uploading files...';
                
                // AJAX रिक्वेस्ट
                fetch('upload-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    
                    let resultHTML = '<h3>' + (data.success ? 'Success' : 'Error') + '</h3>';
                    resultHTML += '<p>' + data.message + '</p>';
                    
                    if (data.files && data.files.length > 0) {
                        resultHTML += '<h4>Uploaded Files:</h4><ul>';
                        data.files.forEach(file => {
                            resultHTML += '<li>' + file.original_name + ' → ' + file.saved_name + '</li>';
                        });
                        resultHTML += '</ul>';
                    }
                    
                    if (data.errors && data.errors.length > 0) {
                        resultHTML += '<h4>Errors:</h4><ul>';
                        data.errors.forEach(error => {
                            resultHTML += '<li>' + error + '</li>';
                        });
                        resultHTML += '</ul>';
                    }
                    
                    resultDiv.innerHTML = resultHTML;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<h3>Error</h3><p>' + error.message + '</p>';
                });
            });
        });
    </script>
</body>
</html> 