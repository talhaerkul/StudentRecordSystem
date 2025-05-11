<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . url('/pages/department/departments.php'));
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$department = new Department($db);

// Get department data
$department->id = $_GET['id'];
if (!$department->readOne()) {
    $_SESSION['alert'] = "Bölüm bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/department/departments.php'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Set department properties
        $department->name = $_POST['name'];
        $department->code = $_POST['code'];
        $department->status = $_POST['status'];

        // Update department
        if ($department->update()) {
            $_SESSION['alert'] = "Bölüm başarıyla güncellendi.";
            $_SESSION['alert_type'] = "success";
            header('Location: ' . url('/pages/department/departments.php'));
            exit();
        } else {
            $_SESSION['alert'] = "Bölüm güncellenirken bir hata oluştu.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Set page title
$page_title = "Bölüm Düzenle";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Bölüm Düzenle: <?php echo htmlspecialchars($department->code); ?> - <?php echo htmlspecialchars($department->name); ?>
                    </h5>
                    <a href="<?php echo url('/pages/department/departments.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Bölüm Listesine Dön
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['alert']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
                    <?php endif; ?>

                    <form method="POST" id="editDepartmentForm" action="<?php echo url('/pages/department/edit_department.php?id=' . $department->id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $department->id; ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Bölüm Kodu</label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($department->code); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Bölüm Adı</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($department->name); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Durum</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($department->status == 'active') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="inactive" <?php echo ($department->status == 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo url('/pages/department/departments.php'); ?>" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Güncelle
                            </button>
                        </div>
                    </form>
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