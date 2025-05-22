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

// Check if user is student
if ($_SESSION['role'] != ROLE_STUDENT) {
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

// Check if course selection is active for this term
$is_course_selection_active = false;
if ($selected_term_id) {
    $term->id = $selected_term_id;
    if ($term->readOne()) {
        $is_course_selection_active = $term->isCourseSelectionActive();
    }
}

// Process course selection form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id']) && $is_course_selection_active) {
    $courseRequest->student_id = $_SESSION['user_id'];
    $courseRequest->course_id = $_POST['course_id'];
    $courseRequest->term_id = $selected_term_id;
    
    // Check if student is already enrolled in this course
    if ($courseRequest->isStudentEnrolled()) {
        $error = "Bu derse zaten kayıtlısınız.";
    } else if ($courseRequest->requestExists()) {
        $error = "Bu ders için zaten bir talebiniz bulunmaktadır.";
    } else if ($courseRequest->create()) {
        $message = "Ders seçim talebiniz başarıyla oluşturuldu. Öğretmen onayı bekleniyor.";
    } else {
        $error = "Ders seçim talebi oluşturulurken bir hata oluştu.";
    }
}

// Delete course request if requested
if (isset($_GET['delete_request']) && $is_course_selection_active) {
    $courseRequest->id = $_GET['delete_request'];
    // Verify that this request belongs to the current student
    if ($courseRequest->readOne() && $courseRequest->student_id == $_SESSION['user_id']) {
        if ($courseRequest->delete()) {
            $message = "Ders seçim talebi başarıyla silindi.";
        } else {
            $error = "Ders seçim talebi silinirken bir hata oluştu.";
        }
    }
}

// Get student's course requests for this term
$student_id = $_SESSION['user_id'];
$course_requests = [];
if ($selected_term_id) {
    $stmt = $courseRequest->getStudentRequests($student_id, $selected_term_id);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $course_requests[] = $row;
    }
}

// Get available courses for this term (courses assigned to teachers)
$available_courses = [];
if ($selected_term_id) {
    // Get department ID of the student
    $user->id = $student_id;
    $user->readOne();
    $department_id = $user->department_id;
    
    $sql = "SELECT c.id, c.code, c.name, c.credit, c.description, c.hours_per_week, 
                   d.name as department_name, CONCAT(u.name, ' ', u.surname) as teacher_name
            FROM courses c
            JOIN teacher_courses tc ON c.id = tc.course_id
            JOIN users u ON tc.teacher_id = u.id
            JOIN terms t ON tc.term_id = t.id
            LEFT JOIN departments d ON c.department_id = d.id
            WHERE tc.term_id = ? AND c.status = 'active' AND t.status = 'active'";
    
    // Filter by department if student has one
    if ($department_id) {
        $sql .= " AND c.department_id = ?";
    }
    
    $sql .= " ORDER BY c.code";
    
    $stmt = $db->prepare($sql);
    
    if ($department_id) {
        $stmt->bindParam(1, $selected_term_id);
        $stmt->bindParam(2, $department_id);
    } else {
        $stmt->bindParam(1, $selected_term_id);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Check if student is already enrolled or has a pending request
        $courseRequest->student_id = $student_id;
        $courseRequest->course_id = $row['id'];
        $courseRequest->term_id = $selected_term_id;
        
        if (!$courseRequest->isStudentEnrolled() && !$courseRequest->requestExists()) {
            $available_courses[] = $row;
        }
    }
}

// Get all terms for filter
$all_terms = $term->readActive();

// Page title
$page_title = 'Ders Seçimi';

// Get content
ob_start();
?>

<div class="row">
    <!-- Main content -->
    <div class="col-md-12">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book"></i> Ders Seçimi</h5>
            </div>
            <div class="card-body">
                <!-- Term filter -->
                <form method="GET" action="<?php echo url('/pages/course/select_courses.php'); ?>" class="mb-4">
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

                <?php if (!$is_course_selection_active): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Ders seçim dönemi aktif değil. Ders seçimi yapamazsınız.
                </div>
                <?php endif; ?>

                <!-- Current course requests -->
                <h5 class="mb-3">Ders Seçim Talepleriniz</h5>
                
                <?php if (empty($course_requests)): ?>
                <div class="alert alert-info">
                    Bu dönem için ders seçim talebiniz bulunmamaktadır.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Ders Kodu</th>
                                <th>Ders Adı</th>
                                <th>Bölüm</th>
                                <th>Kredi</th>
                                <th>Haftalık Saat</th>
                                <th>Durum</th>
                                <th>Talep Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['code']); ?></td>
                                <td><?php echo htmlspecialchars($request['name']); ?></td>
                                <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['credit']); ?></td>
                                <td><?php echo htmlspecialchars($request['hours_per_week']); ?></td>
                                <td>
                                    <?php if ($request['status'] == 'pending'): ?>
                                    <span class="badge badge-warning">Beklemede</span>
                                    <?php elseif ($request['status'] == 'approved'): ?>
                                    <span class="badge badge-success">Onaylandı</span>
                                    <?php elseif ($request['status'] == 'rejected'): ?>
                                    <span class="badge badge-danger">Reddedildi</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <?php if ($request['status'] == 'pending' && $is_course_selection_active): ?>
                                    <a href="<?php echo url('/pages/course/select_courses.php?term_id=' . $selected_term_id . '&delete_request=' . $request['id']); ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Bu ders seçim talebini silmek istediğinizden emin misiniz?');">
                                        <i class="fas fa-trash"></i> İptal Et
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Available courses for selection -->
                <h5 class="mt-4 mb-3">Seçilebilir Dersler</h5>
                
                <?php if (empty($available_courses)): ?>
                <div class="alert alert-info">
                    Bu dönem için seçebileceğiniz ders bulunmamaktadır.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Ders Kodu</th>
                                <th>Ders Adı</th>
                                <th>Bölüm</th>
                                <th>Kredi</th>
                                <th>Öğretmen</th>
                                <th>Haftalık Saat</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                <td><?php echo htmlspecialchars($course['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['credit']); ?></td>
                                <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['hours_per_week']); ?></td>
                                <td>
                                    <?php if ($is_course_selection_active): ?>
                                    <form method="POST" action="<?php echo url('/pages/course/select_courses.php?term_id=' . $selected_term_id); ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Bu dersi seçmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-plus"></i> Seç
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        <i class="fas fa-lock"></i> Seçim Kapalı
                                    </button>
                                    <?php endif; ?>
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