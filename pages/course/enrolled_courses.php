<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../models/User.php';
require_once '../../models/Term.php';
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

// Get current term
$term->getCurrentTerm();
$current_term_id = $term->id ?? null;

// Get selected term (default to current)
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : $current_term_id;

// Get student's enrolled courses
$student_id = $_SESSION['user_id'];
$enrolled_courses = [];

if ($selected_term_id) {
    $sql = "SELECT c.id, c.code, c.name, c.credit, c.description, c.hours_per_week, 
                   d.name as department_name, CONCAT(u.name, ' ', u.surname) as teacher_name
            FROM student_courses sc
            JOIN courses c ON sc.course_id = c.id
            JOIN teacher_courses tc ON c.id = tc.course_id AND tc.term_id = sc.term_id
            JOIN users u ON tc.teacher_id = u.id
            JOIN terms t ON sc.term_id = t.id
            LEFT JOIN departments d ON c.department_id = d.id
            WHERE sc.student_id = ? AND sc.term_id = ?
            AND c.status = 'active' AND t.status = 'active'
            ORDER BY c.code";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $student_id);
    $stmt->bindParam(2, $selected_term_id);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $enrolled_courses[] = $row;
    }
}

// Get all terms for filter
$all_terms = $term->readActive();

// Page title
$page_title = 'Kayıtlı Derslerim';

// Get content
ob_start();
?>

<div class="row">

    <!-- Main content -->
    <div class="col-md-12">
        <!-- Page heading -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book"></i> Kayıtlı Derslerim</h5>
            </div>
            <div class="card-body">
                <!-- Term filter -->
                <form method="GET" action="<?php echo url('/pages/course/enrolled_courses.php'); ?>" class="mb-4">
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

                <?php if (empty($enrolled_courses)): ?>
                <div class="alert alert-info">
                    Bu dönem için kayıtlı dersleriniz bulunmamaktadır.
                </div>
                <?php else: ?>
                <!-- Enrolled courses table -->
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                <td><?php echo htmlspecialchars($course['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['credit']); ?></td>
                                <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['hours_per_week']); ?></td>
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

<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php
// Get content
$content = ob_get_clean();

// Include layout
require_once '../../includes/layout.php';
?>
