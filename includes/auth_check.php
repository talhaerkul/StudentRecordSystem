<?php
// Use __DIR__ to get the correct path for absolute includes
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Check if user is logged in
function isLoggedIn() {
    // Check session
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Check remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $auth = new AuthController();
        if ($auth->checkRememberToken($_COOKIE['remember_token'])) {
            return true;
        }
    }
    
    return false;
}

// Redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['alert'] = 'Lütfen önce giriş yapınız.';
        $_SESSION['alert_type'] = 'warning';
        
        // Get relative path to /pages/auth/login.php from current script
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        $root_dir = dirname($script_dir);
        $login_path = ($root_dir == '/' ? '' : $root_dir) . '/pages/auth/login.php';
        
        header("Location: $login_path");
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
        
        // Get relative path to /pages/dashboard.php from current script
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        $root_dir = dirname($script_dir);
        $dashboard_path = ($root_dir == '/' ? '' : $root_dir) . '/pages/dashboard.php';
        
        header("Location: $dashboard_path");
        exit;
    }
}

// Check if user is admin
function requireAdmin() {
    requireRole(ROLE_ADMIN);
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