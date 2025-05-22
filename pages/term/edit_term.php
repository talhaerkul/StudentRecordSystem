<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Term.php';
require_once '../../config/database.php';

// Require admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . url('/pages/term/terms.php'));
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$term = new Term($db);

// Get term data
$term->id = $_GET['id'];
if (!$term->readOne()) {
    $_SESSION['alert'] = "Dönem bulunamadı.";
    $_SESSION['alert_type'] = "danger";
    header('Location: ' . url('/pages/term/terms.php'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = "Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.";
        $_SESSION['alert_type'] = "danger";
    } else {
        // Set term properties
        $term->name = $_POST['name'];
        $term->start_date = $_POST['start_date'];
        $term->end_date = $_POST['end_date'];
        $term->status = $_POST['status'];
        $term->is_course_selection_active = isset($_POST['is_course_selection_active']) ? 1 : 0;
        $term->course_selection_start = $_POST['course_selection_start'] ? $_POST['course_selection_start'] : null;
        $term->course_selection_end = $_POST['course_selection_end'] ? $_POST['course_selection_end'] : null;

        // Update term
        if ($term->update()) {
            $_SESSION['alert'] = "Dönem başarıyla güncellendi.";
            $_SESSION['alert_type'] = "success";
            header('Location: ' . url('/pages/term/terms.php'));
            exit();
        } else {
            $_SESSION['alert'] = "Dönem güncellenirken bir hata oluştu.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Convert datetime for input fields
$course_selection_start = $term->course_selection_start ? date('Y-m-d\TH:i', strtotime($term->course_selection_start)) : '';
$course_selection_end = $term->course_selection_end ? date('Y-m-d\TH:i', strtotime($term->course_selection_end)) : '';

// Set page title
$page_title = "Dönem Düzenle";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Dönem Düzenle: <?php echo htmlspecialchars($term->name); ?>
                    </h5>
                    <a href="<?php echo url('/pages/term/terms.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Dönem Listesine Dön
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

                    <form method="POST" id="editTermForm" action="<?php echo url('/pages/term/edit_term.php?id=' . $term->id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $term->id; ?>">

                        <div class="form-group">
                            <label for="name">Dönem Adı</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                value="<?php echo htmlspecialchars($term->name); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                        value="<?php echo date('Y-m-d', strtotime($term->start_date)); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                        value="<?php echo date('Y-m-d', strtotime($term->end_date)); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Durum</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($term->status == 'active') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="inactive" <?php echo ($term->status == 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>

                        <hr>
                        <h5 class="mb-3">Ders Seçim Dönemi Ayarları</h5>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_course_selection_active" 
                                    name="is_course_selection_active" <?php echo $term->is_course_selection_active ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="is_course_selection_active">Ders Seçim Dönemi Aktif</label>
                            </div>
                            <small class="form-text text-muted">Bu seçenek, ders seçim dönemini aktif veya pasif yapar.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course_selection_start">Ders Seçim Başlangıç Tarihi ve Saati</label>
                                    <input type="datetime-local" class="form-control" id="course_selection_start" 
                                        name="course_selection_start" value="<?php echo $course_selection_start; ?>">
                                    <small class="form-text text-muted">Ders seçim döneminin başlayacağı tarih ve saat.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course_selection_end">Ders Seçim Bitiş Tarihi ve Saati</label>
                                    <input type="datetime-local" class="form-control" id="course_selection_end" 
                                        name="course_selection_end" value="<?php echo $course_selection_end; ?>">
                                    <small class="form-text text-muted">Ders seçim döneminin biteceği tarih ve saat.</small>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo url('/pages/term/terms.php'); ?>" class="btn btn-secondary">İptal</a>
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