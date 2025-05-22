<?php
// forgot_password.php
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../models/User.php';
require_once '../../config/database.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token oluşturma - yalnızca GET istekleri için
if ($_SERVER["REQUEST_METHOD"] == "GET" && !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database bağlantısı
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Adım kontrolü
$step = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1;

// Şifre sıfırlama işlemi
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token kontrolü - POST isteği için
    $csrf_valid = true;
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $csrf_valid = false;
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    }
    
    if ($csrf_valid) {
        // ADIM 1: E-posta doğrulama
        if($step == 1 && isset($_POST['email'])) {
            $email = $_POST['email'];
            
            if(empty($email)) {
                $_SESSION['alert'] = "Lütfen e-posta adresinizi giriniz.";
                $_SESSION['alert_type'] = "danger";
            } else {
                $user->email = $email;
                
                // E-posta var mı kontrol et
                if($user->emailExists()) {
                    // Kullanıcıya ait bilgileri session'a kaydet
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_user_id'] = $user->id;
                    
                    // Güvenlik sorusu adımına geç
                    $_SESSION['reset_step'] = 2;
                    $step = 2;
                } else {
                    $_SESSION['alert'] = "Bu e-posta adresi sistemde bulunamadı.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
        // ADIM 2: Güvenlik soruları
        else if($step == 2 && isset($_POST['security_answer'])) {
            $answer = strtolower(trim($_POST['security_answer']));
            
            // Demo amaçlı basit doğrulama (gerçek sistemde veritabanından kontrol edilmeli)
            $user->id = $_SESSION['reset_user_id'];
            $user->readOne();
            
            $correctAnswer = false;
            
            // Demo sistem için basit kontrol
            // Cevap: öğrenciler için doğum yılı, öğretmenler için mezun olduğu üniversite adı
            if($user->role_id == 4) { // Öğrenci
                $correctAnswer = ($answer == strtolower(date('Y', strtotime($user->birthdate))));
            } else if($user->role_id == 3) { // Öğretmen
                $correctAnswer = ($answer == "okan" || $answer == "istanbul" || $answer == "itu" || $answer == "odtü");
            } else {
                $correctAnswer = ($answer == "admin123"); // Yönetici için demo cevap
            }
            
            if($correctAnswer) {
                // Şimdi sıfırlama kodunu oluştur
                $reset_code = rand(100000, 999999);
                $_SESSION['reset_code'] = $reset_code;
                $_SESSION['reset_code_time'] = time();
                $_SESSION['reset_step'] = 3;
                $step = 3;
                
                // Gerçek sistemde bu kod e-posta veya SMS ile gönderilir
                // Burada demo amaçlı gösteriyoruz
                $_SESSION['alert'] = "Demo sistem: Sıfırlama kodunuz: " . $reset_code;
                $_SESSION['alert_type'] = "info";
            } else {
                $_SESSION['alert'] = "Güvenlik sorusuna verdiğiniz cevap yanlış.";
                $_SESSION['alert_type'] = "danger";
            }
        }
        // ADIM 3: Şifre sıfırlama kodu
        else if($step == 3 && isset($_POST['reset_code'])) {
            $entered_code = trim($_POST['reset_code']);
            $stored_code = $_SESSION['reset_code'];
            $code_time = $_SESSION['reset_code_time'];
            
            // Kodun süresi doldu mu? (5 dakika)
            if(time() - $code_time > 300) {
                $_SESSION['alert'] = "Sıfırlama kodunun süresi doldu. Lütfen tekrar deneyin.";
                $_SESSION['alert_type'] = "danger";
                // İşlemi baştan başlat
                unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_code'], $_SESSION['reset_code_time']);
                header("Location: " . url('/pages/auth/forgot_password.php'));
                exit;
            }
            
            if($entered_code == $stored_code) {
                // Kod doğru, şifre değiştirme ekranına geç
                $_SESSION['reset_step'] = 4;
                $step = 4;
            } else {
                $_SESSION['alert'] = "Girdiğiniz kod hatalı. Lütfen tekrar deneyin.";
                $_SESSION['alert_type'] = "danger";
            }
        }
        // ADIM 4: Yeni şifre
        else if($step == 4 && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if($new_password != $confirm_password) {
                $_SESSION['alert'] = "Şifreler eşleşmiyor.";
                $_SESSION['alert_type'] = "danger";
            } else if(strlen($new_password) < 6) {
                $_SESSION['alert'] = "Şifre en az 6 karakter uzunluğunda olmalıdır.";
                $_SESSION['alert_type'] = "danger";
            } else {
                // Şifreyi değiştir
                $user->id = $_SESSION['reset_user_id'];
                $user->password = $new_password;
                
                if($user->changePassword()) {
                    // Başarılı, tüm reset session değişkenlerini temizle
                    unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_code'], $_SESSION['reset_code_time']);
                    
                    $_SESSION['alert'] = "Şifreniz başarıyla değiştirildi. Yeni şifrenizle giriş yapabilirsiniz.";
                    $_SESSION['alert_type'] = "success";
                    
                    // Giriş sayfasına yönlendir
                    header("Location: " . url('/pages/auth/login.php'));
                    exit;
                } else {
                    $_SESSION['alert'] = "Şifre değiştirme sırasında bir hata oluştu.";
                    $_SESSION['alert_type'] = "danger";
                }
            }
        }
    }
}

// Sayfa başlığı
$page_title = 'Şifremi Unuttum';

// Özel CSS
$page_specific_css = '
.reset-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.reset-card:hover {
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    transform: translateY(-5px);
}

.reset-card .card-header {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    border: none;
    padding: 1.25rem;
}

.reset-step-icon {
    background-color: #fff;
    color: #6a11cb;
    border-radius: 50%;
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    font-size: 28px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.form-control {
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #6a11cb;
    box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5c0fb3 0%, #1e68e1 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #11cb6a 0%, #25fc94 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s;
}

.btn-success:hover {
    background: linear-gradient(135deg, #0fb35c 0%, #1ee16e 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(17, 203, 106, 0.3);
}

.progress {
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.progress-bar {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    transition: width 0.5s ease;
}

.login-link {
    display: inline-block;
    margin-top: 20px;
    color: #6a11cb;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.login-link:hover {
    color: #2575fc;
    text-decoration: none;
    transform: translateX(-5px);
}

.login-link i {
    margin-right: 5px;
    transition: all 0.3s;
}

.login-link:hover i {
    transform: translateX(-3px);
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.alert-info {
    background-color: #e8f4ff;
    color: #0066cc;
}

.alert-success {
    background-color: #e8fff0;
    color: #00994d;
}

.alert-danger {
    background-color: #fff0f0;
    color: #cc0000;
}

.code-input {
    font-size: 24px;
    letter-spacing: 5px;
    text-align: center;
    font-weight: bold;
}

.security-question {
    background-color: #f8f1ff;
    color: #6a11cb;
    border-left: 4px solid #6a11cb;
}

.step-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.step-subtitle {
    color: #666;
    margin-bottom: 25px;
}
';

// İçerik oluştur
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card reset-card mt-5 mb-4">
            <div class="card-header text-center">
                <div class="mb-3">
                    <img src="<?php echo url('/assets/logo.png'); ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 80px;">
                    <h4 class="mb-0">UniTrackSIS Şifre Sıfırlama</h4>
                </div>
                
                <div class="step-indicator d-flex justify-content-center">
                    <!-- Step 1: E-posta -->
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> mx-2">
                        <div class="reset-step-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>E-posta</div>
                    </div>
                    
                    <!-- Step 2: Güvenlik Sorusu -->
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> mx-2">
                        <div class="reset-step-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>Güvenlik</div>
                    </div>
                    
                    <!-- Step 3: Kod -->
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> mx-2">
                        <div class="reset-step-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div>Doğrulama</div>
                    </div>
                    
                    <!-- Step 4: Yeni Şifre -->
                    <div class="step <?php echo $step >= 4 ? 'active' : ''; ?> mx-2">
                        <div class="reset-step-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>Yeni Şifre</div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-5">
                <?php if(isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['alert']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['alert'], $_SESSION['alert_type']); endif; ?>
                
                <?php if($step == 1): ?>
                <!-- ADIM 1: E-posta doğrulama formu -->
                <form action="<?php echo url('/pages/auth/forgot_password.php'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope mr-1"></i> E-posta Adresiniz</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                            placeholder="Kayıtlı e-posta adresinizi giriniz">
                        <small class="form-text text-muted">
                            Sistemde kayıtlı e-posta adresinizi girerek şifre sıfırlama işlemine başlayabilirsiniz.
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo url('/pages/auth/login.php'); ?>" class="btn btn-link">
                            <i class="fas fa-arrow-left mr-1"></i> Giriş Sayfasına Dön
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Devam Et
                        </button>
                    </div>
                </form>
                <?php elseif($step == 2): ?>
                <!-- ADIM 2: Güvenlik sorusu formu -->
                <form action="<?php echo url('/pages/auth/forgot_password.php'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle mr-1"></i> <strong>Güvenlik Sorusu:</strong> 
                        <?php 
                        $user->id = $_SESSION['reset_user_id'];
                        $user->readOne();
                        if($user->role_id == 4) { // Öğrenci
                            echo "Doğum yılınız nedir?";
                        } else if($user->role_id == 3) { // Öğretmen
                            echo "Mezun olduğunuz üniversitenin adı nedir?";
                        } else {
                            echo "Güvenlik şifrenizi giriniz.";
                        }
                        ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="security_answer"><i class="fas fa-shield-alt mr-1"></i> Cevabınız</label>
                        <input type="text" class="form-control" id="security_answer" name="security_answer" required
                            placeholder="Cevabınızı giriniz">
                        <small class="form-text text-muted">
                            <strong>Demo not:</strong> 
                            <?php 
                            if($user->role_id == 4) { // Öğrenci
                                echo "Öğrenciler için cevap doğum yılınızdır, örn: 1998";
                            } else if($user->role_id == 3) { // Öğretmen
                                echo "Öğretmenler için kabul edilen cevaplar: 'okan', 'istanbul', 'itu', 'odtü'";
                            } else {
                                echo "Yönetici hesabı için cevap: 'admin123'";
                            }
                            ?>
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link" onclick="window.location.href='<?php echo url('/pages/auth/forgot_password.php'); ?>'">
                            <i class="fas fa-arrow-left mr-1"></i> Geri
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check mr-1"></i> Doğrula
                        </button>
                    </div>
                </form>
                <?php elseif($step == 3): ?>
                <!-- ADIM 3: Kod doğrulama formu -->
                <form action="<?php echo url('/pages/auth/forgot_password.php'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-bell mr-1"></i> <strong>Bilgi:</strong> 
                        Gerçek bir sistemde, sıfırlama kodu e-posta veya SMS ile gönderilir. Bu demo sistemde kod ekranda gösterilmektedir.
                        Kod 5 dakika içinde kullanılmalıdır.
                    </div>
                    
                    <div class="form-group">
                        <label for="reset_code"><i class="fas fa-key mr-1"></i> Sıfırlama Kodu</label>
                        <input type="text" class="form-control" id="reset_code" name="reset_code" required 
                            placeholder="6 haneli kodu giriniz">
                        <small class="form-text text-muted">
                            Size gönderilen 6 haneli sıfırlama kodunu giriniz.
                            <br>Kodu almadıysanız, tekrar gönderim için <a href="#" onclick="alert('Demo sistemde yeniden kod gönderilmez.')">tıklayınız</a>.
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link" onclick="window.location.href='<?php echo url('/pages/auth/forgot_password.php'); ?>'">
                            <i class="fas fa-arrow-left mr-1"></i> Geri
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chevron-right mr-1"></i> Devam Et
                        </button>
                    </div>
                </form>
                <?php elseif($step == 4): ?>
                <!-- ADIM 4: Yeni şifre formu -->
                <form action="<?php echo url('/pages/auth/forgot_password.php'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock mr-1"></i> Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required
                            minlength="6" placeholder="Yeni şifrenizi giriniz">
                        <small class="form-text text-muted">
                            Şifreniz en az 6 karakter uzunluğunda olmalıdır.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock mr-1"></i> Şifre Tekrar</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                            minlength="6" placeholder="Yeni şifrenizi tekrar giriniz">
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link" onclick="window.location.href='<?php echo url('/pages/auth/forgot_password.php'); ?>'">
                            <i class="fas fa-arrow-left mr-1"></i> Geri
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle mr-1"></i> Şifremi Sıfırla
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            
            <div class="card-footer text-center">
                <p class="text-muted mb-0">
                    <i class="fas fa-shield-alt mr-1"></i> 
                    Şifrenizi yöneticinizden de sıfırlamanız mümkündür.
                </p>
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