<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process login form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;
        
        // Validate inputs
        if(empty($email) || empty($password)) {
            $_SESSION['alert'] = "Lütfen e-posta ve şifrenizi giriniz.";
            $_SESSION['alert_type'] = "danger";
        } else {
            // Attempt login
            $auth = new AuthController();
            if($auth->login($email, $password, $remember)) {
                header("Location: " . url('/pages/dashboard.php'));
                exit;
            } else {
                $_SESSION['alert'] = "Hatalı e-posta veya şifre.";
                $_SESSION['alert_type'] = "danger";
            }
        }
    }
}

// Sayfa başlığı
$page_title = 'Giriş Yap';

// Login sayfası için özel CSS
$page_specific_css = '
.login-page {
    background-color: #f8f9fa;
    padding-top: 2rem;
    padding-bottom: 2rem;
}
';

// İçerik oluştur
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-lg mt-5 rounded-xl overflow-hidden">
            <div class="card-header bg-gradient-to-r from-purple-700 to-indigo-600 text-white text-center py-3">
                <h4 class="mb-0 font-bold">UniTrackSIS Öğrenci Bilgi Sistemi</h4>
            </div>
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="../../assets/logo.png" alt="Logo"
                        class="img-fluid mb-3 mx-auto transform hover:scale-105 transition-transform duration-300"
                        style="max-height: 100px;">
                    <h5 class="text-xl font-semibold text-gray-800">Giriş Yap</h5>
                </div>

                <form id="login" action="<?php echo url('/pages/auth/login.php'); ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label for="email" class="flex items-center text-gray-700 mb-1"><i
                                class="fas fa-envelope mr-1 text-indigo-600"></i> E-posta</label>
                        <input type="email" class="form-control focus:ring-2 focus:ring-indigo-500" id="email"
                            name="email" placeholder="E-posta adresinizi giriniz" required>
                        <small class="form-text text-gray-600 mt-1 text-sm">
                            Öğretmenler için: @uni.edu.tr<br>
                            Öğrenciler için: @stu.uni.edu.tr
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="password" class="flex items-center text-gray-700 mb-1"><i
                                class="fas fa-lock mr-1 text-indigo-600"></i> Şifre</label>
                        <input type="password" class="form-control focus:ring-2 focus:ring-indigo-500" id="password"
                            name="password" placeholder="Şifrenizi giriniz" required>
                    </div>

                    <button type="submit"
                        class="btn btn-primary btn-block shadow-md hover:shadow-lg transition-shadow duration-300">
                        <i class="fas fa-sign-in-alt mr-1"></i> Giriş Yap
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?php echo url('/pages/auth/forgot_password.php'); ?>"
                        class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors duration-300">Şifremi
                        Unuttum</a>
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