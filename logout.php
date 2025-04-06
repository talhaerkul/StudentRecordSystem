<?php
// Include necessary files
require_once 'config/config.php';
require_once 'controllers/AuthController.php';

// Create AuthController instance
$auth = new AuthController();

// Logout user
$auth->logout();

// Redirect to login page
header("Location: login.php");
exit;
?>
