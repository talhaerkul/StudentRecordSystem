<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Term.php';
require_once '../../config/database.php';

// Require login
requireLogin();

// Check if user is admin
if ($_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$term = new Term($db);

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['term_id'])) {
    $term->id = $_POST['term_id'];
    
    if ($term->readOne()) {
        $term->is_course_selection_active = isset($_POST['is_course_selection_active']) ? 1 : 0;
        $term->course_selection_start = $_POST['course_selection_start'] ?? null;
        $term->course_selection_end = $_POST['course_selection_end'] ?? null;
        
        if ($term->update()) {
            $message = "Ders seçim dönemi ayarları başarıyla güncellendi.";
        } else {
            $error = "Ders seçim dönemi ayarları güncellenirken bir hata oluştu.";
        }
    } else {
        $error = "Dönem bulunamadı.";
    }
}

// Get selected term id
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : null;

// Get term details if a term is selected
$term_details = null;
if ($selected_term_id) {
    $term->id = $selected_term_id;
    if ($term->readOne()) {
        $term_details = [
            'id' => $term->id,
            'name' => $term->name,
            'start_date' => $term->start_date,
            'end_date' => $term->end_date,
            'status' => $term->status,
            'is_course_selection_active' => $term->is_course_selection_active,
            'course_selection_start' => $term->course_selection_start,
            'course_selection_end' => $term->course_selection_end
        ];
    }
}

// Get all terms
$all_terms = $term->readAll();

// Page title
$page_title = 'Ders Seçim Dönemi Ayarları';

// Get content
ob_start();
?>

<div class="row">
    <!-- Main content -->
    <div class="col-md-12">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Ders Seçim Dönemi Ayarları</h5>
            </div>
            <div class="card-body">
                <!-- Term selection -->
                <form method="GET" action="<?php echo url('/pages/course/course_selection_settings.php'); ?>" class="mb-4">
                    <div class="form-row align-items-center">
                        <div class="col-md-4">
                            <label for="term_id">Dönem Seçin:</label>
                            <select class="form-control" id="term_id" name="term_id" onchange="this.form.submit()">
                                <option value="">Dönem Seçin</option>
                                <?php while ($term_row = $all_terms->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $term_row['id']; ?>" <?php echo ($term_row['id'] == $selected_term_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($term_row['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($term_details): ?>
                <!-- Course selection settings form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo htmlspecialchars($term_details['name']); ?> Dönemi - Ders Seçim Ayarları
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo url('/pages/course/course_selection_settings.php'); ?>">
                            <input type="hidden" name="term_id" value="<?php echo $term_details['id']; ?>">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_course_selection_active" name="is_course_selection_active" <?php echo $term_details['is_course_selection_active'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_course_selection_active">Ders Seçim Dönemi Aktif</label>
                                </div>
                                <small class="form-text text-muted">Bu seçenek, ders seçim dönemini aktif veya pasif yapar.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="course_selection_start">Ders Seçim Başlangıç Tarihi:</label>
                                <input type="datetime-local" class="form-control" id="course_selection_start" name="course_selection_start" value="<?php echo $term_details['course_selection_start'] ? date('Y-m-d\TH:i', strtotime($term_details['course_selection_start'])) : ''; ?>">
                                <small class="form-text text-muted">Ders seçim döneminin başlayacağı tarih ve saat.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="course_selection_end">Ders Seçim Bitiş Tarihi:</label>
                                <input type="datetime-local" class="form-control" id="course_selection_end" name="course_selection_end" value="<?php echo $term_details['course_selection_end'] ? date('Y-m-d\TH:i', strtotime($term_details['course_selection_end'])) : ''; ?>">
                                <small class="form-text text-muted">Ders seçim döneminin biteceği tarih ve saat.</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <strong>Bilgi:</strong> Ders seçim dönemi aktif ve seçim tarihleri içindeyken, öğrenciler ders seçimi yapabilirler.
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($selected_term_id): ?>
                <div class="alert alert-warning">
                    Seçilen dönem bulunamadı.
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    Lütfen ayarları görmek ve düzenlemek için bir dönem seçin.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Get content
$content = ob_get_clean();

// Include layout
require_once '../../includes/layout.php';
?> 