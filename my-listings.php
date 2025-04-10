<?php
// सेशन स्टार्ट
session_start();

// कॉन्फिग और डेटाबेस कनेक्शन
require_once 'includes/config.php';

// चेक करें कि यूजर लॉगिन है या नहीं
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// यूजर आईडी प्राप्त करें
$userId = $_SESSION['user_id'];

// सक्सेस और एरर मैसेज
$success_message = '';
$error_message = '';

// अगर एक्शन पैरामीटर है
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $listingId = (int)$_GET['id'];
    
    // चेक करें कि लिस्टिंग इस यूजर की है
    $stmt = $db->prepare("SELECT id FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$listingId, $userId]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($listing) {
        // एक्शन के अनुसार प्रोसेस करें
        switch ($action) {
            case 'delete':
                // लिस्टिंग डिलीट करें
                $stmt = $db->prepare("DELETE FROM listings WHERE id = ?");
                if ($stmt->execute([$listingId])) {
                    // इमेजेज भी डिलीट करें
                    $stmt = $db->prepare("DELETE FROM images WHERE listing_id = ?");
                    $stmt->execute([$listingId]);
                    
                    // सोशल मीडिया लिंक्स डिलीट करें
                    $stmt = $db->prepare("DELETE FROM social_media WHERE listing_id = ?");
                    $stmt->execute([$listingId]);
                    
                    // टैग्स डिलीट करें
                    $stmt = $db->prepare("DELETE FROM listing_tags WHERE listing_id = ?");
                    $stmt->execute([$listingId]);
                    
                    $success_message = "Listing deleted successfully!";
                } else {
                    $error_message = "Failed to delete listing.";
                }
                break;
                
            case 'activate':
                // लिस्टिंग एक्टिवेट करें
                $stmt = $db->prepare("UPDATE listings SET is_active = 1 WHERE id = ?");
                if ($stmt->execute([$listingId])) {
                    $success_message = "Listing activated successfully!";
                } else {
                    $error_message = "Failed to activate listing.";
                }
                break;
                
            case 'deactivate':
                // लिस्टिंग डीएक्टिवेट करें
                $stmt = $db->prepare("UPDATE listings SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$listingId])) {
                    $success_message = "Listing deactivated successfully!";
                } else {
                    $error_message = "Failed to deactivate listing.";
                }
                break;
                
            case 'request-verification':
                // वेरिफिकेशन कोड जनरेट करें
                $verificationCode = substr(md5(uniqid(rand(), true)), 0, 10);
                
                // लिस्टिंग के लिए वेरिफिकेशन रिक्वेस्ट करें
                $stmt = $db->prepare("UPDATE listings SET verification_code = ? WHERE id = ?");
                if ($stmt->execute([$verificationCode, $listingId])) {
                    // वेरिफिकेशन पेज पर रीडायरेक्ट करें
                    header("Location: verify-listing.php?id=$listingId&code=$verificationCode");
                    exit;
                } else {
                    $error_message = "Failed to request verification.";
                }
                break;
        }
    } else {
        $error_message = "Invalid listing or you don't have permission to perform this action.";
    }
}

