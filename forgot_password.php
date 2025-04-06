<?php
// forgot_password.php
require_once 'config/config.php';
require_once 'controllers/AuthController.php';

// Şifre sıfırlama işlemi
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    if(empty($email)) {
        $_SESSION['alert'] = "Lütfen e-posta adresinizi giriniz.";
        $_SESSION['alert_type'] = "danger";
    } else {
        $auth = new AuthController();
        if($auth->resetPassword($email)) {
            $_SESSION['alert'] = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
            $_SESSION['alert_type'] = "success";
        } else {
            // Hata mesajı AuthController'da ayarlanıyor
        }
    }
}

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow mt-5">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">Şifremi Unuttum</h4>
            </div>
            <div class="card-body">
                <p class="text-center mb-4">
                    E-posta adresinizi girin, şifre sıfırlama bağlantısını göndereceğiz.
                </p>
                
                <form action="forgot_password.php" method="POST">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Şifre Sıfırlama Bağlantısı Gönder
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php">Giriş sayfasına dön</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?>