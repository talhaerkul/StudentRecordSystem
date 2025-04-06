<?php
// Include necessary files
require_once 'config/config.php';
require_once 'includes/auth_check.php';
require_once 'models/Announcement.php';
require_once 'models/Term.php';
require_once 'config/database.php';

// Require login
requireLogin();

// Get announcements for user
$database = new Database();
$db = $database->getConnection();

$announcement = new Announcement($db);
$announcements = $announcement->readByUserRole($_SESSION['role'], $_SESSION['department_id']);

// Get current term
$term = new Term($db);
$term->getCurrentTerm();
// Include header
require_once 'includes/header.php';
?>

<div class="row">
    <!-- Left sidebar -->
    <div class="col-md-3">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Kullanıcı Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="assets/img/avatar.png" alt="Kullanıcı Avatarı" class="img-fluid rounded-circle mb-2" style="max-width: 100px;">
                    <h5><?php echo $_SESSION['name']; ?></h5>
                    <p class="text-muted"><?php echo $_SESSION['role_name']; ?></p>
                </div>
                
                <div class="list-group">
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="change_password.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Akademik Takvim</h5>
            </div>
            <div class="card-body">
                <h6>Aktif Dönem: <?php echo $term->name ?? 'Belirlenmedi'; ?></h6>
                
                <?php if(isset($term->start_date) && isset($term->end_date)): ?>
                <p>
                    <strong>Başlangıç:</strong> <?php echo date('d.m.Y', strtotime($term->start_date)); ?><br>
                    <strong>Bitiş:</strong> <?php echo date('d.m.Y', strtotime($term->end_date)); ?>
                </p>
                <?php endif; ?>
                
                <a href="academic_calendar.php" class="btn btn-sm btn-outline-info">Detaylı Takvim</a>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="col-md-9">
        <!-- Welcome message -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <h2 class="card-title">Hoş Geldiniz, <?php echo $_SESSION['name']; ?>!</h2>
                <p class="card-text">
                    Okan Üniversitesi Öğrenci Bilgi Sistemi'ne giriş yaptınız. 
                    Aşağıda sizin için hazırlanmış işlem menülerini bulabilirsiniz.
                </p>
            </div>
        </div>
        
        <!-- User-specific quick links -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-th"></i> Hızlı Erişim</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if($_SESSION['role'] == ROLE_ADMIN): ?>
                    <!-- Admin quick links -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-primary mb-2"></i>
                                <h5>Kullanıcı Yönetimi</h5>
                                <a href="users.php" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x text-success mb-2"></i>
                                <h5>Ders Yönetimi</h5>
                                <a href="courses.php" class="btn btn-sm btn-success mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-bullhorn fa-3x text-info mb-2"></i>
                                <h5>Duyuru Yönetimi</h5>
                                <a href="announcements.php" class="btn btn-sm btn-info mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_STUDENT_AFFAIRS): ?>
                    <!-- Student affairs quick links -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-graduate fa-3x text-primary mb-2"></i>
                                <h5>Öğrenci Yönetimi</h5>
                                <a href="students.php" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar fa-3x text-success mb-2"></i>
                                <h5>Ders Programı</h5>
                                <a href="course_schedule.php" class="btn btn-sm btn-success mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tasks fa-3x text-info mb-2"></i>
                                <h5>Ders Atama</h5>
                                <a href="assign_courses.php" class="btn btn-sm btn-info mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <!-- Teacher quick links -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x text-primary mb-2"></i>
                                <h5>Derslerim</h5>
                                <a href="my_courses.php" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clipboard-check fa-3x text-success mb-2"></i>
                                <h5>Not Girişi</h5>
                                <a href="grades.php" class="btn btn-sm btn-success mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-bullhorn fa-3x text-info mb-2"></i>
                                <h5>Duyurularım</h5>
                                <a href="teacher_announcements.php" class="btn btn-sm btn-info mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == ROLE_STUDENT): ?>
                    <!-- Student quick links -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x text-primary mb-2"></i>
                                <h5>Aldığım Dersler</h5>
                                <a href="enrolled_courses.php" class="btn btn-sm btn-primary mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-3x text-success mb-2"></i>
                                <h5>Transkript</h5>
                                <a href="transcript.php" class="btn btn-sm btn-success mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-info mb-2"></i>
                                <h5>Ders Programım</h5>
                                <a href="course_schedule_view.php" class="btn btn-sm btn-info mt-2">Görüntüle</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Announcements -->
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Duyurular</h5>
            </div>
            <div class="card-body">
                <?php if($announcements->rowCount() > 0): ?>
                    <?php while($row = $announcements->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="alert alert-info">
                            <h5><?php echo $row['title']; ?></h5>
                            <p><?php echo substr($row['content'], 0, 150); ?><?php echo (strlen($row['content']) > 150) ? '...' : ''; ?></p>
                            <small class="text-muted">
                                <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?> - 
                                <?php echo $row['user_name'] . ' ' . $row['user_surname']; ?>
                            </small>
                            <div class="mt-2">
                                <a href="view_announcement.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">Detaylar</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <p class="mb-0">Görüntülenecek duyuru bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
                
                <div class="text-right mt-3">
                    <a href="view_announcements.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Tüm Duyuruları Görüntüle
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
