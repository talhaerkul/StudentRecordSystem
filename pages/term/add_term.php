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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$term = new Term($db);

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

        // Create term
        if ($term->create()) {
            $_SESSION['alert'] = "Dönem başarıyla eklendi.";
            $_SESSION['alert_type'] = "success";
            header('Location: ' . url('/pages/term/terms.php'));
            exit();
        } else {
            $_SESSION['alert'] = "Dönem eklenirken bir hata oluştu.";
            $_SESSION['alert_type'] = "danger";
        }
    }
}

// Set page title
$page_title = "Yeni Dönem Ekle";

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Yeni Dönem Ekle
                    </h5>
                    <a href="<?php echo url('/pages/term/terms.php'); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Dönem Listesine Dön
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo $_SESSION['alert']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
                    <?php endif; ?>

                    <form method="POST" id="addTermForm" action="<?php echo url('/pages/term/add_term.php'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="form-group">
                            <label for="name">Dönem Adı</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                placeholder="Örnek: 2023-2024 Güz Dönemi">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Durum</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active">Aktif</option>
                                <option value="inactive">Pasif</option>
                            </select>
                        </div>

                        <hr>
                        <h5 class="mb-3">Ders Seçim Dönemi Ayarları</h5>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_course_selection_active" 
                                    name="is_course_selection_active">
                                <label class="custom-control-label" for="is_course_selection_active">Ders Seçim Dönemi Aktif</label>
                            </div>
                            <small class="form-text text-muted">Bu seçenek, ders seçim dönemini aktif veya pasif yapar.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course_selection_start">Ders Seçim Başlangıç Tarihi ve Saati</label>
                                    <input type="datetime-local" class="form-control" id="course_selection_start" 
                                        name="course_selection_start">
                                    <small class="form-text text-muted">Ders seçim döneminin başlayacağı tarih ve saat.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="course_selection_end">Ders Seçim Bitiş Tarihi ve Saati</label>
                                    <input type="datetime-local" class="form-control" id="course_selection_end" 
                                        name="course_selection_end">
                                    <small class="form-text text-muted">Ders seçim döneminin biteceği tarih ve saat.</small>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <a href="<?php echo url('/pages/term/terms.php'); ?>" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Dönemi Ekle
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