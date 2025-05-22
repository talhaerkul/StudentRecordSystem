<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Term.php';
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

// Initialize Term object
$term = new Term($db);

// Get all terms
$terms = $term->readAll();

// Sayfa başlığı
$page_title = 'Dönem Yönetimi';

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
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Dönem Yönetimi</h5>
            </div>
            <div class="card-body">
                <!-- Add new term button -->
                <div class="mb-4">
                    <a href="<?php echo url('/pages/term/add_term.php'); ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Yeni Dönem Ekle
                    </a>
                </div>

                <!-- Terms table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Dönem Adı</th>
                                <th>Başlangıç Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Durum</th>
                                <th>Ders Seçimi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $terms->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($row['end_date'])); ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo $row['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $term_obj = new Term($db);
                                    $term_obj->id = $row['id'];
                                    $term_obj->readOne();
                                    $is_selection_active = $term_obj->isCourseSelectionActive();
                                    ?>
                                    <span class="badge <?php echo $is_selection_active ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $is_selection_active ? 'Açık' : 'Kapalı'; ?>
                                    </span>
                                    <?php if ($row['is_course_selection_active']): ?>
                                    <small class="d-block text-muted">
                                        <?php echo $row['course_selection_start'] ? date('d.m.Y H:i', strtotime($row['course_selection_start'])) : ''; ?> - 
                                        <?php echo $row['course_selection_end'] ? date('d.m.Y H:i', strtotime($row['course_selection_end'])) : ''; ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url('/pages/term/edit_term.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="<?php echo url('/pages/term/delete_term.php?id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bu dönemi silmek istediğinizden emin misiniz?');">
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