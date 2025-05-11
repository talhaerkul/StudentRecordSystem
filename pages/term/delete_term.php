<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Term.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "Geçersiz dönem ID'si.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/term/terms.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Term object
$term = new Term($db);
$term->id = $_GET['id'];

// Check if term exists
if (!$term->readOne()) {
    $_SESSION['alert'] = "Dönem bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/term/terms.php'));
    exit();
}

// Delete term
if ($term->delete()) {
    $_SESSION['alert'] = "Dönem başarıyla silindi.";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert'] = "Dönem silinirken bir hata oluştu. Bu döneme dersler veya öğrenciler atanmış olabilir.";
    $_SESSION['alert_type'] = "danger";
}

// Redirect to terms page
header('Location: ' . url('/pages/term/terms.php'));
exit();
?> 