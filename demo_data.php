<?php
// डेटाबेस कनेक्शन शामिल करें
$host = '127.0.0.1';
$port = '8889';
$dbname = 'ankit';
$username = 'root';
$password = 'root';

try {
    // PDO इंस्टेंस बनाएं
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    
    // एरर मोड सेट करें
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // डेटाबेस स्कीमा चेक करें
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "Tables in database: " . implode(", ", $tables) . "<br><br>";
    
    // कैटेगरीज अपडेट करें
    $categories = [
        ['name' => 'Male Escorts', 'slug' => 'male-escorts', 'icon' => 'fa-male'],
        ['name' => 'Female Escorts', 'slug' => 'female-escorts', 'icon' => 'fa-female'],
        ['name' => 'Couple Escorts', 'slug' => 'couple-escorts', 'icon' => 'fa-users'],
        ['name' => 'Shemale Escorts', 'slug' => 'shemale-escorts', 'icon' => 'fa-transgender']
    ];
    
    // कैटेगरीज टेबल के कॉलम्स चेक करें
    if (in_array('categories', $tables)) {
        $columns = [];
        $result = $db->query("SHOW COLUMNS FROM categories");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        echo "Columns in categories table: " . implode(", ", $columns) . "<br><br>";
        
        // पहले सभी कैटेगरीज को डिलीट करें
        $db->exec("DELETE FROM categories");
        
        // नई कैटेगरीज जोड़ें
        foreach ($categories as $category) {
            // चेक करें कि कैटेगरी पहले से मौजूद तो नहीं है
            $stmt = $db->prepare("SELECT * FROM categories WHERE name = :name");
            $stmt->execute(['name' => $category['name']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                // केवल वही कॉलम्स शामिल करें जो टेबल में मौजूद हैं
                $data = [];
                foreach ($category as $key => $value) {
                    if (in_array($key, $columns)) {
                        $data[$key] = $value;
                    }
                }
                
                $cols = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                
                $query = "INSERT INTO categories ($cols) VALUES ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($data);
                
                echo "Added category: " . $category['name'] . "<br>";
            } else {
                echo "Category already exists: " . $category['name'] . "<br>";
            }
        }
    }
    
    // लोकेशन्स टेबल के कॉलम्स चेक करें
    if (in_array('locations', $tables)) {
        $columns = [];
        $result = $db->query("SHOW COLUMNS FROM locations");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        echo "<br>Columns in locations table: " . implode(", ", $columns) . "<br><br>";
        
        // लोकेशन्स जोड़ें
        $locations = [
            ['name' => 'Delhi', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Mumbai', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Bangalore', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Kolkata', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Chennai', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Hyderabad', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Pune', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Ahmedabad', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Jaipur', 'country' => 'India', 'is_active' => 1],
            ['name' => 'Lucknow', 'country' => 'India', 'is_active' => 1]
        ];
        
        foreach ($locations as $location) {
            // चेक करें कि लोकेशन पहले से मौजूद तो नहीं है
            $stmt = $db->prepare("SELECT * FROM locations WHERE name = :name");
            $stmt->execute(['name' => $location['name']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                // केवल वही कॉलम्स शामिल करें जो टेबल में मौजूद हैं
                $data = [];
                foreach ($location as $key => $value) {
                    if (in_array($key, $columns)) {
                        $data[$key] = $value;
                    }
                }
                
                $cols = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                
                $query = "INSERT INTO locations ($cols) VALUES ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($data);
                
                echo "Added location: " . $location['name'] . "<br>";
            } else {
                echo "Location already exists: " . $location['name'] . "<br>";
            }
        }
    }
    
    // लिस्टिंग्स टेबल के कॉलम्स चेक करें
    if (in_array('listings', $tables)) {
        $columns = [];
        $result = $db->query("SHOW COLUMNS FROM listings");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        echo "<br>Columns in listings table: " . implode(", ", $columns) . "<br><br>";
        
        // लिस्टिंग्स जोड़ें
        $listings = [
            [
                'user_id' => 1,
                'title' => 'Premium Female Escort',
                'description' => 'High-class female escort service with professional and elegant companions.',
                'category_id' => 2, // Female Escorts
                'location_id' => 1, // Delhi
                'age' => 25,
                'height' => 165,
                'weight' => 55,
                'price' => 5000,
                'currency' => 'INR',
                'price_type' => 'hour',
                'sexuality' => 'Straight',
                'contact_number' => '9876543210',
                'country_code' => '+91',
                'is_verified' => 1,
                'is_active' => 1,
                'is_featured' => 1,
                'is_popular' => 1,
                'is_special' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'total_views' => 100,
                'today_views' => 10,
                'contact_clicks' => 20
            ],
            [
                'user_id' => 1,
                'title' => 'VIP Male Escort',
                'description' => 'Exclusive male escort services for discerning clients.',
                'category_id' => 1, // Male Escorts
                'location_id' => 2, // Mumbai
                'age' => 28,
                'height' => 180,
                'weight' => 75,
                'price' => 4000,
                'currency' => 'INR',
                'price_type' => 'hour',
                'sexuality' => 'Straight',
                'contact_number' => '9876543211',
                'country_code' => '+91',
                'is_verified' => 1,
                'is_active' => 1,
                'is_featured' => 1,
                'is_popular' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'total_views' => 80,
                'today_views' => 8,
                'contact_clicks' => 15
            ],
            [
                'user_id' => 1,
                'title' => 'Couple Escort Service',
                'description' => 'Professional couple escort service for unique experiences.',
                'category_id' => 3, // Couple Escorts
                'location_id' => 3, // Bangalore
                'age' => 26,
                'height' => 170,
                'weight' => 60,
                'price' => 8000,
                'currency' => 'INR',
                'price_type' => 'hour',
                'sexuality' => 'Bisexual',
                'contact_number' => '9876543212',
                'country_code' => '+91',
                'is_verified' => 1,
                'is_active' => 1,
                'is_featured' => 1,
                'is_special' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'total_views' => 120,
                'today_views' => 12,
                'contact_clicks' => 25
            ],
            [
                'user_id' => 1,
                'title' => 'Shemale Escort Service',
                'description' => 'Premium shemale escort service with beautiful companions.',
                'category_id' => 4, // Shemale Escorts
                'location_id' => 4, // Kolkata
                'age' => 24,
                'height' => 175,
                'weight' => 65,
                'price' => 6000,
                'currency' => 'INR',
                'price_type' => 'hour',
                'sexuality' => 'Bisexual',
                'contact_number' => '9876543213',
                'country_code' => '+91',
                'is_verified' => 1,
                'is_active' => 1,
                'is_popular' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'total_views' => 90,
                'today_views' => 9,
                'contact_clicks' => 18
            ]
        ];
        
        foreach ($listings as $listing) {
            // चेक करें कि लिस्टिंग पहले से मौजूद तो नहीं है
            $stmt = $db->prepare("SELECT * FROM listings WHERE title = :title");
            $stmt->execute(['title' => $listing['title']]);
            $existing = $stmt->fetch();
            
            if (!$existing) {
                // केवल वही कॉलम्स शामिल करें जो टेबल में मौजूद हैं
                $data = [];
                foreach ($listing as $key => $value) {
                    if (in_array($key, $columns)) {
                        $data[$key] = $value;
                    }
                }
                
                $cols = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                
                $query = "INSERT INTO listings ($cols) VALUES ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($data);
                
                $listing_id = $db->lastInsertId();
                echo "Added listing: " . $listing['title'] . " (ID: $listing_id)<br>";
            } else {
                echo "Listing already exists: " . $listing['title'] . "<br>";
            }
        }
    }
    
    echo "<br>Demo data added successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    if (isset($query)) {
        echo "<br>Query: " . $query;
    }
}
?> 