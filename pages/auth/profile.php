<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Check if user is logged in
requireLogin();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$department = new Department($db);

// Set user ID from session
$user->id = $_SESSION['user_id'];

// Get user details
$user->readOne();

// Get all departments for dropdown
$departments = $department->readAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Handle profile update
        if (isset($_POST['update_profile'])) {
            $user->name = $_POST['name'];
            $user->surname = $_POST['surname'];
            $user->email = $_POST['email'];
            $user->phone = !empty($_POST['phone']) ? $_POST['phone'] : null;

            if ($user->update()) {
                // Update session variables
                $_SESSION['name'] = $user->name;
                $_SESSION['surname'] = $user->surname;
                $_SESSION['email'] = $user->email;
                
                $_SESSION['alert'] = "Profil bilgileriniz başarıyla güncellendi.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert'] = "Profil bilgileriniz güncellenirken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        }
        
        // Handle password change
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            if (!password_verify($current_password, $user->password)) {
                $_SESSION['alert'] = "Mevcut şifreniz doğru değil.";
                $_SESSION['alert_type'] = "danger";
            } else if ($new_password !== $confirm_password) {
                $_SESSION['alert'] = "Yeni şifreler eşleşmiyor.";
                $_SESSION['alert_type'] = "danger";
            } else if (strlen($new_password) < 6) {
                $_SESSION['alert'] = "Yeni şifre en az 6 karakter uzunluğunda olmalıdır.";
                $_SESSION['alert_type'] = "danger";
            } else {
                $user->password = $new_password;
                
                if ($user->changePassword()) {
                    $_SESSION['alert'] = "Şifreniz başarıyla değiştirildi.";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert'] = "Şifreniz değiştirilirken bir hata oluştu.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . url('/pages/auth/profile.php'));
        exit();
    }
}

// Set page title
$page_title = "Profil Bilgilerim";

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
                    <h5><?php echo $user->name . ' ' . $user->surname; ?></h5>
                    <p class="text-muted"><?php echo $user->role_name; ?></p>
                </div>

                <div class="list-group">
                    <a href="<?php echo url('/pages/auth/profile.php'); ?>"
                        class="list-group-item list-group-item-action active">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="<?php echo url('/pages/dashboard.php'); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Ana Sayfaya Dön
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
        <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['alert']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
        <?php endif; ?>

        <!-- Profile Information Card -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit"></i> Profil Bilgilerim</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo url('/pages/auth/profile.php'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="update_profile" value="1">

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
                                <label for="phone">Telefon</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($user->phone); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Rol</label>
                                <input type="text" class="form-control" id="role" name="role"
                                    value="<?php echo htmlspecialchars($user->role_name); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department">Bölüm</label>
                                <input type="text" class="form-control" id="department" name="department"
                                    value="<?php echo htmlspecialchars($user->department_name); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
                    <div class="form-group">
                        <label for="student_id">Öğrenci Numarası</label>
                        <input type="text" class="form-control" id="student_id" name="student_id"
                            value="<?php echo htmlspecialchars($user->student_id); ?>" readonly>
                    </div>
                    <?php endif; ?>

                    <div class="text-right">
                        <a href="<?php echo url('/pages/dashboard.php'); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> İptal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Bilgilerimi Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->

    </div>
</div>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>