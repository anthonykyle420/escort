<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get categories from database
$categories = select("SELECT * FROM categories WHERE status = 'active'");
?>

<form id="listingForm" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <!-- Basic Information -->
    <div class="section mb-4">
        <h4 class="text-primary">Basic Information</h4>
        <hr class="border-secondary">
        
        <div class="row g-3">
            <!-- Title -->
            <div class="col-12">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-dark text-white" 
                       name="title" required>
                <div class="invalid-feedback">Please enter a title</div>
            </div>

            <!-- Description -->
            <div class="col-12">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control bg-dark text-white" 
                          name="description" rows="4" required></textarea>
                <div class="invalid-feedback">Please enter a description</div>
            </div>

            <!-- Category -->
            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select bg-dark text-white" name="category" required>
                    <option value="">Select Category</option>
                    <optgroup label="Escort Services">
                        <option value="male_escort">Male Escort</option>
                        <option value="female_escort">Female Escort</option>
                        <option value="couple_escort">Couple</option>
                        <option value="trans_escort">Trans Escort</option>
                    </optgroup>
                    <optgroup label="Other Services">
                        <option value="events">Events & Entertainment</option>
                        <option value="dating">Dating</option>
                    </optgroup>
                </select>
                <div class="invalid-feedback">Please select a category</div>
            </div>

            <!-- Gender -->
            <div class="col-md-6">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select class="form-select bg-dark text-white" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="trans">Transgender</option>
                </select>
                <div class="invalid-feedback">Please select your gender</div>
            </div>

            <!-- Location with Search -->
            <div class="col-12">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="text" class="form-control bg-dark text-white" 
                           id="locationInput" name="location" 
                           placeholder="Search location..." required>
                    <input type="hidden" id="locationId" name="location_id">
                    <div id="locationSuggestions" 
                         class="position-absolute w-100 mt-1 bg-dark border border-secondary rounded-3 d-none"
                         style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                    </div>
                </div>
                <div class="invalid-feedback">Please select a location</div>
            </div>
        </div>
    </div>

    <!-- Personal Details -->
    <div class="section mb-4">
        <h4 class="text-primary">Personal Details</h4>
        <hr class="border-secondary">

        <div class="row g-3">
            <!-- Age -->
            <div class="col-md-4">
                <label class="form-label">Age <span class="text-danger">*</span></label>
                <input type="number" class="form-control bg-dark text-white" 
                       name="age" min="18" max="99" required>
                <div class="invalid-feedback">Age must be between 18 and 99</div>
            </div>

            <!-- Height -->
            <div class="col-md-4">
                <label class="form-label">Height</label>
                <select class="form-select bg-dark text-white" name="height">
                    <option value="">Select Height</option>
                </select>
            </div>

            <!-- Weight -->
            <div class="col-md-4">
                <label class="form-label">Weight (kg)</label>
                <input type="number" class="form-control bg-dark text-white" 
                       name="weight" min="30" max="200">
                <div class="invalid-feedback">Weight must be between 30 and 200 kg</div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <!-- Body Type -->
            <div class="col-md-6">
                <label class="form-label">Body Type</label>
                <select class="form-select bg-dark text-white" name="body_type">
                    <option value="">Select Body Type</option>
                    <option value="slim">Slim</option>
                    <option value="athletic">Athletic</option>
                    <option value="average">Average</option>
                    <option value="curvy">Curvy</option>
                    <option value="muscular">Muscular</option>
                    <option value="bbw">BBW</option>
                </select>
            </div>

            <!-- Sexuality -->
            <div class="col-md-6">
                <label class="form-label">Sexuality</label>
                <select class="form-select bg-dark text-white" name="sexuality">
                    <option value="">Select Sexuality</option>
                    <option value="straight">Straight</option>
                    <option value="gay">Gay</option>
                    <option value="lesbian">Lesbian</option>
                    <option value="bisexual">Bisexual</option>
                    <option value="pansexual">Pansexual</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div class="section mb-4">
        <h4 class="text-primary">Pricing</h4>
        <hr class="border-secondary">

        <!-- WhatsApp Number -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">WhatsApp Number <span class="text-danger">*</span></label>
                <div class="input-group">
                    <button class="btn btn-dark border-secondary dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" id="countryCodeBtn">
                        <img src="assets/flags/in.svg" width="20" id="selectedFlag"> +91
                    </button>
                    <ul class="dropdown-menu bg-dark country-code-menu" style="max-height: 200px; overflow-y: auto;">
                        <!-- Will be populated by JavaScript -->
                    </ul>
                    <input type="hidden" name="country_code" id="countryCode" value="+91">
                    <input type="tel" class="form-control bg-dark text-white" 
                           name="whatsapp" placeholder="WhatsApp number" required>
                </div>
                <div class="invalid-feedback">Please enter a valid WhatsApp number</div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Currency <span class="text-danger">*</span></label>
                <select class="form-select bg-dark text-white" name="currency" id="currencySelect" required>
                    <option value="INR" data-symbol="₹">Indian Rupee (₹)</option>
                    <option value="USD" data-symbol="$">US Dollar ($)</option>
                    <option value="EUR" data-symbol="€">Euro (€)</option>
                    <option value="GBP" data-symbol="£">British Pound (£)</option>
                    <option value="AUD" data-symbol="A$">Australian Dollar (A$)</option>
                    <option value="SGD" data-symbol="S$">Singapore Dollar (S$)</option>
                    <!-- Add more currencies as needed -->
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">1 Hour Rate <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-dark text-white border-secondary currency-symbol">₹</span>
                    <input type="number" class="form-control bg-dark text-white" 
                           name="hourly_rate" min="0" required>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Full Night Rate</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark text-white border-secondary currency-symbol">₹</span>
                    <input type="number" class="form-control bg-dark text-white" 
                           name="night_rate" min="0">
                </div>
            </div>
        </div>

        <!-- Special Offer Section -->
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header bg-dark border-secondary">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="hasSpecialOffer" name="has_special_offer">
                    <label class="form-check-label" for="hasSpecialOffer">Add Special Offer</label>
                </div>
            </div>
            <div class="card-body d-none" id="specialOfferSection">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Offer Price</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-secondary currency-symbol">₹</span>
                            <input type="number" class="form-control bg-dark text-white" 
                                   name="offer_price" min="0">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valid Until</label>
                        <input type="datetime-local" class="form-control bg-dark text-white" 
                               name="offer_valid_until">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Offer Description</label>
                    <textarea class="form-control bg-dark text-white" 
                             name="offer_description" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Section -->
    <div class="section mb-4">
        <h4 class="text-primary">Social Media (Optional)</h4>
        <hr class="border-secondary">
        
        <div id="socialMediaContainer">
            <div class="social-media-item mb-3">
                <div class="input-group">
                    <select class="form-select bg-dark text-white w-auto" name="social_media_type[]">
                        <option value="">Select Platform</option>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="twitter">Twitter/X</option>
                        <option value="snapchat">Snapchat</option>
                        <option value="telegram">Telegram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                        <option value="onlyfans">OnlyFans</option>
                        <option value="linkedin">LinkedIn</option>
                    </select>
                    <input type="text" class="form-control bg-dark text-white" 
                           name="social_media_url[]" placeholder="Enter profile URL">
                    <button type="button" class="btn btn-danger remove-social-media">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <button type="button" class="btn btn-outline-primary btn-sm" id="addSocialMedia">
            <i class="fas fa-plus"></i> Add Social Media
        </button>
    </div>

    <!-- Images Section -->
    <div class="section mb-4">
        <h4 class="text-primary">Images</h4>
        <hr class="border-secondary">

        <!-- Main Image -->
        <div class="mb-4">
            <label class="form-label">Main Image <span class="text-danger">*</span></label>
            <div class="main-image-preview d-none mb-3">
                <div class="position-relative d-inline-block">
                    <img src="" alt="Main preview" class="rounded" style="max-height: 200px;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                            id="removeMainImage">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="upload-zone p-4 text-center border border-dashed rounded" id="mainImageUpload">
                <input type="file" id="mainImage" name="main_image" 
                       accept="image/*" class="d-none" required>
                <i class="fas fa-cloud-upload-alt fa-2x mb-3"></i>
                <h5>Select Main Image</h5>
                <p class="text-muted small">Click to browse or drag & drop</p>
                <p class="text-muted small">Maximum size: 5MB</p>
            </div>
            <div class="invalid-feedback">Please select a main image</div>
        </div>

        <!-- Additional Images -->
        <div class="mb-3">
            <label class="form-label">Additional Images (Max 10)</label>
            <div class="additional-images-preview d-flex flex-wrap gap-3 mb-3"></div>
            <div class="upload-zone p-4 text-center border border-dashed rounded" id="additionalImagesUpload">
                <input type="file" id="additionalImages" name="additional_images[]" 
                       multiple accept="image/*" class="d-none">
                <i class="fas fa-images fa-2x mb-3"></i>
                <h5>Add More Images</h5>
                <p class="text-muted small">Click to browse or drag & drop</p>
                <p class="text-muted small">Maximum 10 images, 5MB each</p>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="text-center mt-5">
        <button type="submit" class="btn btn-primary btn-lg px-5 py-3" id="submitButton">
            <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
            <i class="fas fa-check me-2"></i> Submit Listing
        </button>
    </div>
</form>

<!-- Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Image Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Preview" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="position-fixed top-0 start-0 w-100 h-100 d-none" 
     id="loadingOverlay" 
     style="background: rgba(0,0,0,0.8); z-index: 9999;">
    <div class="position-absolute top-50 start-50 translate-middle text-center">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="text-white">Uploading...</h5>
        <p class="text-white-50" id="uploadProgress">0%</p>
    </div>
</div> 