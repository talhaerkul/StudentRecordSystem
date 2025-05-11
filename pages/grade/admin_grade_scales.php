<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../models/Term.php';
require_once '../../models/GradeScale.php';
require_once '../../models/User.php';
require_once '../../config/database.php';

// Require login
requireLogin();

// Only admin can access this page
if ($_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$gradeScale = new GradeScale($db);
$term = new Term($db);
$user = new User($db);

// Get all teachers
$teachers = [];
$stmt = $user->readByRole(ROLE_TEACHER);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teachers[$row['id']] = $row['name'] . ' ' . $row['surname'];
}

// Get all active terms
$activeTerms = [];
$terms = $term->readActive();
while ($row = $terms->fetch(PDO::FETCH_ASSOC)) {
    $activeTerms[$row['id']] = $row;
}

// Set selected term ID (use the first active term by default)
$selected_term_id = null;
if (isset($_GET['term_id']) && !empty($_GET['term_id']) && isset($activeTerms[$_GET['term_id']])) {
    $selected_term_id = $_GET['term_id'];
} else if (!empty($activeTerms)) {
    $selected_term_id = array_key_first($activeTerms);
}

// Get all course-teacher-term combinations
$query = "SELECT DISTINCT c.id as course_id, c.code as course_code, c.name as course_name, 
         tc.teacher_id, tc.term_id, 
         CONCAT(u.name, ' ', u.surname) as teacher_name,
         t.name as term_name,
         (SELECT COUNT(*) FROM course_grade_scales cgs 
          WHERE cgs.course_id = tc.course_id 
          AND cgs.teacher_id = tc.teacher_id 
          AND cgs.term_id = tc.term_id) as scale_count
         FROM teacher_courses tc
         JOIN courses c ON tc.course_id = c.id
         JOIN users u ON tc.teacher_id = u.id
         JOIN terms t ON tc.term_id = t.id
         WHERE tc.term_id = ?
         ORDER BY c.code ASC, teacher_name ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $selected_term_id);
$stmt->execute();
$coursesWithScales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Not Ölçekleri Yönetimi";

// Generate content
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Not Ölçekleri Yönetimi
                    </h5>
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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form id="selectTermForm" method="GET"
                                action="<?php echo url('/pages/grade/admin_grade_scales.php'); ?>" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="term_id" class="mr-2">Dönem:</label>
                                    <select class="form-control" id="term_id" name="term_id"
                                        onchange="this.form.submit()">
                                        <?php foreach ($activeTerms as $term_id => $term): ?>
                                        <option value="<?php echo $term_id; ?>"
                                            <?php echo ($term_id == $selected_term_id) ? 'selected' : ''; ?>>
                                            <?php echo $term['name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">

                            <a href="<?php echo url('/pages/grade/course_grade_scales.php'); ?>"
                                class="btn btn-success ml-2">
                                <i class="fas fa-edit"></i> Not Ölçeği Düzenle
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ders Kodu</th>
                                    <th>Ders Adı</th>
                                    <th>Öğretmen</th>
                                    <th>Not Ölçeği</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($coursesWithScales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Seçili dönemde ders kaydı bulunamadı.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($coursesWithScales as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['teacher_name']; ?></td>
                                    <td>
                                        <?php if ($course['scale_count'] > 0): ?>
                                        <button type="button" class="btn btn-sm btn-info view-scales"
                                            data-course-id="<?php echo $course['course_id']; ?>"
                                            data-teacher-id="<?php echo $course['teacher_id']; ?>"
                                            data-term-id="<?php echo $course['term_id']; ?>"
                                            data-course-name="<?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>"
                                            data-teacher-name="<?php echo $course['teacher_name']; ?>">
                                            <i class="fas fa-eye"></i> Görüntüle
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">Not ölçeği tanımlanmamış</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($course['scale_count'] > 0): ?>
                                        <span class="badge badge-success">Tanımlı</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">Tanımsız</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo url('/pages/grade/course_grade_scales.php?course_id=' . $course['course_id'] . '&term_id=' . $course['term_id'] . '&teacher_id=' . $course['teacher_id']); ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying grade scales -->
<div class="modal fade" id="scalesModal" tabindex="-1" role="dialog" aria-labelledby="scalesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="scalesModalLabel">Not Ölçeği Detayları</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p><strong>Ders:</strong> <span id="modalCourseName"></span></p>
                    <p><strong>Öğretmen:</strong> <span id="modalTeacherName"></span></p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="modalGradeTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Harf Notu</th>
                                <th>Min Not</th>
                                <th>Max Not</th>
                                <th>Katsayı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Grade scale data will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                <a href="#" class="btn btn-primary" id="editScalesLink">
                    <i class="fas fa-edit"></i> Düzenle
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View scales button click event
    const viewButtons = document.querySelectorAll('.view-scales');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.getAttribute('data-course-id');
            const teacherId = this.getAttribute('data-teacher-id');
            const termId = this.getAttribute('data-term-id');
            const courseName = this.getAttribute('data-course-name');
            const teacherName = this.getAttribute('data-teacher-name');

            // Set modal content
            document.getElementById('modalCourseName').textContent = courseName;
            document.getElementById('modalTeacherName').textContent = teacherName;

            // Set edit link
            const editLink = document.getElementById('editScalesLink');
            editLink.href =
                `<?php echo url('/pages/grade/course_grade_scales.php'); ?>?course_id=${courseId}&term_id=${termId}&teacher_id=${teacherId}`;

            // Clear previous table data
            const tableBody = document.getElementById('modalGradeTable').querySelector('tbody');
            tableBody.innerHTML =
                '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

            // Fetch grade scales via AJAX
            fetch(
                    `<?php echo url('/api/get_grade_scales.php'); ?>?course_id=${courseId}&teacher_id=${teacherId}&term_id=${termId}`
                )
                .then(response => response.json())
                .then(data => {
                    // Clear loading message
                    tableBody.innerHTML = '';

                    if (data.length === 0) {
                        tableBody.innerHTML =
                            '<tr><td colspan="4" class="text-center">Bu ders için not ölçeği bulunamadı.</td></tr>';
                        return;
                    }

                    // Add each grade scale to the table
                    data.forEach(scale => {
                        const row = document.createElement('tr');

                        row.innerHTML = `
                            <td>${scale.letter}</td>
                            <td>${scale.min_grade}</td>
                            <td>${scale.max_grade}</td>
                            <td>${scale.grade_point}</td>
                        `;

                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching grade scales:', error);
                    tableBody.innerHTML =
                        '<tr><td colspan="4" class="text-center text-danger">Hata oluştu. Not ölçeği bilgileri alınamadı.</td></tr>';
                });

            // Show modal
            $('#scalesModal').modal('show');
        });
    });
});
</script>

<?php
// Get content
$content = ob_get_clean();

// Include layout
require_once '../../includes/layout.php';
?>