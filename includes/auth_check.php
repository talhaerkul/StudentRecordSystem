<?php
require_once 'config/config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['alert'] = 'Lütfen önce giriş yapınız.';
        $_SESSION['alert_type'] = 'warning';
        header('Location: login.php');
        exit;
    }
}

// Check if user has required role
function requireRole($requiredRoles) {
    requireLogin();
    
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    if (!in_array($_SESSION['role'], $requiredRoles)) {
        $_SESSION['alert'] = 'Bu sayfaya erişim yetkiniz bulunmamaktadır.';
        $_SESSION['alert_type'] = 'danger';
        header('Location: dashboard.php');
        exit;
    }
}

// Check if user is admin
function requireAdmin() {
    requireRole(ROLE_ADMIN);
}

// Check if user is student affairs
function requireStudentAffairs() {
    requireRole([ROLE_ADMIN, ROLE_STUDENT_AFFAIRS]);
}

// Check if user is teacher
function requireTeacher() {
    requireRole(ROLE_TEACHER);
}

// Check if user is student
function requireStudent() {
    requireRole(ROLE_STUDENT);
}
?>

