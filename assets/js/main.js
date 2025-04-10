// मुख्य JavaScript फाइल

// फिल्टर टॉगल
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.querySelector('.filter-toggle');
    if (filterToggle) {
        filterToggle.addEventListener('click', function() {
            const filterContent = document.querySelector('.filter-content');
            if (filterContent) {
                filterContent.style.display = filterContent.style.display === 'block' ? 'none' : 'block';
                filterToggle.querySelector('i').classList.toggle('fa-chevron-down');
                filterToggle.querySelector('i').classList.toggle('fa-chevron-up');
            }
        });
    }
    
    // लोकेशन सर्च ऑटोकम्पलीट
    const locationInput = document.getElementById('location-search');
    const locationResults = document.getElementById('location-results');
    const locationIdInput = document.getElementById('location-id');
    
    if (locationInput && locationResults) {
        locationInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 2) {
                locationResults.innerHTML = '';
                locationResults.style.display = 'none';
                return;
            }
            
            // AJAX रिक्वेस्ट भेजें
            fetch(`${siteUrl}/ajax/search-locations.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    locationResults.innerHTML = '';
                    
                    if (data.length === 0) {
                        locationResults.style.display = 'none';
                        return;
                    }
                    
                    data.forEach(location => {
                        const item = document.createElement('div');
                        item.className = 'location-item';
                        item.textContent = `${location.name}, ${location.country}`;
                        item.dataset.id = location.id;
                        
                        item.addEventListener('click', function() {
                            locationInput.value = `${location.name}, ${location.country}`;
                            locationIdInput.value = location.id;
                            locationResults.innerHTML = '';
                            locationResults.style.display = 'none';
                        });
                        
                        locationResults.appendChild(item);
                    });
                    
                    locationResults.style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        });
        
        // बाहर क्लिक करने पर ड्रॉपडाउन बंद करें
        document.addEventListener('click', function(e) {
            if (!locationInput.contains(e.target) && !locationResults.contains(e.target)) {
                locationResults.innerHTML = '';
                locationResults.style.display = 'none';
            }
        });
    }
    
    // डार्क मोड टॉगल
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            
            // डार्क मोड प्रेफरेंस सेव करें
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
            
            // आइकन अपडेट करें
            const icon = this.querySelector('i');
            if (isDarkMode) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
        
        // पेज लोड होने पर डार्क मोड प्रेफरेंस चेक करें
        const savedDarkMode = localStorage.getItem('darkMode');
        if (savedDarkMode === 'enabled') {
            document.body.classList.add('dark-mode');
            const icon = darkModeToggle.querySelector('i');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }
});

// इमेज अपलोड प्रीव्यू - ग्लोबल स्कोप में डिफाइन करें
window.previewImages = function(input, previewContainer) {
    if (input.files) {
        const previewDiv = document.getElementById(previewContainer);
        previewDiv.innerHTML = '';
        
        const flex = document.createElement('div');
        flex.style.display = 'flex';
        flex.style.flexWrap = 'wrap';
        flex.style.gap = '10px';
        previewDiv.appendChild(flex);
        
        for (let i = 0; i < input.files.length; i++) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'image-preview-item';
                imgContainer.dataset.index = i;
                
                // इमेज एलिमेंट
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '5px';
                
                // रिमूव बटन
                const removeBtn = document.createElement('button');
                removeBtn.className = 'image-preview-remove';
                removeBtn.dataset.index = i;
                removeBtn.innerHTML = '×';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '5px';
                removeBtn.style.right = '5px';
                removeBtn.style.backgroundColor = 'red';
                removeBtn.style.color = 'white';
                removeBtn.style.border = 'none';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.width = '20px';
                removeBtn.style.height = '20px';
                removeBtn.style.display = 'flex';
                removeBtn.style.alignItems = 'center';
                removeBtn.style.justifyContent = 'center';
                removeBtn.style.cursor = 'pointer';
                
                removeBtn.addEventListener('click', function() {
                    // इमेज प्रीव्यू हटाएं
                    imgContainer.remove();
                    
                    // मेन इमेज अपडेट करें
                    updateMainImage();
                });
                
                // मेन इमेज बटन
                const mainBtn = document.createElement('button');
                mainBtn.className = 'image-preview-main';
                mainBtn.dataset.index = i;
                mainBtn.innerHTML = 'Main Image';
                mainBtn.style.position = 'absolute';
                mainBtn.style.bottom = '5px';
                mainBtn.style.left = '0';
                mainBtn.style.right = '0';
                mainBtn.style.margin = '0 auto';
                mainBtn.style.backgroundColor = '#007bff';
                mainBtn.style.color = 'white';
                mainBtn.style.border = 'none';
                mainBtn.style.borderRadius = '3px';
                mainBtn.style.padding = '2px 5px';
                mainBtn.style.fontSize = '10px';
                mainBtn.style.cursor = 'pointer';
                
                mainBtn.addEventListener('click', function() {
                    // सभी मेन इमेज बटन्स को रीसेट करें
                    document.querySelectorAll('.image-preview-main').forEach(btn => {
                        btn.style.backgroundColor = '#007bff';
                    });
                    
                    // इस बटन को हाइलाइट करें
                    mainBtn.style.backgroundColor = '#28a745';
                    
                    // हिडन इनपुट अपडेट करें
                    document.getElementById('main_image').value = i;
                });
                
                // इमेज कंटेनर स्टाइल
                imgContainer.style.position = 'relative';
                imgContainer.style.width = '100px';
                imgContainer.style.height = '100px';
                
                // एलिमेंट्स जोड़ें
                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                imgContainer.appendChild(mainBtn);
                
                // फ्लेक्स कंटेनर में जोड़ें
                flex.appendChild(imgContainer);
                
                // पहली इमेज को मेन इमेज के रूप में सेट करें
                if (i === 0) {
                    mainBtn.click();
                }
            };
            
            reader.readAsDataURL(input.files[i]);
        }
    }
};

// मेन इमेज अपडेट करें
function updateMainImage() {
    const containers = document.querySelectorAll('.image-preview-item');
    if (containers.length > 0) {
        // पहली इमेज को मेन इमेज के रूप में सेट करें
        const firstMainBtn = containers[0].querySelector('.image-preview-main');
        if (firstMainBtn) {
            firstMainBtn.click();
        }
    } else {
        // कोई इमेज नहीं है
        document.getElementById('main_image').value = '';
    }
}

// टैग इनपुट
function addTag(inputId, tagsContainerId) {
    const input = document.getElementById(inputId);
    const tagsContainer = document.getElementById(tagsContainerId);
    
    if (input.value.trim() !== '') {
        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.textContent = input.value.trim();
        
        const removeBtn = document.createElement('span');
        removeBtn.className = 'tag-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() {
            tagsContainer.removeChild(tag);
            updateTagsInput();
        };
        
        tag.appendChild(removeBtn);
        tagsContainer.appendChild(tag);
        
        input.value = '';
        updateTagsInput();
    }
}

function updateTagsInput() {
    const tagsContainer = document.getElementById('tags-container');
    const tagsInput = document.getElementById('tags-input-hidden');
    
    const tags = [];
    const tagElements = tagsContainer.querySelectorAll('.tag');
    
    tagElements.forEach(function(tag) {
        tags.push(tag.textContent.replace('×', '').trim());
    });
    
    tagsInput.value = tags.join(',');
}

// सोशल मीडिया फील्ड्स जोड़ें
function addSocialMediaField() {
    const container = document.getElementById('social-media-container');
    const index = container.children.length;
    
    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'social-media-field mb-3 row';
    
    fieldDiv.innerHTML = `
        <div class="col-md-4">
            <select name="social_media_type[]" class="form-select" required>
                <option value="">Select Platform</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="telegram">Telegram</option>
                <option value="imo">IMO</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="twitter">X (Twitter)</option>
                <option value="tiktok">TikTok</option>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" name="social_media_value[]" class="form-control" placeholder="Username/Number/Link" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger remove-field" onclick="removeField(this)">Remove</button>
        </div>
    `;
    
    container.appendChild(fieldDiv);
}

// फील्ड हटाएं
function removeField(button) {
    const field = button.closest('.social-media-field');
    field.remove();
}

// मोबाइल मेनू टॉगल
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('active');
            }
        });
    }
});

// फॉर्म सबमिशन हैंडलर
document.addEventListener('DOMContentLoaded', function() {
    const addListingForm = document.getElementById('add-listing-form');
    console.log('Form element:', addListingForm); // डीबग
    
    if (addListingForm) {
        console.log('Form found, adding event listener');
        
        addListingForm.addEventListener('submit', function(e) {
            console.log('Form submitted'); // डीबग
            e.preventDefault(); // डिफॉल्ट सबमिशन रोकें
            
            // फॉर्म डेटा प्राप्त करें
            const formData = new FormData(this);
            
            // फाइल इनपुट से फाइल्स प्राप्त करें
            const fileInput = document.getElementById('images');
            console.log('File input:', fileInput); // डीबग
            console.log('Files:', fileInput ? fileInput.files : 'No files'); // डीबग
            
            if (fileInput && fileInput.files.length > 0) {
                console.log('Files found:', fileInput.files.length); // डीबग
                
                // पहले से जोड़े गए फाइल्स को हटाएं (अगर कोई हो)
                if (formData.has('images[]')) {
                    formData.delete('images[]');
                }
                
                // फाइल्स को FormData में जोड़ें
                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append('images[]', fileInput.files[i]);
                    console.log('Added file:', fileInput.files[i].name); // डीबग
                }
            } else {
                console.log('No files selected');
                alert('Please select at least one image');
                return;
            }
            
            // FormData के कंटेंट को लॉग करें
            console.log('FormData entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0], pair[1]);
            }
            
            // फॉर्म सबमिट करें
            fetch(addListingForm.action || window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status); // डीबग
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                console.log('Response data:', data); // डीबग
                // सफल सबमिशन के बाद रीडायरेक्ट करें या मैसेज दिखाएं
                alert('Form submitted successfully!');
                // window.location.href = 'my-listings.php?success=1';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
            });
        });
    } else {
        console.log('Form not found');
    }
});

// फाइल अपलोड और प्रीव्यू फंक्शनैलिटी
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('images');
    const previewContainer = document.getElementById('preview-container');
    const mainImageInput = document.getElementById('main-image');
    const form = document.getElementById('add-listing-form');
    
    if (fileInput && previewContainer) {
        // फाइल इनपुट चेंज इवेंट
        fileInput.addEventListener('change', function() {
            previewImages();
        });
        
        // इमेज प्रीव्यू फंक्शन
        function previewImages() {
            previewContainer.innerHTML = '';
            
            if (fileInput.files && fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'image-preview-item';
                        imgContainer.style.position = 'relative';
                        imgContainer.style.width = '100px';
                        imgContainer.style.height = '100px';
                        imgContainer.style.margin = '5px';
                        imgContainer.style.display = 'inline-block';
                        
                        // इमेज एलिमेंट
                        const img = document.createElement('img');
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '5px';
                        img.src = e.target.result;
                        
                        // रिमूव बटन
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'image-preview-remove';
                        removeBtn.innerHTML = '×';
                        removeBtn.style.position = 'absolute';
                        removeBtn.style.top = '5px';
                        removeBtn.style.right = '5px';
                        removeBtn.style.backgroundColor = 'red';
                        removeBtn.style.color = 'white';
                        removeBtn.style.border = 'none';
                        removeBtn.style.borderRadius = '50%';
                        removeBtn.style.width = '20px';
                        removeBtn.style.height = '20px';
                        removeBtn.style.display = 'flex';
                        removeBtn.style.alignItems = 'center';
                        removeBtn.style.justifyContent = 'center';
                        removeBtn.style.cursor = 'pointer';
                        
                        // मेन इमेज बटन
                        const mainBtn = document.createElement('button');
                        mainBtn.className = 'image-preview-main';
                        mainBtn.innerHTML = 'Main Image';
                        mainBtn.dataset.index = i;
                        mainBtn.style.position = 'absolute';
                        mainBtn.style.bottom = '5px';
                        mainBtn.style.left = '0';
                        mainBtn.style.right = '0';
                        mainBtn.style.margin = '0 auto';
                        mainBtn.style.backgroundColor = '#007bff';
                        mainBtn.style.color = 'white';
                        mainBtn.style.border = 'none';
                        mainBtn.style.borderRadius = '3px';
                        mainBtn.style.padding = '2px 5px';
                        mainBtn.style.fontSize = '10px';
                        mainBtn.style.cursor = 'pointer';
                        
                        // मेन इमेज बटन क्लिक इवेंट
                        mainBtn.addEventListener('click', function() {
                            // सभी बटन्स को रीसेट करें
                            document.querySelectorAll('.image-preview-main').forEach(btn => {
                                btn.style.backgroundColor = '#007bff';
                            });
                            
                            // इस बटन को हाइलाइट करें
                            mainBtn.style.backgroundColor = '#28a745';
                            
                            // मेन इमेज इनपुट अपडेट करें
                            mainImageInput.value = i;
                        });
                        
                        // एलिमेंट्स जोड़ें
                        imgContainer.appendChild(img);
                        imgContainer.appendChild(removeBtn);
                        imgContainer.appendChild(mainBtn);
                        
                        // कंटेनर में जोड़ें
                        previewContainer.appendChild(imgContainer);
                        
                        // पहली इमेज को मेन इमेज के रूप में सेट करें
                        if (i === 0) {
                            mainBtn.click();
                        }
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        }
    }
    
    // फॉर्म सबमिशन
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // फाइल्स चेक करें
            if (fileInput && (!fileInput.files || fileInput.files.length === 0)) {
                alert('Please select at least one image');
                return;
            }
            
            // फॉर्म डेटा
            const formData = new FormData(form);
            
            // लोडिंग मैसेज
            const loadingMsg = document.createElement('div');
            loadingMsg.innerHTML = 'Submitting form...';
            loadingMsg.style.padding = '10px';
            loadingMsg.style.backgroundColor = '#f8f9fa';
            loadingMsg.style.borderRadius = '5px';
            loadingMsg.style.marginTop = '10px';
            form.appendChild(loadingMsg);
            
            // AJAX रिक्वेस्ट
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Form submission response:', data);
                
                // लोडिंग मैसेज हटाएं
                form.removeChild(loadingMsg);
                
                if (data.success) {
                    alert('Listing added successfully!');
                    window.location.href = 'my-listings.php?success=1';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // लोडिंग मैसेज हटाएं
                form.removeChild(loadingMsg);
                
                alert('An error occurred while submitting the form. Please try again.');
            });
        });
    }
}); 