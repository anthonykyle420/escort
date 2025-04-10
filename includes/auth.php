<?php
require_once 'db.php';
require_once 'functions.php';

// यूजर रजिस्टर करें
function registerUser($username, $email, $password) {
    global $db;
    
    // चेक करें कि यूजरनेम या ईमेल पहले से मौजूद तो नहीं है
    $existingUser = $db->selectOne("SELECT * FROM users WHERE username = :username OR email = :email", 
                                  ['username' => $username, 'email' => $email]);
    
    if ($existingUser) {
        if ($existingUser['username'] == $username) {
            return ['success' => false, 'message' => 'Username already exists'];
        } else {
            return ['success' => false, 'message' => 'Email already exists'];
        }
    }
    
    // पासवर्ड हैश करें
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // यूजर इन्सर्ट करें
    $userId = $db->insert('users', [
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword
    ]);
    
    if ($userId) {
        return ['success' => true, 'user_id' => $userId];
    } else {
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

// यूजर लॉगिन करें
function loginUser($username, $password) {
    global $db;
    
    // यूजरनेम या ईमेल से यूजर ढूंढें
    $user = $db->selectOne("SELECT * FROM users WHERE username = :username OR email = :username", 
                          ['username' => $username]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // पासवर्ड वेरिफाई करें
    if (password_verify($password, $user['password'])) {
        // सेशन में यूजर डेटा सेट करें
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // लास्ट लॉगिन अपडेट करें
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        
        return ['success' => true, 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Invalid password'];
    }
}

// यूजर लॉगआउट करें
function logoutUser() {
    // सेशन डेटा हटाएं
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['is_admin']);
    
    // सेशन डिस्ट्रॉय करें
    session_destroy();
    
    return true;
}

// पासवर्ड रीसेट टोकन जनरेट करें
function generatePasswordResetToken($email) {
    global $db;
    
    // ईमेल से यूजर ढूंढें
    $user = $db->selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email not found'];
    }
    
    // रैंडम टोकन जनरेट करें
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // टोकन डेटाबेस में सेव करें (यहां हम users टेबल में ही सेव कर रहे हैं, आप अलग टेबल भी बना सकते हैं)
    $db->update('users', [
        'reset_token' => $token,
        'reset_expires' => $expires
    ], 'id = :id', ['id' => $user['id']]);
    
    return [
        'success' => true,
        'token' => $token,
        'email' => $email,
        'expires' => $expires
    ];
}

// पासवर्ड रीसेट करें
function resetPassword($token, $password) {
    global $db;
    
    // टोकन से यूजर ढूंढें
    $user = $db->selectOne("SELECT * FROM users WHERE reset_token = :token AND reset_expires > NOW()", 
                          ['token' => $token]);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid or expired token'];
    }
    
    // पासवर्ड हैश करें
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // पासवर्ड अपडेट करें और टोकन हटाएं
    $db->update('users', [
        'password' => $hashedPassword,
        'reset_token' => null,
        'reset_expires' => null
    ], 'id = :id', ['id' => $user['id']]);
    
    return ['success' => true];
}