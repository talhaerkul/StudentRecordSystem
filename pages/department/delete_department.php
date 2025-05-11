<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "Geçersiz bölüm ID'si.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/department/departments.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Department object
$department = new Department($db);
$department->id = $_GET['id'];

// Check if department exists
if (!$department->readOne()) {
    $_SESSION['alert'] = "Bölüm bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/department/departments.php'));
    exit();
}

// Delete department
if ($department->delete()) {
    $_SESSION['alert'] = "Bölüm başarıyla silindi.";
    $_SESSION['alert_type'] = "success";
} else {
    $_SESSION['alert'] = "Bölüm silinirken bir hata oluştu. Bu bölüme dersler veya öğrenciler atanmış olabilir.";
    $_SESSION['alert_type'] = "danger";
}

// Redirect to departments page
header('Location: ' . url('/pages/department/departments.php'));
exit();
?> 