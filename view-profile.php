<?php
$pageTitle = 'Profile';
include 'includes/header.php';

// प्रोफाइल आईडी प्राप्त करें
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// प्रोफाइल डेटा प्राप्त करें
$listing = selectOne("
    SELECT l.*, c.name as category_name, loc.name as location_name
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    JOIN locations loc ON l.location_id = loc.id 
    WHERE l.id = :id AND l.is_active = 1
", ['id' => $id]);

if (!$listing) {
    header('Location: index.php');
    exit;
}

// प्रोफाइल व्यू काउंट अपडेट करें
$db->prepare("
    UPDATE listings 
    SET total_views = total_views + 1, 
        today_views = today_views + 1 
    WHERE id = :id
")->execute(['id' => $id]);

// व्यू को listing_views टेबल में रिकॉर्ड करें
$db->prepare("
    INSERT INTO listing_views (listing_id, ip_address, user_agent, referrer)
    VALUES (:listing_id, :ip_address, :user_agent, :referrer)
")->execute([
    'listing_id' => $id,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'referrer' => $_SERVER['HTTP_REFERER'] ?? null
]);
?>

<main class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title mb-3">
                            <?php echo htmlspecialchars($listing['title']); ?>
                            <?php if ($listing['is_verified']): ?>
                                <i class="fas fa-check-circle verified-badge" title="Verified"></i>
                            <?php endif; ?>
                        </h1>
                        
                        <div class="mb-4">
                            <?php if ($listing['is_special']): ?>
                                <span class="badge bg-danger me-2">Special</span>
                            <?php endif; ?>
                            
                            <?php if ($listing['is_featured']): ?>
                                <span class="badge bg-primary me-2">Featured</span>
                            <?php endif; ?>
                            
                            <?php if ($listing['is_popular']): ?>
                                <span class="badge bg-success me-2">Popular</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($listing['location_name']); ?></p>
                                <p><i class="fas fa-tag"></i> <strong>Category:</strong> <?php echo htmlspecialchars($listing['category_name']); ?></p>
                                <p><i class="fas fa-birthday-cake"></i> <strong>Age:</strong> <?php echo $listing['age']; ?></p>
                                <p><i class="fas fa-ruler-vertical"></i> <strong>Height:</strong> <?php echo $listing['height']; ?> cm</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-weight"></i> <strong>Weight:</strong> <?php echo $listing['weight']; ?> kg</p>
                                <p><i class="fas fa-money-bill"></i> <strong>Price:</strong> <?php echo $listing['price'] . ' ' . $listing['currency'] . '/' . $listing['price_type']; ?></p>
                                <p><i class="fas fa-heart"></i> <strong>Sexuality:</strong> <?php echo htmlspecialchars($listing['sexuality']); ?></p>
                                <p><i class="fas fa-eye"></i> <strong>Views:</strong> <?php echo $listing['total_views']; ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4>Description</h4>
                            <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                        </div>
                        
                        <div class="text-center">
                            <a href="tel:<?php echo $listing['country_code'] . $listing['contact_number']; ?>" class="btn btn-danger btn-lg" onclick="recordContactClick(<?php echo $id; ?>, 'phone')">
                                <i class="fas fa-phone-alt me-2"></i> Call Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <p><i class="fas fa-phone-alt"></i> <strong>Phone:</strong> 
                            <a href="tel:<?php echo $listing['country_code'] . $listing['contact_number']; ?>" onclick="recordContactClick(<?php echo $id; ?>, 'phone')">
                                <?php echo $listing['country_code'] . ' ' . $listing['contact_number']; ?>
                            </a>
                        </p>
                        <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($listing['location_name']); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Safety Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Always meet in a public place first</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Inform someone about your whereabouts</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Verify identity before meeting</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Trust your instincts</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function recordContactClick(listingId, contactType = 'phone') {
    // AJAX रिक्वेस्ट भेजें कॉन्टैक्ट क्लिक रिकॉर्ड करने के लिए
    fetch('record_contact_click.php?id=' + listingId + '&type=' + contactType, {
        method: 'GET'
    });
}
</script>

<?php include 'includes/footer.php'; ?> 