// यूजर की सभी लिस्टिंग्स प्राप्त करें
$stmt = $db->prepare("
    SELECT l.*, c.name as category_name, loc.name as location_name, 
    (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image,
    (SELECT COUNT(*) > 0 FROM verification_photos WHERE listing_id = l.id) as has_verification_photo
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$userId]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// हेडर इंक्लूड करें
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="text-white">My Listings</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="add-listing.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus"></i> Add New Listing
            </a>
        </div>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($listings)): ?>
        <div class="card bg-dark text-white">
            <div class="card-body text-center py-5">
                <h3>You don't have any listings yet</h3>
                <p class="mb-4">Create your first listing to get started</p>
                <a href="add-listing.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus"></i> Add New Listing
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($listings as $listing): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card bg-dark text-white h-100">
                        <div class="position-relative">
                            <?php if ($listing['main_image']): ?>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>">
                                    <img src="<?php echo $listing['main_image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>" style="height: 250px; object-fit: cover;">
                                </a>
                            <?php else: ?>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>">
                                    <div class="bg-secondary text-center py-5" style="height: 250px;">
                                        <i class="fas fa-image fa-3x text-white-50"></i>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <div class="position-absolute top-0 end-0 p-2">
                                <?php if ($listing['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                                
                                <?php if ($listing['is_featured']): ?>
                                    <span class="badge bg-warning text-dark">Featured</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Edit and Delete buttons on image -->
                            <div class="position-absolute bottom-0 end-0 p-2">
                                <a href="insights.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-info me-1" title="View Insights">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-primary me-1" title="Edit Listing">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $listing['id']; ?>" data-title="<?php echo htmlspecialchars($listing['title']); ?>" data-image="<?php echo $listing['main_image']; ?>" title="Delete Listing">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body text-center p-2">
                            <h5 class="card-title mb-0">
                                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="text-white text-decoration-none">
                                    <?php echo htmlspecialchars($listing['title']); ?>
                                </a>
                            </h5>
                        </div>
                        
                        <!-- वेरिफिकेशन स्टेटस -->
                        <?php if ($listing['is_verified']): ?>
                            <div class="bg-success text-white py-1 px-2 text-center">
                                <i class="fas fa-check-circle me-1"></i> Verified Profile
                            </div>
                        <?php elseif ($listing['verification_code'] && isset($listing['has_verification_photo']) && $listing['has_verification_photo']): ?>
                            <div class="bg-warning text-dark py-1 px-2 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-clock me-1"></i> Verification Pending
                                </div>
                                <a href="verify-listing.php?id=<?php echo $listing['id']; ?>&code=<?php echo $listing['verification_code']; ?>" class="btn btn-sm btn-warning py-0 px-2">
                                    <i class="fas fa-arrow-right"></i> Continue
                                </a>
                            </div>
                        <?php elseif ($listing['verification_code']): ?>
                            <div class="bg-info text-white py-1 px-2 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-upload me-1"></i> Verify Photo
                                </div>
                                <a href="verify-listing.php?id=<?php echo $listing['id']; ?>&code=<?php echo $listing['verification_code']; ?>" class="btn btn-sm btn-info py-0 px-2">
                                    <i class="fas fa-arrow-right"></i> Continue
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-secondary text-white py-1 px-2 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-exclamation-circle me-1"></i> Not Verified
                                </div>
                                <a href="my-listings.php?action=request-verification&id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-primary py-0 px-2">
                                    <i class="fas fa-shield-alt"></i> Verify
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- एक्टिवेशन स्टेटस -->
                        <div class="card-footer bg-dark border-secondary p-0">
                            <div class="d-flex justify-content-center py-1">
                                <?php if ($listing['is_active']): ?>
                                    <a href="my-listings.php?action=deactivate&id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Are you sure you want to deactivate this listing?')">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="my-listings.php?action=activate&id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-play"></i> Activate
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Custom Delete Confirmation Dialog -->
<div id="customDeleteModal" class="custom-modal" style="display: none;">
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <h5>Confirm Delete</h5>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <div class="custom-modal-body">
            <p>Are you sure you want to delete this listing? This action cannot be undone.</p>
            <div class="listing-preview">
                <img id="deleteImagePreview" src="" alt="Listing Image">
                <div id="deleteTitlePreview"></div>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn-cancel">Cancel</button>
            <a href="#" id="confirmDeleteBtn" class="btn-delete">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<style>
/* Custom Modal Styles */
.custom-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.custom-modal-content {
    background-color: #1e1e1e;
    width: 300px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.custom-modal-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #333;
}

.custom-modal-header h5 {
    margin: 0;
    color: #fff;
    font-size: 18px;
}

.close-modal {
    background: none;
    border: none;
    color: #aaa;
    font-size: 22px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.custom-modal-body {
    padding: 15px;
    color: #fff;
}

.custom-modal-body p {
    margin-bottom: 15px;
}

.listing-preview {
    text-align: center;
    margin-bottom: 10px;
}

.listing-preview img {
    max-width: 100%;
    max-height: 120px;
    border-radius: 4px;
    margin-bottom: 10px;
    object-fit: cover;
}

.custom-modal-footer {
    padding: 15px;
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-cancel {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
}

.btn-delete i {
    margin-right: 5px;
}

/* Prevent body scrolling when modal is open */
body.modal-open {
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const customModal = document.getElementById('customDeleteModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.btn-cancel');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteImagePreview = document.getElementById('deleteImagePreview');
    const deleteTitlePreview = document.getElementById('deleteTitlePreview');
    
    // Add click event to all delete buttons
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get listing data from data attributes
            const listingId = this.getAttribute('data-id');
            const listingTitle = this.getAttribute('data-title');
            const listingImage = this.getAttribute('data-image');
            
            // Set the delete confirmation link
            confirmDeleteBtn.href = `my-listings.php?action=delete&id=${listingId}`;
            
            // Set the image and title in the preview
            if (listingImage) {
                deleteImagePreview.src = listingImage;
                deleteImagePreview.style.display = 'block';
            } else {
                deleteImagePreview.style.display = 'none';
            }
            
            deleteTitlePreview.textContent = listingTitle;
            
            // Show the modal
            customModal.style.display = 'flex';
            document.body.classList.add('modal-open');
        });
    });
    
    // Close modal when clicking the close button
    closeModalBtn.addEventListener('click', function() {
        customModal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
    
    // Close modal when clicking the cancel button
    cancelBtn.addEventListener('click', function() {
        customModal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
    
    // Close modal when clicking outside the modal content
    customModal.addEventListener('click', function(e) {
        if (e.target === customModal) {
            customModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
});
</script> 