<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
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

// Set user ID from session
$user->id = $_SESSION['user_id'];

// Get user details
$user->readOne();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // First fetch the current user data to get the stored password hash
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user->id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stored_hash = $userData['password'];
        
        $passwordValid = false;
        
        // Check if we're using hashed passwords or plain text
        if (password_verify($current_password, $stored_hash)) {
            // Password is hashed and valid
            $passwordValid = true;
        } elseif ($current_password === $stored_hash) {
            // Direct comparison for legacy plain text passwords
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            $_SESSION['alert'] = "Mevcut şifreniz doğru değil.";
            $_SESSION['alert_type'] = "danger";
        } else if ($new_password !== $confirm_password) {
            $_SESSION['alert'] = "Yeni şifreler eşleşmiyor.";
            $_SESSION['alert_type'] = "danger";
        } else if (strlen($new_password) < 6) {
            $_SESSION['alert'] = "Yeni şifre en az 6 karakter uzunluğunda olmalıdır.";
            $_SESSION['alert_type'] = "danger";
        } else {
            // Create password hash
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password directly using PDO for more control
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$password_hash, $user->id])) {
                $_SESSION['alert'] = "Şifreniz başarıyla değiştirildi.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert'] = "Şifreniz değiştirilirken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . url('/pages/auth/change_password.php'));
        exit();
    }
}

// Set page title
$page_title = "Şifre Değiştir";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
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
                        <a href="<?php echo url('/pages/auth/profile.php'); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-id-card"></i> Profil Bilgileri
                        </a>
                        <a href="<?php echo url('/pages/auth/change_password.php'); ?>" class="list-group-item list-group-item-action active">
                            <i class="fas fa-key"></i> Şifre Değiştir
                        </a>
                        <a href="<?php echo url('/pages/dashboard.php'); ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt"></i> Ana Sayfaya Dön
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
            <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alert']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
            <?php endif; ?>

            <!-- Change Password Card -->
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Şifre Değiştir</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo url('/pages/auth/change_password.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="current_password">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                required minlength="6">
                            <small class="form-text text-muted">Şifreniz en az 6 karakter uzunluğunda olmalıdır.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                required minlength="6">
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading">Güvenli Şifre Önerileri:</h5>
                            <ul>
                                <li>En az 8 karakter kullanın</li>
                                <li>Büyük ve küçük harfleri birlikte kullanın</li>
                                <li>Rakam ve özel karakterler (!, @, #, $ gibi) ekleyin</li>
                                <li>Tahmin edilebilir bilgiler (doğum tarihi, isim gibi) kullanmayın</li>
                            </ul>
                        </div>
                        
                        <div class="form-group text-right">
                            <a href="<?php echo url('/pages/auth/profile.php'); ?>" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Şifremi Değiştir
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