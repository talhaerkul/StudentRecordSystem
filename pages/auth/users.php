<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
require_once '../../models/Role.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$role = new Role($db);
$department = new Department($db);

// Get all users
$users = $user->readAll();
$roles = $role->readAll();
$departments = $department->readAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        if (isset($_POST['create'])) {
            // Create new user
            $user->name = $_POST['name'];
            $user->surname = $_POST['surname'];
            $user->email = $_POST['email'];
            $user->password = $_POST['password'];
            $user->role_id = $_POST['role_id'];
            $user->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $user->student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
            $user->phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
            $user->status = 'active';

            if ($user->register()) {
                $_SESSION['alert'] = "Kullanıcı başarıyla oluşturuldu.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert'] = "Kullanıcı oluşturulurken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        } elseif (isset($_POST['update'])) {
            // Update existing user
            $user->id = $_POST['id'];
            $user->name = $_POST['name'];
            $user->surname = $_POST['surname'];
            $user->email = $_POST['email'];
            $user->role_id = $_POST['role_id'];
            $user->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $user->student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
            $user->phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
            $user->status = $_POST['status'];

            if ($user->update()) {
                $_SESSION['alert'] = "Kullanıcı başarıyla güncellendi.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert'] = "Kullanıcı güncellenirken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        } elseif (isset($_POST['delete'])) {
            // Delete user
            $user->id = $_POST['id'];

            if ($user->delete()) {
                $_SESSION['alert'] = "Kullanıcı başarıyla silindi.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert'] = "Kullanıcı silinirken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        }
    }

    // Redirect to prevent form resubmission
    header('Location: ' . url('/pages/auth/users.php'));
    exit();
}

// Read one user for editing
if (isset($_GET['edit'])) {
    $user->id = $_GET['edit'];
    $user->readOne();
}

// Include header
// İçerik oluştur
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Kullanıcı Yönetimi</h5>
                    <a href="<?php echo url('/pages/auth/add_user.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Yeni Kullanıcı
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo $_SESSION['alert']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="usersTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Ad</th>
                                    <th>Soyad</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>Bölüm</th>
                                    <th>Öğrenci No</th>
                                    <th>Telefon</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $users->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['role_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'Belirtilmemiş'); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_id'] ?? 'Belirtilmemiş'); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'Belirtilmemiş'); ?></td>
                                    <td>
                                        <span
                                            class="badge badge-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo $row['status'] == 'active' ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo url('/pages/auth/edit_user.php?id=' . $row['id']); ?>"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <form method="POST" class="d-inline"
                                                action="<?php echo url('/pages/auth/users.php'); ?>">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        </div>
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
</div>

<?php // İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php'; ?>