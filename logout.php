<?php
session_start();

// सेशन डेटा क्लियर करें
$_SESSION = array();

// सेशन कुकी डिस्ट्रॉय करें
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// सेशन डिस्ट्रॉय करें
session_destroy();

// होम पेज पर रीडायरेक्ट करें
header('Location: index.php');
exit;
?> 