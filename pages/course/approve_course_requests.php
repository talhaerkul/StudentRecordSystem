<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../models/User.php';
require_once '../../models/Term.php';
require_once '../../models/CourseRequest.php';
require_once '../../config/database.php';

// Require login
requireLogin();

// Check if user is teacher
if ($_SESSION['role'] != ROLE_TEACHER) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$course = new Course($db);
$user = new User($db);
$term = new Term($db);
$courseRequest = new CourseRequest($db);

// Get current term
$term->getCurrentTerm();
$current_term_id = $term->id ?? null;

// Get selected term (default to current)
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : $current_term_id;

// Process approval/rejection
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_request'])) {
        $courseRequest->id = $_POST['request_id'];
        $courseRequest->processed_by = $_SESSION['user_id'];
        
        if ($courseRequest->approve()) {
            $message = "Ders seçim talebi başarıyla onaylandı.";
        } else {
            $error = "Ders seçim talebi onaylanırken bir hata oluştu.";
        }
    } elseif (isset($_POST['reject_request'])) {
        $courseRequest->id = $_POST['request_id'];
        $courseRequest->processed_by = $_SESSION['user_id'];
        
        if ($courseRequest->reject()) {
            $message = "Ders seçim talebi reddedildi.";
        } else {
            $error = "Ders seçim talebi reddedilirken bir hata oluştu.";
        }
    }
}

// Get pending course requests for the teacher's courses
$teacher_id = $_SESSION['user_id'];
$pending_requests = [];

if ($selected_term_id) {
    $stmt = $courseRequest->getTeacherPendingRequests($teacher_id, $selected_term_id);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pending_requests[] = $row;
    }
}

// Get all terms for filter
$all_terms = $term->readActive();

// Page title
$page_title = 'Ders Seçim Talepleri';

// Get content
ob_start();
?>

<div class="row">
    <!-- Main content -->
    <div class="col-md-12">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-check-square"></i> Ders Seçim Talepleri</h5>
            </div>
            <div class="card-body">
                <!-- Term filter -->
                <form method="GET" action="<?php echo url('/pages/course/approve_course_requests.php'); ?>" class="mb-4">
                    <div class="form-row align-items-center">
                        <div class="col-md-4">
                            <label for="term_id">Dönem:</label>
                            <select class="form-control" id="term_id" name="term_id" onchange="this.form.submit()">
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

                <!-- Pending course requests -->
                <h5 class="mb-3">Onay Bekleyen Ders Seçim Talepleri</h5>
                
                <?php if (empty($pending_requests)): ?>
                <div class="alert alert-info">
                    Bu dönem için onay bekleyen ders seçim talebi bulunmamaktadır.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Öğrenci Numarası</th>
                                <th>Öğrenci Adı</th>
                                <th>Ders Kodu</th>
                                <th>Ders Adı</th>
                                <th>Bölüm</th>
                                <th>Kredi</th>
                                <th>Talep Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($request['student_name'] . ' ' . $request['student_surname']); ?></td>
                                <td><?php echo htmlspecialchars($request['code']); ?></td>
                                <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['credit']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($request['requested_at'])); ?></td>
                                <td class="d-flex">
                                    <form method="POST" action="<?php echo url('/pages/course/approve_course_requests.php?term_id=' . $selected_term_id); ?>" class="mr-2">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="approve_request" class="btn btn-sm btn-success" onclick="return confirm('Bu ders seçim talebini onaylamak istediğinizden emin misiniz?');">
                                            <i class="fas fa-check"></i> Onayla
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo url('/pages/course/approve_course_requests.php?term_id=' . $selected_term_id); ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="reject_request" class="btn btn-sm btn-danger" onclick="return confirm('Bu ders seçim talebini reddetmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-times"></i> Reddet
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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