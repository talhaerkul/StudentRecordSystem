<?php
// Include necessary files
require_once 'config/config.php';
require_once 'controllers/AuthController.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process login form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if(empty($email) || empty($password)) {
        $_SESSION['alert'] = "Lütfen e-posta ve şifrenizi giriniz.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Attempt login
        $auth = new AuthController();
        if($auth->login($email, $password)) {
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['alert'] = "Hatalı e-posta veya şifre.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow mt-5">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">Okan Üniversitesi Öğrenci Bilgi Sistemi</h4>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Okan Üniversitesi Logo" class="img-fluid mb-3" style="max-height: 100px;">
                    <h5>Giriş Yap</h5>
                </div>
                
                <form id="login" action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="E-posta adresinizi giriniz" required>
                        <small class="form-text text-muted">
                            Öğretmenler için: @okan.edu.tr<br>
                            Öğrenciler için: @stu.okan.edu.tr
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Şifrenizi giriniz" required>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Beni hatırla</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
