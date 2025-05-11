<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "Geçersiz ders ID'si.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/course/courses.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Course object
$course = new Course($db);
$course->id = $_GET['id'];

// Check if course exists
if (!$course->readOne()) {
    $_SESSION['alert'] = "Ders bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/course/courses.php'));
    exit();
}

// Delete course
if ($course->delete()) {
    $_SESSION['alert'] = "Ders başarıyla silindi.";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert'] = "Ders silinirken bir hata oluştu. Bu ders öğrencilere veya öğretmenlere atanmış olabilir.";
    $_SESSION['alert_type'] = "danger";
}

// Redirect to courses page
header('Location: ' . url('/pages/course/courses.php'));
exit();
?> 