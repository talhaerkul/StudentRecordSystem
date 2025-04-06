<?php
// Include necessary files
require_once 'config/config.php';
require_once 'includes/auth_check.php';
require_once 'models/User.php';
require_once 'models/Role.php';
require_once 'models/Scholarship.php';
require_once 'models/Department.php';
require_once 'config/database.php';

// Require login
requireLogin();

// Check if user is admin
if ($_SESSION['role'] != ROLE_ADMIN) {
    header('Location: dashboard.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize User, Role, and Department objects
$user = new User($db);
$role = new Role($db);
$scholarship = new Scholarship($db);
$department = new Department($db);

// Get all roles and departments for dropdowns
$roles = $role->readFilteredRoles();
$departments = $department->readAll();
$scholarships = $scholarship->readAll();

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Server-side validation
    if (empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['email']) || 
        empty($_POST['password']) || empty($_POST['role_id']) || empty($_POST['department_id']) || 
        empty($_POST['status'])) {
        
        $_SESSION['error_message'] = "Lütfen tüm zorunlu alanları doldurun.";
        header('Location: add_user.php');
        exit();
    }

    // Email validation
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Geçerli bir e-posta adresi giriniz.";
        header('Location: add_user.php');
        exit();
    }

    // Student email domain validation
    if ($_POST['role_id'] == ROLE_STUDENT && !str_ends_with($_POST['email'], '@stu.okan.edu.tr')) {
        $_SESSION['error_message'] = "Öğrenciler için e-posta adresi @stu.okan.edu.tr ile bitmelidir.";
        header('Location: add_user.php');
        exit();
    }

    // Kullanıcı bilgilerini atama
    $user->name = trim($_POST['name']);
    $user->surname = trim($_POST['surname']);
    $user->email = trim($_POST['email']);
    $user->password = trim($_POST['password']);
    $user->role_id = $_POST['role_id'];
    $user->department_id = $_POST['department_id'];
    $user->status = $_POST['status'];

    // Rol bazlı ek alan kontrolleri
    if ($user->role_id == ROLE_TEACHER) {
        if (empty($_POST['title']) || empty($_POST['specialization']) || empty($_POST['phone'])) {
            $_SESSION['error_message'] = "Öğretmen bilgileri eksik!";
            header('Location: add_user.php');
            exit();
        }
        $user->title = $_POST['title'];
        $user->specialization = $_POST['specialization'];
        $user->phone = $_POST['phone'];
    } elseif ($user->role_id == ROLE_STUDENT) {
        if (empty($_POST['student_id']) || empty($_POST['birthdate']) || empty($_POST['address']) || 
            empty($_POST['advisor_id']) || empty($_POST['entry_year'])) {
            $_SESSION['error_message'] = "Öğrenci bilgileri eksik!";
            header('Location: add_user.php');
            exit();
        }
        $user->student_id = $_POST['student_id'];
        $user->birthdate = $_POST['birthdate'];
        $user->address = $_POST['address'];
        $user->advisor_id = $_POST['advisor_id'];
        $user->scholarship_id = isset($_POST['scholarship_id']) ? $_POST['scholarship_id'] : null;
        $user->entry_year = $_POST['entry_year'];
    }

    // Kullanıcıyı kaydet
    if ($user->register()) {
        $_SESSION['success_message'] = "Kullanıcı başarıyla eklendi.";
        header('Location: users.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Kullanıcı eklenirken bir hata oluştu.";
        header('Location: add_user.php');
        exit();
    }
}


// Include header
require_once 'includes/header.php';
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
                    <img src="assets/img/avatar.png" alt="Kullanıcı Avatarı" class="img-fluid rounded-circle mb-2" style="max-width: 100px;">
                    <h5><?php echo $_SESSION['name']; ?></h5>
                    <p class="text-muted"><?php echo $_SESSION['role_name']; ?></p>
                </div>
                
                <div class="list-group">
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="change_password.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="col-md-9">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Yeni Kullanıcı Ekle</h5>
            </div>
            <div class="card-body">
                <!-- Success/Error messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Add user form -->
                <form id="addUserForm" action="add_user.php" method="POST" onsubmit="return validateForm('addUserForm')">
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="name">Ad</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="surname">Soyad</label>
                        <input type="text" class="form-control" id="surname" name="surname" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role_id">Rol</label>
                        <select class="form-control" id="role_id" name="role_id" required>
                            <option value="">Rol Seçin</option>
                            <?php while ($role_row = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $role_row['id']; ?>"><?php echo $role_row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Departman</label>
                        <select class="form-control" id="department_id" name="department_id" required>
                            <option value="">Departman Seçin</option>
                            <?php while ($dept_row = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $dept_row['id']; ?>"><?php echo $dept_row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Durum</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>

                    <!-- Teacher-specific fields -->
                    <div id="teacherFields" style="display: none;">
                        <div class="form-group">
                            <label for="title">Ünvan</label>
                            <input type="text" class="form-control" id="title" name="title">
                        </div>
                        <div class="form-group">
                            <label for="specialization">Uzmanlık Alanı</label>
                            <input type="text" class="form-control" id="specialization" name="specialization">
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>

                    <!-- Student-specific fields -->
                    <div id="studentFields" style="display: none;">
                        <div class="form-group">
                            <label for="student_id">Öğrenci Numarası</label>
                            <input type="text" class="form-control" id="student_id" name="student_id">
                        </div>
                        <div class="form-group">
                            <label for="birthdate">Doğum Tarihi</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate">
                        </div>
                        <div class="form-group">
                            <label for="address">Adres</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>
                        <div class="form-group">
                            <label for="advisor_id">Danışman</label>
                            <select class="form-control" id="advisor_id" name="advisor_id">
                                <option value="">Danışman Seçin</option>
                                <?php
                                $advisors = $user->readAll();
                                while ($advisor_row = $advisors->fetch(PDO::FETCH_ASSOC)):
                                    if ($advisor_row['role_id'] == ROLE_TEACHER):
                                ?>
                                    <option value="<?php echo $advisor_row['id']; ?>"><?php echo $advisor_row['name'] . ' ' . $advisor_row['surname']; ?></option>
                                <?php endif; endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="scholarship_id">Burs</label>
                            <select class="form-control" id="scholarship_id" name="scholarship_id">
                                <option value="">Burs Seçin</option>
                                <?php
                                while ($scholarship_row = $scholarships->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <option value="<?php echo $scholarship_row['id']; ?>"><?php echo $scholarship_row['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="entry_year">Giriş Yılı</label>
                            <input type="number" class="form-control" id="entry_year" name="entry_year">
                        </div>
                    </div>

                    <!-- Submit button -->
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
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