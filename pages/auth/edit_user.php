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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . url('/pages/auth/users.php'));
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

// Get user data
$user->id = $_GET['id'];
if (!$user->readOne()) {
    $_SESSION['alert'] = "Kullanıcı bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/auth/users.php'));
    exit();
}

// Get roles and departments for dropdowns
$roles = $role->readAll();
$departments = $department->readAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Update user
        $user->name = $_POST['name'];
        $user->surname = $_POST['surname'];
        $user->email = $_POST['email'];
        $user->role_id = $_POST['role_id'];
        $user->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $user->student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : null;
        $user->phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
        $user->status = $_POST['status'];

        // Update password if provided
        if (!empty($_POST['password'])) {
            $user->password = $_POST['password'];
        }

        if ($user->update()) {
            // If password was changed, make a separate call to update it
            if (!empty($_POST['password'])) {
                if ($user->changePassword()) {
                    error_log("Password successfully changed for user ID: " . $user->id);
                    $_SESSION['console_log'][] = "Password successfully changed for user ID: " . $user->id;
                } else {
                    error_log("ERROR: Password change failed for user ID: " . $user->id);
                    $_SESSION['console_log'][] = "ERROR: Password change failed for user ID: " . $user->id;
                }
            }
            
            $_SESSION['alert'] = "Kullanıcı başarıyla güncellendi.";
            $_SESSION['alert_type'] = "success";
            header('Location: ' . url('/pages/auth/users.php'));
            exit();
        } else {
            $_SESSION['alert'] = "Kullanıcı güncellenirken bir hata oluştu.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Set page title
$page_title = "Kullanıcı Düzenle";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit"></i> Kullanıcı Düzenle
                    </h5>
                    <a href="<?php echo url('/pages/auth/users.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Kullanıcı Listesine Dön
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

                    <form method="POST" id="editUserForm"
                        action="<?php echo url('/pages/auth/edit_user.php?id=' . $user->id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $user->id; ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Ad</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?php echo htmlspecialchars($user->name); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="surname">Soyad</label>
                                    <input type="text" class="form-control" id="surname" name="surname"
                                        value="<?php echo htmlspecialchars($user->surname); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">E-posta</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($user->email); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Şifre (Boş bırakılırsa değişmez)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role_id">Rol</label>
                                    <select class="form-control" id="role_id" name="role_id" required>
                                        <option value="">Rol Seçin</option>
                                        <?php while ($row = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>"
                                            <?php echo ($user->role_id == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department_id">Bölüm</label>
                                    <select class="form-control" id="department_id" name="department_id">
                                        <option value="">Bölüm Seçin</option>
                                        <?php while ($row = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>"
                                            <?php echo ($user->department_id == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">Öğrenci No</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id"
                                        value="<?php echo htmlspecialchars($user->student_id ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telefon</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($user->phone ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Durum</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($user->status == 'active') ? 'selected' : ''; ?>>
                                    Aktif</option>
                                <option value="inactive" <?php echo ($user->status == 'inactive') ? 'selected' : ''; ?>>
                                    Pasif</option>
                            </select>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo url('/pages/auth/users.php'); ?>" class="btn btn-secondary">İptal</a>
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

// Add console log JavaScript if needed
if (isset($_SESSION['console_log']) && !empty($_SESSION['console_log'])) {
    $content .= "<script>\n";
    foreach ($_SESSION['console_log'] as $log) {
        $content .= "console.log(" . json_encode($log) . ");\n";
    }
    $content .= "</script>\n";
    unset($_SESSION['console_log']);
}

// Layout'u dahil et
require_once '../../includes/layout.php';
?>