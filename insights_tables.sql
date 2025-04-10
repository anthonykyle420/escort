-- Insights टेबल्स

-- listing_views टेबल
CREATE TABLE IF NOT EXISTS `listing_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`),
  KEY `viewed_at` (`viewed_at`),
  CONSTRAINT `listing_views_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listing_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contact_clicks टेबल
CREATE TABLE IF NOT EXISTS `contact_clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `clicked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `contact_type` enum('phone','email','whatsapp','telegram','other') DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `user_id` (`user_id`),
  KEY `clicked_at` (`clicked_at`),
  CONSTRAINT `contact_clicks_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_clicks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- डेमो डेटा जोड़ें
-- लिस्टिंग ID 1 के लिए व्यूज डेटा
INSERT INTO `listing_views` (`listing_id`, `ip_address`, `viewed_at`)
VALUES 
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, '127.0.0.1', CURDATE()),
(1, '127.0.0.1', CURDATE()),
(1, '127.0.0.1', CURDATE());

-- लिस्टिंग ID 1 के लिए कॉन्टैक्ट क्लिक्स डेटा
INSERT INTO `contact_clicks` (`listing_id`, `ip_address`, `clicked_at`, `contact_type`)
VALUES 
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'phone'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'phone'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'whatsapp'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'phone'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'email'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'phone'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'whatsapp'),
(1, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'phone'),
(1, '127.0.0.1', CURDATE(), 'phone'),
(1, '127.0.0.1', CURDATE(), 'whatsapp');

-- लिस्टिंग ID 2 के लिए व्यूज डेटा
INSERT INTO `listing_views` (`listing_id`, `ip_address`, `viewed_at`)
VALUES 
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(2, '127.0.0.1', CURDATE()),
(2, '127.0.0.1', CURDATE()),
(2, '127.0.0.1', CURDATE());

-- लिस्टिंग ID 2 के लिए कॉन्टैक्ट क्लिक्स डेटा
INSERT INTO `contact_clicks` (`listing_id`, `ip_address`, `clicked_at`, `contact_type`)
VALUES 
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'phone'),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'whatsapp'),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'phone'),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'email'),
(2, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'phone'),
(2, '127.0.0.1', CURDATE(), 'whatsapp');

-- लिस्टिंग ID 3 के लिए व्यूज डेटा
INSERT INTO `listing_views` (`listing_id`, `ip_address`, `viewed_at`)
VALUES 
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(3, '127.0.0.1', CURDATE()),
(3, '127.0.0.1', CURDATE());

-- लिस्टिंग ID 3 के लिए कॉन्टैक्ट क्लिक्स डेटा
INSERT INTO `contact_clicks` (`listing_id`, `ip_address`, `clicked_at`, `contact_type`)
VALUES 
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'phone'),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'whatsapp'),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'phone'),
(3, '127.0.0.1', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'email'),
(3, '127.0.0.1', CURDATE(), 'phone'); 