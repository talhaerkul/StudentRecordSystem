<?php
// Include necessary files
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../models/Announcement.php';
require_once '../models/Term.php';
require_once '../config/database.php';

// Require login
requireLogin();

// Get announcements for user
$database = new Database();
$db = $database->getConnection();

$announcement = new Announcement($db);
$user_role = $_SESSION['role'];
$user_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

// Get announcements
$announcements = $announcement->readAll();
error_log("Dashboard - User Role: " . $user_role . ", Department: " . ($user_dept ? $user_dept : "NULL"));
error_log("Dashboard - Number of announcements: " . $announcements->rowCount());

// Get current term
$term = new Term($db);
$term->getCurrentTerm();

// Sayfa başlığı
$page_title = 'Ana Sayfa';

// Sayfa içeriğini oluştur
ob_start();
?>

<div class="row">
    <!-- Left sidebar -->
    <div class="col-md-3">
        <div class="card shadow-lg rounded-lg mb-4 overflow-hidden">
            <div class="card-header bg-gradient-to-r from-purple-700 to-indigo-600 text-white py-3">
                <h5 class="mb-0 font-semibold flex items-center"><i class="fas fa-user mr-2"></i> Kullanıcı Bilgileri
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="../assets/avatar.png" alt="Kullanıcı Avatarı"
                        class="img-fluid rounded-circle mb-2 mx-auto shadow-sm transform hover:scale-105 transition-transform duration-300"
                        style="max-width: 100px;">
                    <h5 class="font-semibold text-gray-800"><?php echo $_SESSION['name']; ?></h5>
                    <p class="text-gray-600 text-sm"><?php echo $_SESSION['role_name']; ?></p>
                </div>

                <div class="list-group rounded-md overflow-hidden shadow-sm">
                    <a href="<?php echo url('/pages/auth/profile.php'); ?>"
                        class="list-group-item list-group-item-action hover:bg-gray-100 transition-colors duration-200 flex items-center">
                        <i class="fas fa-id-card text-indigo-600 mr-2"></i> Profil Bilgileri
                    </a>
                    <a href="<?php echo url('/pages/auth/change_password.php'); ?>"
                        class="list-group-item list-group-item-action hover:bg-gray-100 transition-colors duration-200 flex items-center">
                        <i class="fas fa-key text-indigo-600 mr-2"></i> Şifre Değiştir
                    </a>
                    <a href="<?php echo url('/pages/auth/logout.php'); ?>"
                        class="list-group-item list-group-item-action text-red-600 hover:bg-red-50 transition-colors duration-200 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-lg rounded-lg overflow-hidden">
            <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-500 text-white py-3">
                <h5 class="mb-0 font-semibold flex items-center"><i class="fas fa-calendar-alt mr-2"></i> Akademik
                    Takvim</h5>
            </div>
            <div class="card-body">
                <h6 class="font-semibold text-gray-800">Aktif Dönem: <?php echo $term->name ?? 'Belirlenmedi'; ?></h6>

                <?php if(isset($term->start_date) && isset($term->end_date)): ?>
                <p class="text-gray-700">
                    <strong>Başlangıç:</strong> <?php echo date('d.m.Y', strtotime($term->start_date)); ?><br>
                    <strong>Bitiş:</strong> <?php echo date('d.m.Y', strtotime($term->end_date)); ?>
                </p>
                <?php endif; ?>

                <a href="academic_calendar.php"
                    class="btn btn-sm btn-outline-info hover:shadow-md transition-shadow duration-200">Detaylı
                    Takvim</a>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="col-md-9">
        <!-- Welcome message -->
        <div class="card shadow-lg rounded-lg mb-4 overflow-hidden border-t-4 border-indigo-500">
            <div class="card-body p-4">
                <h2 class="card-title text-2xl font-bold text-gray-800">Hoş Geldiniz, <?php echo $_SESSION['name']; ?>!
                </h2>
                <p class="card-text text-gray-700">
                    UniTrackSIS Öğrenci Bilgi Sistemi'ne giriş yaptınız.
                    Aşağıda sizin için hazırlanmış işlem menülerini bulabilirsiniz.
                </p>
            </div>
        </div>

        <!-- User-specific quick links -->
        <div class="card shadow-lg rounded-lg mb-4 overflow-hidden">
            <div class="card-header bg-gray-50 border-b border-gray-200 py-3">
                <h5 class="mb-0 font-semibold text-gray-800 flex items-center"><i
                        class="fas fa-th mr-2 text-indigo-600"></i> Hızlı Erişim</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <?php if($_SESSION['role'] == ROLE_ADMIN): ?>
                    <!-- Admin quick links -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-indigo-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-users fa-3x text-indigo-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Kullanıcı Yönetimi</h5>
                                <a href="<?php echo url('/pages/auth/users.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-purple-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-book fa-3x text-purple-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Yönetimi</h5>
                                <a href="<?php echo url('/pages/course/courses.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-violet-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-bullhorn fa-3x text-violet-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Duyuru Yönetimi</h5>
                                <a href="<?php echo url('/pages/announcement/announcements.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-teal-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-calendar-alt fa-3x text-teal-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Programı</h5>
                                <a href="<?php echo url('/pages/course/course_schedule.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-indigo-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-file-alt fa-3x text-indigo-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Transkript Yönetimi</h5>
                                <a href="<?php echo url('/pages/transcript/admin_transcripts.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <!-- Yeni eklenen admin hızlı erişim linkleri -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-blue-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-building fa-3x text-blue-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Bölümler</h5>
                                <a href="<?php echo url('/pages/department/departments.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-green-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-calendar-alt fa-3x text-green-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Dönemler</h5>
                                <a href="<?php echo url('/pages/term/terms.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-amber-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-cog fa-3x text-amber-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Seçim Ayarları</h5>
                                <a href="<?php echo url('/pages/course/course_selection_settings.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-red-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-chart-bar fa-3x text-red-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Not Ölçekleri</h5>
                                <a href="<?php echo url('/pages/grade/admin_grade_scales.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-cyan-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-user-graduate fa-3x text-cyan-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Öğrenciler</h5>
                                <a href="<?php echo url('/pages/students.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-pink-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-tasks fa-3x text-pink-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Atama</h5>
                                <a href="<?php echo url('/pages/course/assign_courses.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>



                    <?php if($_SESSION['role'] == ROLE_STUDENT || $_SESSION['role'] == ROLE_TEACHER): ?>
                    <!-- Common quick links for students and teachers -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-purple-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-calendar fa-3x text-purple-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Programı</h5>
                                <a href="<?php echo url('/pages/course/course_schedule.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <!-- Teacher quick links -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-indigo-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-book fa-3x text-indigo-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Derslerim</h5>
                                <a href="<?php echo url('/pages/course/my_courses.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-violet-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-bullhorn fa-3x text-violet-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Duyurularım</h5>
                                <a href="<?php echo url('/pages/announcement/teacher_announcements.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-teal-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-file-alt fa-3x text-teal-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Öğrenci Transkriptleri</h5>
                                <a href="<?php echo url('/pages/transcript/teacher_transcripts.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-amber-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-chart-bar fa-3x text-amber-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Not Ölçekleri</h5>
                                <a href="<?php echo url('/pages/grade/course_grade_scales.php'); ?>"
                                    class="btn btn-sm btn-warning mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <!-- Yeni eklenen öğretmen hızlı erişim linkleri -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-blue-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-user-graduate fa-3x text-blue-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Öğrencilerim</h5>
                                <a href="<?php echo url('/pages/course/my_students.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-green-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-check-square fa-3x text-green-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Seçim Onayları</h5>
                                <a href="<?php echo url('/pages/course/approve_course_requests.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == ROLE_STUDENT): ?>
                    <!-- Student quick links -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-indigo-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-book fa-3x text-indigo-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Aldığım Dersler</h5>
                                <a href="<?php echo url('/pages/course/enrolled_courses.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-purple-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-file-alt fa-3x text-purple-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Transkript</h5>
                                <a href="<?php echo url('/pages/transcript/transcript.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>


                    <!-- Yeni eklenen öğrenci hızlı erişim linkleri -->
                    <div class="col-md-4 mb-3">
                        <div
                            class="card h-100 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg overflow-hidden border-t-2 border-green-500">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-plus-circle fa-3x text-green-600 mb-3"></i>
                                <h5 class="font-semibold text-gray-800">Ders Seçimi</h5>
                                <a href="<?php echo url('/pages/course/select_courses.php'); ?>"
                                    class="btn btn-sm btn-info mt-3 shadow-sm hover:shadow-md transition-all duration-200">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="card shadow-lg rounded-lg overflow-hidden">
            <div class="card-header bg-gray-50 border-b border-gray-200 py-3">
                <h5 class="mb-0 font-semibold text-gray-800 flex items-center"><i
                        class="fas fa-bullhorn mr-2 text-indigo-600"></i> Duyurular</h5>
            </div>
            <div class="card-body p-4">
                <?php if($announcements->rowCount() > 0): ?>
                <?php while($row = $announcements->fetch(PDO::FETCH_ASSOC)): ?>
                <div
                    class="alert alert-info bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg mb-3 shadow-sm">
                    <h5 class="font-semibold"><?php echo $row['title']; ?></h5>
                    <p class="my-2">
                        <?php echo substr($row['content'], 0, 150); ?><?php echo (strlen($row['content']) > 150) ? '...' : ''; ?>
                    </p>
                    <small class="text-gray-600 flex items-center">
                        <i class="far fa-clock mr-1"></i>
                        <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?> -
                        <i class="far fa-user ml-2 mr-1"></i>
                        <?php echo $row['user_name'] . ' ' . $row['user_surname']; ?>
                    </small>
                    <div class="mt-2">
                        <a href="<?php echo url('/pages/announcement/view_announcement.php?id=' . $row['id']); ?>"
                            class="btn btn-sm btn-outline-info">Detaylar</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="alert alert-warning">
                    <p class="mb-0">Görüntülenecek duyuru bulunmamaktadır.</p>
                </div>
                <?php endif; ?>

                <div class="text-right mt-3">
                    <a href="<?php echo url('/pages/announcement/view_announcement.php'); ?>"
                        class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Tüm Duyuruları Görüntüle
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Oluşturulan içeriği al
$content = ob_get_clean();

// Ana layout dosyasını dahil et
require_once '../includes/layout.php';
?>