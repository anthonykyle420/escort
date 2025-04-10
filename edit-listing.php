<?php
// सेशन स्टार्ट
session_start();

// कॉन्फिग और डेटाबेस कनेक्शन
require_once 'includes/config.php';

// चेक करें कि यूजर लॉगिन है या नहीं
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=edit-listing-new.php');
    exit;
}

// लिस्टिंग आईडी प्राप्त करें
$listingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// रीडायरेक्ट करें
header("Location: edit-listing-new.php?id=" . $listingId);
exit;
?> 