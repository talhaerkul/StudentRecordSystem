<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Department.php';
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

// Initialize Department object
$department = new Department($db);

// Get all departments
$departments = $department->readAll();

// Sayfa başlığı
$page_title = 'Bölüm Yönetimi';

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
                    <a href="<?php echo url('/pages/auth/profile.php'); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="<?php echo url('/pages/auth/change_password.php'); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="<?php echo url('/pages/auth/logout.php'); ?>" class="list-group-item list-group-item-action text-danger">
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
                <h5 class="mb-0"><i class="fas fa-university"></i> Bölüm Yönetimi</h5>
            </div>
            <div class="card-body">
                <!-- Add new department button -->
                <div class="mb-4">
                    <a href="<?php echo url('/pages/department/add_department.php'); ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Yeni Bölüm Ekle
                    </a>
                </div>

                <!-- Departments table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kod</th>
                                <th>Bölüm Adı</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['code']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo $row['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo url('/pages/department/edit_department.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="<?php echo url('/pages/department/delete_department.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bu bölümü silmek istediğinizden emin misiniz?');">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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