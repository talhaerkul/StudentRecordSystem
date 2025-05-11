<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../config/database.php';

// Require login
requireLogin();

// Check if user is admin
if ($_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Course object
$course = new Course($db);

// Get filter parameters
$show_only_active = isset($_GET['active']) && $_GET['active'] == '1' ? true : false;

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Get paginated courses
if ($show_only_active) {
    $courses = $course->readActivePaginated($page, $records_per_page);
    $total_records = $course->countActive();
} else {
    $courses = $course->readAllPaginated($page, $records_per_page);
    $total_records = $course->countAll();
}

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Sayfa başlığı
$page_title = 'Ders Yönetimi';

// İçerik oluştur
ob_start();
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
                    <img src="../../assets/avatar.png" alt="Kullanıcı Avatarı" class="img-fluid rounded-circle mb-2"
                        style="max-width: 100px;">
                    <h5><?php echo $_SESSION['name']; ?></h5>
                    <p class="text-muted"><?php echo $_SESSION['role_name']; ?></p>
                </div>

                <div class="list-group">
                    <a href="<?php echo url('/pages/auth/profile.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="<?php echo url('/pages/auth/change_password.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="<?php echo url('/pages/auth/logout.php'); ?>"
                        class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="col-md-9">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-book"></i> Ders Yönetimi</h5>
            </div>
            <div class="card-body">
                <!-- Add new course button -->
                <div class="mb-4 d-flex justify-content-between">
                    <a href="<?php echo url('/pages/course/add_course.php'); ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Yeni Ders Ekle
                    </a>
                    
                    <!-- Status filter -->
                    <div class="btn-group">
                        <a href="?active=0" class="btn btn-outline-primary <?php echo !$show_only_active ? 'active' : ''; ?>">
                            Tüm Dersler
                        </a>
                        <a href="?active=1" class="btn btn-outline-primary <?php echo $show_only_active ? 'active' : ''; ?>">
                            Sadece Aktif Dersler
                        </a>
                    </div>
                </div>

                <!-- Courses table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kod</th>
                                <th>Ders Adı</th>
                                <th>Kredi</th>
                                <th>Departman</th>
                                <th>Haftalık Saat</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $courses->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['code']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['credit']; ?></td>
                                <td><?php echo $row['department_name']; ?></td>
                                <td><?php echo $row['hours_per_week']; ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo $row['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url('/pages/course/edit_course.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="<?php echo url('/pages/course/delete_course.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bu dersi silmek istediğinizden emin misiniz?');">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfalama">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo $show_only_active ? '&active=1' : ''; ?>">İlk</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $show_only_active ? '&active=1' : ''; ?>">Önceki</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        // Display page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $show_only_active ? '&active=1' : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $show_only_active ? '&active=1' : ''; ?>">Sonraki</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $show_only_active ? '&active=1' : ''; ?>">Son</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="text-center text-muted mt-2">
                    Toplam <?php echo $total_records; ?> kayıt, <?php echo $total_pages; ?> sayfa
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>