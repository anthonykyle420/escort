<?php
// डीबग के लिए एरर रिपोर्टिंग ऑन करें
error_reporting(E_ALL);
ini_set('display_errors', 1);

// सेशन स्टार्ट करें
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Home';
include 'includes/header.php';

// डेटाबेस क्वेरीज़
try {
    global $db;
    
    // स्पेशल लिस्टिंग्स
    $specialListings = $db->query("
        SELECT l.*, c.name as category_name, loc.name as location_name, 
               i.image_path as main_image 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        JOIN locations loc ON l.location_id = loc.id 
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_main = 1 
        WHERE l.is_special = 1 AND l.is_active = 1 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ")->fetchAll();

    // पॉपुलर लिस्टिंग्स
    $popularListings = $db->query("
        SELECT l.*, c.name as category_name, loc.name as location_name, 
               i.image_path as main_image 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        JOIN locations loc ON l.location_id = loc.id 
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_main = 1 
        WHERE l.is_active = 1 
        ORDER BY l.total_views DESC 
        LIMIT 10
    ")->fetchAll();

    // फीचर्ड लिस्टिंग्स
    $featuredListings = $db->query("
        SELECT l.*, c.name as category_name, loc.name as location_name, 
               i.image_path as main_image 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        JOIN locations loc ON l.location_id = loc.id 
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_main = 1 
        WHERE l.is_featured = 1 AND l.is_active = 1 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ")->fetchAll();

    // पेजिनेशन के लिए
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;

    // सभी लिस्टिंग्स
    $stmt = $db->prepare("
        SELECT l.*, c.name as category_name, loc.name as location_name, 
               i.image_path as main_image 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        JOIN locations loc ON l.location_id = loc.id 
        LEFT JOIN images i ON l.id = i.listing_id AND i.is_main = 1 
        WHERE l.is_active = 1 
        ORDER BY l.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $allListings = $stmt->fetchAll();

    // टोटल लिस्टिंग्स काउंट
    $totalListings = $db->query("SELECT COUNT(*) FROM listings WHERE is_active = 1")->fetchColumn();
    $totalPages = ceil($totalListings / $perPage);
    
    // सभी लोकेशन्स
    $locations = $db->query("SELECT * FROM locations ORDER BY name")->fetchAll();
    
    // सभी कैटेगरीज
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    
} catch (PDOException $e) {
    // एरर हैंडलिंग
    $error = "An error occurred while fetching listings.";
    error_log($e->getMessage());
}
?>

<div class="container py-5">
    <!-- Search Bar -->
    <div class="row mb-5">
        <div class="col-md-8 mx-auto">
            <form action="search.php" method="GET" class="search-form">
                <div class="input-group mb-3">
                    <input type="text" name="q" class="form-control form-control-lg" placeholder="Search escorts..." aria-label="Search">
                    <button class="btn btn-primary btn-lg" type="submit">Search</button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="location-search-container">
                            <input type="text" id="location-search" class="form-control" placeholder="Search location...">
                            <input type="hidden" name="location_id" id="location-id">
                            <div class="location-results" id="location-results"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="display-4 text-white mb-4">Welcome to Escort Directory</h1>
            <p class="lead text-light mb-4">Find the best escort services in your area</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Special Listings Section -->
    <?php if (!empty($specialListings)): ?>
    <div class="mb-5">
        <h2 class="section-title">Special Listings</h2>
        <div class="swiper special-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($specialListings as $listing): ?>
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <img src="<?php echo !empty($listing['main_image']) ? htmlspecialchars($listing['main_image']) : 'assets/images/no-image.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($listing['location_name']); ?></p>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Popular Listings Section -->
    <?php if (!empty($popularListings)): ?>
    <div class="mb-5">
        <h2 class="section-title">Popular Listings</h2>
        <div class="swiper popular-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($popularListings as $listing): ?>
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <img src="<?php echo !empty($listing['main_image']) ? htmlspecialchars($listing['main_image']) : 'assets/images/no-image.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($listing['location_name']); ?></p>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Featured Listings Section -->
    <?php if (!empty($featuredListings)): ?>
    <div class="mb-5">
        <h2 class="section-title">Featured Listings</h2>
        <div class="swiper featured-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($featuredListings as $listing): ?>
                    <div class="swiper-slide">
                        <div class="card h-100">
                            <img src="<?php echo !empty($listing['main_image']) ? htmlspecialchars($listing['main_image']) : 'assets/images/no-image.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($listing['location_name']); ?></p>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Listings Section -->
    <?php if (!empty($allListings)): ?>
    <div>
        <h2 class="section-title">All Listings</h2>
        <div class="row g-4">
            <?php foreach ($allListings as $listing): ?>
                <div class="col-6 col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo !empty($listing['main_image']) ? htmlspecialchars($listing['main_image']) : 'assets/images/no-image.jpg'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($listing['location_name']); ?></p>
                            <a href="listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
/* Search form styles */
.search-form {
    background-color: rgba(30, 30, 30, 0.8);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.location-search-container {
    position: relative;
}

.location-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #1e1e1e;
    border: 1px solid #333;
    border-radius: 0 0 5px 5px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.location-item {
    padding: 8px 15px;
    cursor: pointer;
    color: #fff;
}

.location-item:hover {
    background-color: #333;
}

.section-title {
    color: #fff;
    margin-bottom: 2rem;
    font-size: 2rem;
}

.card {
    background-color: #1e1e1e;
    border: 1px solid #333;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-img-top {
    height: 200px;
    object-fit: cover;
}

.card-title {
    color: #fff;
    font-size: 1.2rem;
}

.card-text {
    color: #aaa;
}

/* Swiper customization for mobile */
@media (max-width: 767px) {
    .swiper-slide {
        width: 50% !important; /* 2 slides per view on mobile */
    }
}
</style>

<!-- Swiper JS initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all Swipers
    const swiperOptions = {
        slidesPerView: 4,
        spaceBetween: 20,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            // Mobile devices
            320: {
                slidesPerView: 2,
                spaceBetween: 10
            },
            // Tablets
            768: {
                slidesPerView: 3,
                spaceBetween: 15
            },
            // Desktop
            1024: {
                slidesPerView: 4,
                spaceBetween: 20
            }
        }
    };
    
    new Swiper('.special-swiper', swiperOptions);
    new Swiper('.popular-swiper', swiperOptions);
    new Swiper('.featured-swiper', swiperOptions);
    
    // Location search functionality
    const locationSearch = document.getElementById('location-search');
    const locationResults = document.getElementById('location-results');
    const locationId = document.getElementById('location-id');
    
    // All locations data
    const locations = <?php echo json_encode($locations); ?>;
    
    locationSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm.length < 2) {
            locationResults.style.display = 'none';
            return;
        }
        
        // Filter locations based on search term
        const filteredLocations = locations.filter(location => 
            location.name.toLowerCase().includes(searchTerm)
        );
        
        // Display results
        if (filteredLocations.length > 0) {
            locationResults.innerHTML = '';
            filteredLocations.forEach(location => {
                const div = document.createElement('div');
                div.className = 'location-item';
                div.textContent = location.name;
                div.addEventListener('click', function() {
                    locationSearch.value = location.name;
                    locationId.value = location.id;
                    locationResults.style.display = 'none';
                });
                locationResults.appendChild(div);
            });
            locationResults.style.display = 'block';
        } else {
            locationResults.style.display = 'none';
        }
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!locationSearch.contains(e.target) && !locationResults.contains(e.target)) {
            locationResults.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 