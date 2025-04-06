<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Okan ÖBS</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Ana Sayfa</a>
                    </li>
                    
                    <?php if($_SESSION['role'] == ROLE_ADMIN): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="users.php"><i class="fas fa-users"></i> Kullanıcılar</a>
                            <a class="dropdown-item" href="departments.php"><i class="fas fa-building"></i> Bölümler</a>
                            <a class="dropdown-item" href="terms.php"><i class="fas fa-calendar-alt"></i> Dönemler</a>
                            <a class="dropdown-item" href="announcements.php"><i class="fas fa-bullhorn"></i> Duyurular</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_ADMIN || $_SESSION['role'] == ROLE_STUDENT_AFFAIRS): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="studentAffairsDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-graduation-cap"></i> Öğrenci İşleri
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="students.php"><i class="fas fa-user-graduate"></i> Öğrenciler</a>
                            <a class="dropdown-item" href="courses.php"><i class="fas fa-book"></i> Dersler</a>
                            <a class="dropdown-item" href="course_schedule.php"><i class="fas fa-calendar"></i> Ders Programı</a>
                            <a class="dropdown-item" href="assign_courses.php"><i class="fas fa-tasks"></i> Ders Atama</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="teacherDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-chalkboard-teacher"></i> Öğretmen
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="my_courses.php"><i class="fas fa-book"></i> Derslerim</a>
                            <a class="dropdown-item" href="my_students.php"><i class="fas fa-user-graduate"></i> Öğrencilerim</a>
                            <a class="dropdown-item" href="grades.php"><i class="fas fa-clipboard-check"></i> Not Girişi</a>
                            <a class="dropdown-item" href="teacher_announcements.php"><i class="fas fa-bullhorn"></i> Duyurularım</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_STUDENT): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-user-graduate"></i> Öğrenci
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="enrolled_courses.php"><i class="fas fa-book"></i> Aldığım Dersler</a>
                            <a class="dropdown-item" href="transcript.php"><i class="fas fa-file-alt"></i> Transkript</a>
                            <a class="dropdown-item" href="course_schedule_view.php"><i class="fas fa-calendar"></i> Ders Programı</a>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="view_announcements.php"><i class="fas fa-bullhorn"></i> Duyurular</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['name']; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> Profil</a>
                            <a class="dropdown-item" href="change_password.php"><i class="fas fa-key"></i> Şifre Değiştir</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container mt-4">
        <?php if(isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show">
                <?php echo $_SESSION['alert']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php
            unset($_SESSION['alert']);
            unset($_SESSION['alert_type']);
            ?>
        <?php endif; ?>

