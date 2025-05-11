<?php
// Session configuration
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.name', 'OKAN_SESSID');
ini_set('session.cookie_lifetime', '0');
ini_set('session.gc_maxlifetime', '1800'); // 30 minutes

// Site configuration
define('SITE_NAME', 'Üniversite - Öğrenci Bilgi Sistemi');
define('SITE_URL', 'http://localhost/student_record_system');
define('BASE_PATH', '/student_record_system');

// URL helper function to ensure all URLs include the base path
function url($path) {
    // Make sure path starts with a slash
    if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }
    
    return BASE_PATH . $path;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_record_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// User roles
define('ROLE_ADMIN', 1);
define('ROLE_STUDENT_AFFAIRS', 2);
define('ROLE_TEACHER', 3);
define('ROLE_STUDENT', 4);

// Role names for display
$ROLE_NAMES = [
    ROLE_ADMIN => 'Admin',
    ROLE_STUDENT_AFFAIRS => 'Öğrenci İşleri',
    ROLE_TEACHER => 'Öğretmen',
    ROLE_STUDENT => 'Öğrenci'
];

// Email domains for different roles
define('TEACHER_EMAIL_DOMAIN', '@uni.edu.tr');
define('STUDENT_EMAIL_DOMAIN', '@stu.uni.edu.tr');

// Academic terms
define('CURRENT_TERM', '2023-2024 Bahar');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>