<?php if(isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
<!-- Özel header CSS'i -->
<style>
/* Navbar stilleri - sadece header için */
.navbar {
    padding: 0.8rem 0;
}

.navbar-brand {
    font-weight: 600;
    letter-spacing: 0.5px;
    padding: 0.5rem 1rem;
    margin-right: 2rem;
    position: relative;
    left: 0;
}

.nav-item {
    margin: 0 6px;
    transition: all 0.2s ease;
}

.nav-link {
    padding: 0.6rem 0.8rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.nav-link i {
    margin-right: 6px;
}

.right-nav {
    margin-left: auto;
}

.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.2);
}

/* Mobil görünüm için */
@media (max-width: 991px) {
    .navbar-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .navbar-nav {
        flex-direction: column;
        width: 100%;
        margin: 0.5rem 0;
    }

    .right-nav {
        margin-top: 1rem;
        width: 100%;
        display: flex;
        justify-content: flex-start;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nav-item {
        margin: 3px 0;
        width: 100%;
    }

    .nav-link {
        width: 100%;
        display: block;
        padding: 0.75rem 1rem;
    }
}
</style>

<nav class="navbar navbar-dark bg-gradient-to-r from-purple-800 to-indigo-700 shadow-md">
    <div class="container">
        <div class="d-flex w-100 align-items-center">
            <a class="navbar-brand" href="<?php echo url('/pages/dashboard.php'); ?>">UniTrackSIS</a>

            <div class="navbar-content d-flex w-100">
                <ul class="navbar-nav d-flex flex-row flex-wrap mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/dashboard.php'); ?>">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                    </li>

                    <?php if($_SESSION['role'] == ROLE_ADMIN): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/auth/users.php'); ?>">
                            <i class="fas fa-users mr-1"></i> Kullanıcılar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/department/departments.php'); ?>">
                            <i class="fas fa-building mr-1"></i> Bölümler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/term/terms.php'); ?>">
                            <i class="fas fa-calendar-alt mr-1"></i> Dönemler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/grade/admin_grade_scales.php'); ?>">
                            <i class="fas fa-chart-bar mr-1"></i> Not Ölçekleri
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == ROLE_ADMIN || $_SESSION['role'] == ROLE_STUDENT_AFFAIRS): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/students.php'); ?>">
                            <i class="fas fa-user-graduate mr-1"></i> Öğrenciler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/courses.php'); ?>">
                            <i class="fas fa-book mr-1"></i> Dersler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/course_schedule.php'); ?>">
                            <i class="fas fa-calendar mr-1"></i> Ders Programı
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/assign_courses.php'); ?>">
                            <i class="fas fa-tasks mr-1"></i> Ders Atama
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/my_courses.php'); ?>">
                            <i class="fas fa-book mr-1"></i> Derslerim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/my_students.php'); ?>">
                            <i class="fas fa-user-graduate mr-1"></i> Öğrencilerim
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/grade/course_grade_scales.php'); ?>">
                            <i class="fas fa-chart-bar mr-1"></i> Not Ölçekleri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/announcement/teacher_announcements.php'); ?>">
                            <i class="fas fa-bullhorn mr-1"></i> Duyurularım
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == ROLE_STUDENT): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/enrolled_courses.php'); ?>">
                            <i class="fas fa-book mr-1"></i> Aldığım Dersler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/transcript/transcript.php'); ?>">
                            <i class="fas fa-file-alt mr-1"></i> Transkript
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/course/course_schedule.php'); ?>">
                            <i class="fas fa-calendar mr-1"></i> Ders Programı
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/pages/announcement/announcements.php'); ?>">
                            <i class="fas fa-bullhorn mr-1"></i> Duyurular
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav right-nav d-flex flex-row">


                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo url('/pages/auth/logout.php'); ?>">
                            <i class="fas fa-sign-out-alt mr-1"></i> Çıkış
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>