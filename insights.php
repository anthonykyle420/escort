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

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// चेक करें कि लिस्टिंग इस यूजर की है
$stmt = $db->prepare("
    SELECT l.*, c.name as category_name, loc.name as location_name,
    (SELECT image_path FROM images WHERE listing_id = l.id AND is_main = 1 LIMIT 1) as main_image
    FROM listings l
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN locations loc ON l.location_id = loc.id
    WHERE l.id = ? AND l.user_id = ?
");
$stmt->execute([$listingId, $userId]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$listing) {
    header('Location: my-listings.php?error=invalid_listing');
    exit;
}

// पिछले 7 दिनों के व्यूज डेटा प्राप्त करें
$stmt = $db->prepare("
    SELECT DATE(viewed_at) as date, COUNT(*) as views
    FROM listing_views
    WHERE listing_id = ?
    AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(viewed_at)
    ORDER BY date ASC
");
$stmt->execute([$listingId]);
$viewsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// पिछले 7 दिनों के कॉन्टैक्ट्स डेटा प्राप्त करें
$stmt = $db->prepare("
    SELECT DATE(clicked_at) as date, COUNT(*) as contacts
    FROM contact_clicks
    WHERE listing_id = ?
    AND clicked_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(clicked_at)
    ORDER BY date ASC
");
$stmt->execute([$listingId]);
$contactsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// पिछले 30 दिनों के कुल व्यूज प्राप्त करें
$stmt = $db->prepare("
    SELECT COUNT(*) as total_views
    FROM listing_views
    WHERE listing_id = ?
    AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$listingId]);
$totalViews = $stmt->fetchColumn();

// पिछले 30 दिनों के कुल कॉन्टैक्ट्स प्राप्त करें
$stmt = $db->prepare("
    SELECT COUNT(*) as total_contacts
    FROM contact_clicks
    WHERE listing_id = ?
    AND clicked_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute([$listingId]);
$totalContacts = $stmt->fetchColumn();

// आज के व्यूज प्राप्त करें
$stmt = $db->prepare("
    SELECT COUNT(*) as today_views
    FROM listing_views
    WHERE listing_id = ?
    AND DATE(viewed_at) = CURDATE()
");
$stmt->execute([$listingId]);
$todayViews = $stmt->fetchColumn();

// आज के कॉन्टैक्ट्स प्राप्त करें
$stmt = $db->prepare("
    SELECT COUNT(*) as today_contacts
    FROM contact_clicks
    WHERE listing_id = ?
    AND DATE(clicked_at) = CURDATE()
");
$stmt->execute([$listingId]);
$todayContacts = $stmt->fetchColumn();

// पिछले 7 दिनों के लिए डेटा प्रीपेयर करें
$dates = [];
$views = [];
$contacts = [];

// पिछले 7 दिनों की तारीखें जनरेट करें
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));
    $views[$date] = 0;
    $contacts[$date] = 0;
}

// व्यूज डेटा को एरे में सेट करें
foreach ($viewsData as $data) {
    $views[$data['date']] = (int)$data['views'];
}

// कॉन्टैक्ट्स डेटा को एरे में सेट करें
foreach ($contactsData as $data) {
    $contacts[$data['date']] = (int)$data['contacts'];
}

// चार्ट के लिए डेटा प्रीपेयर करें
$viewsChartData = array_values($views);
$contactsChartData = array_values($contacts);

// पेज टाइटल सेट करें
$pageTitle = "Insights - " . htmlspecialchars($listing['title']);

// एक्स्ट्रा CSS जोड़ें
$extraCss = '<style>
    .insights-card {
        background-color: #1a1a1a;
        border-radius: 10px;
        border: 1px solid #333;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .insights-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    .insights-card-header {
        background: linear-gradient(135deg, #8e44ad, #3498db);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
    }
    
    .insights-stat {
        background-color: #2c2c2c;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        height: 100%;
    }
    
    .insights-stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        background: -webkit-linear-gradient(45deg, #3498db, #2ecc71);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .insights-stat-label {
        font-size: 1rem;
        color: #aaa;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        padding: 20px;
    }
</style>';

// हेडर इंक्लूड करें
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="text-white">
                <i class="fas fa-chart-line me-2"></i> Insights
            </h1>
            <p class="text-muted">
                Performance statistics for: <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="my-listings.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i> Back to My Listings
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="insights-stat">
                <div class="insights-stat-value"><?php echo number_format($totalViews); ?></div>
                <div class="insights-stat-label">Total Views (30 days)</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="insights-stat">
                <div class="insights-stat-value"><?php echo number_format($totalContacts); ?></div>
                <div class="insights-stat-label">Total Contacts (30 days)</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="insights-stat">
                <div class="insights-stat-value"><?php echo number_format($todayViews); ?></div>
                <div class="insights-stat-label">Today's Views</div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="insights-stat">
                <div class="insights-stat-value"><?php echo number_format($todayContacts); ?></div>
                <div class="insights-stat-label">Today's Contacts</div>
            </div>
        </div>
    </div>
    
    <div class="insights-card">
        <div class="insights-card-header">
            <i class="fas fa-chart-area me-2"></i> Performance Over Last 7 Days
        </div>
        <div class="chart-container">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="insights-card">
                <div class="insights-card-header">
                    <i class="fas fa-eye me-2"></i> Views Breakdown
                </div>
                <div class="chart-container">
                    <canvas id="viewsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="insights-card">
                <div class="insights-card-header">
                    <i class="fas fa-phone me-2"></i> Contacts Breakdown
                </div>
                <div class="chart-container">
                    <canvas id="contactsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js लाइब्रेरी -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // चार्ट के लिए डेटा
    const dates = <?php echo json_encode($dates); ?>;
    const viewsData = <?php echo json_encode($viewsChartData); ?>;
    const contactsData = <?php echo json_encode($contactsChartData); ?>;
    
    // परफॉर्मेंस चार्ट
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Views',
                    data: viewsData,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Contacts',
                    data: contactsData,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#fff'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                }
            }
        }
    });
    
    // व्यूज चार्ट
    const viewsCtx = document.getElementById('viewsChart').getContext('2d');
    const viewsChart = new Chart(viewsCtx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Views',
                    data: viewsData,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: '#3498db',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                }
            }
        }
    });
    
    // कॉन्टैक्ट्स चार्ट
    const contactsCtx = document.getElementById('contactsChart').getContext('2d');
    const contactsChart = new Chart(contactsCtx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Contacts',
                    data: contactsData,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: '#2ecc71',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#aaa'
                    }
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?> 