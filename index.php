<?php
// Include config file
require_once 'config/config.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // If logged in, redirect to dashboard
    header("Location: " . url('/pages/dashboard.php'));
    exit;
} else {
    // If not logged in, redirect to login page
    header("Location: " . url('/pages/auth/login.php'));
    exit;
}
?>