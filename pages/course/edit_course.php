<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . url('/pages/course/courses.php'));
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$course = new Course($db);
$department = new Department($db);

// Get course data
$course->id = $_GET['id'];
if (!$course->readOne()) {
    $_SESSION['alert'] = "Ders bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/course/courses.php'));
    exit();
}

// Get departments for dropdown
$departments = $department->readAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Set course properties
        $course->code = $_POST['code'];
        $course->name = $_POST['name'];
        $course->description = $_POST['description'];
        $course->credit = $_POST['credit'];
        $course->department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $course->hours_per_week = $_POST['hours_per_week'];
        $course->status = $_POST['status'];
        $course->year = !empty($_POST['year']) ? $_POST['year'] : 1;

        // Check if course code exists (for another course)
        if ($course->codeExists()) {
            $_SESSION['alert'] = "Bu ders kodu zaten başka bir ders tarafından kullanılıyor.";
            $_SESSION['alert_type'] = "danger";
        } else {
            // Update course
            if ($course->update()) {
                $_SESSION['alert'] = "Ders başarıyla güncellendi.";
                $_SESSION['alert_type'] = "success";
                header('Location: ' . url('/pages/course/courses.php'));
                exit();
            } else {
                $_SESSION['alert'] = "Ders güncellenirken bir hata oluştu.";
                $_SESSION['alert_type'] = "danger";
            }
        }
    }
}

// Set page title
$page_title = "Ders Düzenle";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Ders Düzenle: <?php echo htmlspecialchars($course->code); ?> - <?php echo htmlspecialchars($course->name); ?>
                    </h5>
                    <a href="<?php echo url('/pages/course/courses.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Ders Listesine Dön
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

                    <form method="POST" id="editCourseForm" action="<?php echo url('/pages/course/edit_course.php?id=' . $course->id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $course->id; ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Ders Kodu</label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($course->code); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Ders Adı</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($course->name); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Ders Açıklaması</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($course->description); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="credit">Kredi</label>
                                    <input type="number" class="form-control" id="credit" name="credit" min="1" max="10" value="<?php echo htmlspecialchars($course->credit); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hours_per_week">Haftalık Saat</label>
                                    <input type="number" class="form-control" id="hours_per_week" name="hours_per_week" min="1" max="20" value="<?php echo htmlspecialchars($course->hours_per_week); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="department_id">Bölüm</label>
                                    <select class="form-control" id="department_id" name="department_id" required>
                                        <option value="">Bölüm Seçin</option>
                                        <?php 
                                        // Reset the departments pointer
                                        $departments->execute();
                                        while ($row = $departments->fetch(PDO::FETCH_ASSOC)): 
                                        ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($course->department_id == $row['id']) ? 'selected' : ''; ?>>
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
                                    <label for="year">Yıl</label>
                                    <select class="form-control" id="year" name="year" required>
                                        <option value="1" <?php echo ($course->year == 1) ? 'selected' : ''; ?>>1. Yıl</option>
                                        <option value="2" <?php echo ($course->year == 2) ? 'selected' : ''; ?>>2. Yıl</option>
                                        <option value="3" <?php echo ($course->year == 3) ? 'selected' : ''; ?>>3. Yıl</option>
                                        <option value="4" <?php echo ($course->year == 4) ? 'selected' : ''; ?>>4. Yıl</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Durum</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?php echo ($course->status == 'active') ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="inactive" <?php echo ($course->status == 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo url('/pages/course/courses.php'); ?>" class="btn btn-secondary">İptal</a>
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