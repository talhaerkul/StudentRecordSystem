<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../controllers/AuthController.php';
require_once '../../models/Department.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Get departments for dropdown
$database = new Database();
$db = $database->getConnection();

$departmentObj = new Department($db);
$departments = $departmentObj->readAll();

// Process registration form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $auth = new AuthController();
    if($auth->register($_POST)) {
        $_SESSION['alert'] = "Kayıt işlemi başarıyla tamamlandı. Lütfen giriş yapınız.";
        $_SESSION['alert_type'] = "success";
        header("Location: " . url('/pages/auth/login.php'));
        exit;
    }
}

// Include header
// İçerik oluştur
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow mt-4 mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">UniTrackSIS Öğrenci Bilgi Sistemi</h4>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="../../assets/logo.png" alt="Logo" class="img-fluid mb-3" style="max-height: 100px;">
                    <h5>Yeni Hesap Oluştur</h5>
                </div>
                
                <form action="<?php echo url('/pages/auth/register.php'); ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name"><i class="fas fa-user"></i> Ad</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="surname"><i class="fas fa-user"></i> Soyad</label>
                                <input type="text" class="form-control" id="surname" name="surname" value="<?php echo $_POST['surname'] ?? ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        <small class="form-text text-muted">
                            Öğretmenler için: @uni.edu.tr<br>
                            Öğrenciler için: @stu.uni.edu.tr
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="role_id"><i class="fas fa-user-tag"></i> Kullanıcı Tipi</label>
                        <select class="form-control" id="role_id" name="role_id" required>
                            <option value="">Seçiniz</option>
                            <option value="<?php echo ROLE_STUDENT; ?>" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == ROLE_STUDENT) ? 'selected' : ''; ?>>Öğrenci</option>
                            <option value="<?php echo ROLE_TEACHER; ?>" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == ROLE_TEACHER) ? 'selected' : ''; ?>>Öğretmen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="department_id"><i class="fas fa-building"></i> Bölüm</label>
                        <select class="form-control" id="department_id" name="department_id" required>
                            <option value="">Seçiniz</option>
                            <?php while($department = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $department['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="student_id_group" style="display: none;">
                        <label for="student_id"><i class="fas fa-id-card"></i> Öğrenci Numarası</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $_POST['student_id'] ?? ''; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Şifreniz en az 6 karakter olmalıdır.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirm"><i class="fas fa-lock"></i> Şifre Tekrar</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            <a href="#" data-toggle="modal" data-target="#termsModal">Kullanım koşullarını</a> okudum ve kabul ediyorum
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Kayıt Ol
                    </button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Zaten hesabınız var mı? <a href="<?php echo url('/pages/auth/login.php'); ?>">Giriş Yap</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Kullanım Koşulları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5>UniTrackSIS Öğrenci Bilgi Sistemi Kullanım Koşulları</h5>
                <p>
                    Bu sistem, Üniversite'nin öğrenci ve öğretim elemanları için tasarlanmıştır. 
                    Sistemi kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız.
                </p>
                
                <h6>1. Genel Kurallar</h6>
                <p>
                    Öğrenci Bilgi Sistemi'ne erişim için size verilen kullanıcı adı ve şifre bilgilerini kimseyle paylaşmamanız gerekmektedir. 
                    Hesabınızdan yapılan her türlü işlemden siz sorumlusunuz.
                </p>
                
                <h6>2. Gizlilik</h6>
                <p>
                    Sistem üzerinde bulunan kişisel bilgileriniz gizli tutulacak ve üçüncü şahıslarla paylaşılmayacaktır. 
                    Ancak, hukuki bir zorunluluk durumunda yasal mercilerle paylaşılabilir.
                </p>
                
                <h6>3. Kullanım Amacı</h6>
                <p>
                    Sistemi sadece eğitim-öğretim amaçlı kullanmanız gerekmektedir. 
                    Kötüye kullanım durumunda hesabınız askıya alınabilir ve okul yönetmeliklerine göre disiplin işlemleri uygulanabilir.
                </p>
                
                <h6>4. Sorumluluk Reddi</h6>
                <p>
                    Sistem üzerinde oluşabilecek teknik arızalardan ve bunların sonuçlarından Üniversite sorumlu tutulamaz. 
                    Önemli işlemlerinizi sistem üzerinden yaparken gerekli önlemleri almanız tavsiye edilir.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide student ID field based on role selection
document.getElementById('role_id').addEventListener('change', function() {
    var studentIdGroup = document.getElementById('student_id_group');
    var studentIdInput = document.getElementById('student_id');
    
    if (this.value == <?php echo ROLE_STUDENT; ?>) {
        studentIdGroup.style.display = 'block';
        studentIdInput.required = true;
    } else {
        studentIdGroup.style.display = 'none';
        studentIdInput.required = false;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    var roleId = document.getElementById('role_id');
    if (roleId.value == <?php echo ROLE_STUDENT; ?>) {
        document.getElementById('student_id_group').style.display = 'block';
        document.getElementById('student_id').required = true;
    }
});
</script>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>

