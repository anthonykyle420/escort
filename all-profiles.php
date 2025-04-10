<?php
$pageTitle = 'All Profiles';
include 'includes/header.php';

// पेजिनेशन सेटअप
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12; // एक पेज पर 12 प्रोफाइल्स (4x3 ग्रिड)
$offset = ($page - 1) * $perPage;

// टोटल प्रोफाइल्स काउंट करें
$totalProfiles = $db->selectOne("SELECT COUNT(*) as count FROM listings WHERE is_active = 1");
$totalPages = ceil($totalProfiles['count'] / $perPage);

// प्रोफाइल्स फेच करें
$profiles = $db->select("
    SELECT l.*, c.name as category_name, loc.name as location_name, 
           i.image_path as main_image 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    JOIN locations loc ON l.location_id = loc.id 
    LEFT JOIN images i ON l.id = i.listing_id AND i.is_main = 1 
    WHERE l.is_active = 1
    ORDER BY l.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
?>

<section class="all-profiles py-4">
    <div class="container">
        <h1 class="section-title mb-4">All Profiles</h1>
        
        <div class="row">
            <?php if (!empty($profiles)): ?>
                <?php foreach ($profiles as $profile): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card listing-card h-100">
                        <?php if (!empty($profile['is_verified'])): ?>
                        <div class="verified-badge" title="Verified Profile">
                            <i class="fas fa-check"></i>
                        </div>
                        <?php endif; ?>
                        <div class="card-badges">
                            <?php if ($profile['is_special']): ?>
                            <span class="badge bg-danger">Special</span>
                            <?php endif; ?>
                            <?php if ($profile['is_featured']): ?>
                            <span class="badge bg-primary">Featured</span>
                            <?php endif; ?>
                            <?php if ($profile['is_popular']): ?>
                            <span class="badge bg-success">Popular</span>
                            <?php endif; ?>
                        </div>
                        <a href="view-profile.php?id=<?php echo $profile['id']; ?>">
                            <img src="<?php echo !empty($profile['main_image']) ? $profile['main_image'] : 'assets/images/no-image.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($profile['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($profile['title']); ?></h5>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No profiles found.</div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- पेजिनेशन -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                // पेजिनेशन लिंक्स
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                            <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                          </li>';
                }
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 