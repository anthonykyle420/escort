// फाइल अपलोड हैंडलर
document.addEventListener('DOMContentLoaded', function() {
    console.log('File upload script loaded');
    
    // एलिमेंट्स प्राप्त करें
    const form = document.getElementById('add-listing-form');
    const fileInput = document.getElementById('images');
    const previewContainer = document.getElementById('preview-container');
    const mainImageInput = document.getElementById('main-image');
    const addImageBtn = document.querySelector('.add-image-btn');
    
    let uploadedFiles = []; // अपलोड की गई फाइल्स का ट्रैक रखें
    
    console.log('Form:', form);
    console.log('File input:', fileInput);
    console.log('Preview container:', previewContainer);
    console.log('Main image input:', mainImageInput);
    
    // फाइल इनपुट को छिपाएं और कस्टम बटन से ट्रिगर करें
    if (fileInput && addImageBtn) {
        addImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });
    }
    
    if (fileInput) {
        // महत्वपूर्ण: फाइल इनपुट के डिफॉल्ट बिहेवियर को रोकें
        fileInput.addEventListener('change', function(e) {
            e.preventDefault(); // इवेंट को रोकें
            e.stopPropagation(); // इवेंट प्रोपगेशन को रोकें
            
            console.log('File input change event triggered');
            
            // फाइल्स चेक करें
            if (this.files.length === 0) {
                console.log('No files selected');
                return;
            }
            
            // प्रीव्यू कंटेनर को साफ न करें, बल्कि नई इमेजेज जोड़ें
            
            // प्रत्येक फाइल के लिए प्रीव्यू बनाएं
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                console.log('Processing file:', file.name);
                
                // फाइल टाइप चेक करें
                if (!file.type.match('image.*')) {
                    console.log('Not an image file:', file.name);
                    continue;
                }
                
                uploadedFiles.push(file);
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    
                    const radioInput = document.createElement('input');
                    radioInput.type = 'radio';
                    radioInput.name = 'main-image-select';
                    radioInput.value = uploadedFiles.length - 1;
                    radioInput.className = 'main-image-radio';
                    
                    // पहली इमेज को मेन इमेज के रूप में सेट करें
                    if (previewContainer.children.length === 0) {
                        radioInput.checked = true;
                        if (mainImageInput) mainImageInput.value = 0;
                    }
                    
                    radioInput.addEventListener('change', function() {
                        if (mainImageInput) mainImageInput.value = this.value;
                        console.log('Main image set to:', this.value);
                    });
                    
                    const label = document.createElement('label');
                    label.className = 'main-image-label';
                    label.textContent = 'Main Image';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'remove-image-btn';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        previewContainer.removeChild(previewItem);
                    });
                    
                    previewItem.appendChild(img);
                    previewItem.appendChild(radioInput);
                    previewItem.appendChild(label);
                    previewItem.appendChild(removeBtn);
                    
                    previewContainer.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            }
            
            return false; // इवेंट को रोकें
        });
    }
    
    // फॉर्म सबमिट हैंडलर - केवल Add Listing बटन पर क्लिक करने पर ही सबमिट होगा
    if (form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            // फॉर्म के डिफॉल्ट सबमिट को रोकें
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                return false;
            });
            
            // केवल सबमिट बटन पर क्लिक करने पर ही फॉर्म सबमिट करें
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Submit button clicked');
                
                // फॉर्म डेटा बनाएं
                const formData = new FormData(form);
                
                // लोडिंग मैसेज दिखाएं
                const loadingMsg = document.createElement('div');
                loadingMsg.className = 'loading-message';
                loadingMsg.textContent = 'Uploading... Please wait.';
                form.appendChild(loadingMsg);
                
                // AJAX रिक्वेस्ट भेजें
                fetch('add-listing.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response received:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    
                    // लोडिंग मैसेज हटाएं
                    if (form.contains(loadingMsg)) {
                        form.removeChild(loadingMsg);
                    }
                    
                    if (data.success) {
                        // पॉपअप के बजाय सीधे रीडायरेक्ट करें
                        window.location.href = 'dashboard.php';
                    } else {
                        console.error('Error:', data.message);
                        // साइलेंट एरर हैंडलिंग - पॉपअप के बिना
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message alert alert-danger';
                        errorMsg.textContent = data.message || 'Failed to add listing. Please try again.';
                        form.prepend(errorMsg);
                        
                        // 5 सेकंड बाद एरर मैसेज हटा दें
                        setTimeout(() => {
                            if (form.contains(errorMsg)) {
                                form.removeChild(errorMsg);
                            }
                        }, 5000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // लोडिंग मैसेज हटाएं
                    if (form.contains(loadingMsg)) {
                        form.removeChild(loadingMsg);
                    }
                    
                    // साइलेंट एरर हैंडलिंग
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message alert alert-danger';
                    errorMsg.textContent = 'An error occurred while submitting the form. Please try again.';
                    form.prepend(errorMsg);
                    
                    // 5 सेकंड बाद एरर मैसेज हटा दें
                    setTimeout(() => {
                        if (form.contains(errorMsg)) {
                            form.removeChild(errorMsg);
                        }
                    }, 5000);
                });
            });
        }
    }
}); 