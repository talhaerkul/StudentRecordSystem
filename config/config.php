<?php
// Site configuration
define('SITE_NAME', 'Okan Üniversitesi - Öğrenci Bilgi Sistemi');
define('SITE_URL', 'http://localhost/student_record_system');

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
define('TEACHER_EMAIL_DOMAIN', '@okan.edu.tr');
define('STUDENT_EMAIL_DOMAIN', '@stu.okan.edu.tr');

// Academic terms
define('CURRENT_TERM', '2023-2024 Bahar');

// Session settings
session_start();
?>

