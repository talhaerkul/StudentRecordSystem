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

// Only teachers and admins can access this page
if ($_SESSION['role'] != ROLE_TEACHER && $_SESSION['role'] != ROLE_ADMIN) {
    header('Location: ' . url('/pages/dashboard.php'));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$course = new Course($db);
$term = new Term($db);
$gradeScale = new GradeScale($db);
$user = new User($db);

// Check if user is admin
$isAdmin = ($_SESSION['role'] == ROLE_ADMIN);

// Get all active terms
$terms = $term->readActive();

// Set selected term ID (use the first active term by default)
$selected_term_id = null;
if (isset($_GET['term_id']) && !empty($_GET['term_id'])) {
    $selected_term_id = $_GET['term_id'];
} else {
    $firstTerm = $terms->fetch(PDO::FETCH_ASSOC);
    if ($firstTerm) {
        $selected_term_id = $firstTerm['id'];
        // Reset pointer for later use
        $terms->execute();
    }
}

// For admin users, allow selecting a teacher
$selected_teacher_id = $_SESSION['user_id']; // Default to current user
if ($isAdmin && isset($_GET['teacher_id']) && !empty($_GET['teacher_id'])) {
    $selected_teacher_id = $_GET['teacher_id'];
}

// Get all teachers for admin selection
$teachers = [];
if ($isAdmin) {
    $stmt = $user->readByRole(ROLE_TEACHER);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $teachers[] = $row;
    }
}

// Get teacher's courses for the selected term
$teacherCourses = [];
if ($selected_term_id) {
    $query = "SELECT c.id, c.code, c.name, c.credit, d.name as department_name
              FROM courses c
              JOIN teacher_courses tc ON c.id = tc.course_id
              LEFT JOIN departments d ON c.department_id = d.id
              WHERE tc.teacher_id = ? AND tc.term_id = ? AND c.status = 'active'
              ORDER BY c.code ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $selected_teacher_id);
    $stmt->bindParam(2, $selected_term_id);
    $stmt->execute();
    
    $teacherCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Set selected course ID (use the first course by default)
$selected_course_id = null;
if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
    $selected_course_id = $_GET['course_id'];
} else if (!empty($teacherCourses)) {
    $selected_course_id = $teacherCourses[0]['id'];
}

// Get grade scales for the selected course
$gradeScales = [];
if ($selected_course_id && $selected_term_id) {
    // Check if grade scales exist, if not create defaults
    if (!$gradeScale->scalesExist($selected_course_id, $selected_teacher_id, $selected_term_id)) {
        $gradeScale->course_id = $selected_course_id;
        $gradeScale->teacher_id = $selected_teacher_id;
        $gradeScale->term_id = $selected_term_id;
        $gradeScale->createDefaultScales();
    }
    
    // Read grade scales
    $stmt = $gradeScale->readByCourseTeacherTerm($selected_course_id, $selected_teacher_id, $selected_term_id);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gradeScales[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scales'])) {
    // Verify course and term IDs
    if (isset($_POST['course_id'], $_POST['term_id'], $_POST['teacher_id']) && 
        !empty($_POST['course_id']) && !empty($_POST['term_id']) && !empty($_POST['teacher_id'])) {
        
        $course_id = $_POST['course_id'];
        $term_id = $_POST['term_id'];
        $teacher_id = $_POST['teacher_id'];
        
        // Security check: only admin can edit other teachers' scales
        if ($teacher_id != $_SESSION['user_id'] && !$isAdmin) {
            $_SESSION['alert'] = "Bu işlem için yetkiniz bulunmamaktadır.";
            $_SESSION['alert_type'] = "danger";
            header("Location: " . url('/pages/grade/course_grade_scales.php'));
            exit();
        }
        
        // Build scales array
        $scales = [];
        for ($i = 0; $i < count($_POST['letter']); $i++) {
            if (empty($_POST['letter'][$i])) continue;
            
            $scales[] = [
                'letter' => $_POST['letter'][$i],
                'min_grade' => $_POST['min_grade'][$i],
                'max_grade' => $_POST['max_grade'][$i],
                'grade_point' => $_POST['grade_point'][$i]
            ];
        }
        
        // Update scales
        $gradeScale->course_id = $course_id;
        $gradeScale->teacher_id = $teacher_id;
        $gradeScale->term_id = $term_id;
        
        if ($gradeScale->updateScales($scales)) {
            $_SESSION['alert'] = "Not ölçeği başarıyla güncellendi.";
            $_SESSION['alert_type'] = "success";
        } else {
            $_SESSION['alert'] = "Not ölçeği güncellenirken bir hata oluştu.";
            $_SESSION['alert_type'] = "danger";
        }
        
        // Redirect to avoid form resubmission
        $redirectUrl = url('/pages/grade/course_grade_scales.php') . "?term_id=$term_id&course_id=$course_id";
        if ($isAdmin) {
            $redirectUrl .= "&teacher_id=$teacher_id";
        }
        header("Location: $redirectUrl");
        exit();
    }
}

// Set page title
$page_title = "Ders Not Ölçekleri";

// Generate content
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Ders Not Ölçekleri
                    </h5>
                </div>
                <div class="card-body">
                    <style>
                        .is-invalid {
                            border-color: #dc3545 !important;
                            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
                        }
                    </style>
                    
                    <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['alert']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['alert'], $_SESSION['alert_type']); ?>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <?php if ($isAdmin): ?>
                        <div class="col-md-4">
                            <form id="selectTeacherForm" method="GET" action="<?php echo url('/pages/grade/course_grade_scales.php'); ?>" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="teacher_id" class="mr-2">Öğretmen:</label>
                                    <select class="form-control" id="teacher_id" name="teacher_id" onchange="this.form.submit()">
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher['id'] == $selected_teacher_id) ? 'selected' : ''; ?>>
                                            <?php echo $teacher['name'] . ' ' . $teacher['surname']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if ($selected_term_id): ?>
                                <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                                <?php endif; ?>
                                
                                <?php if ($selected_course_id): ?>
                                <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div class="<?php echo $isAdmin ? 'col-md-4' : 'col-md-6'; ?>">
                            <form id="selectTermForm" method="GET" action="<?php echo url('/pages/grade/course_grade_scales.php'); ?>" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="term_id" class="mr-2">Dönem:</label>
                                    <select class="form-control" id="term_id" name="term_id" onchange="this.form.submit()">
                                        <?php while ($termRow = $terms->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $termRow['id']; ?>" <?php echo ($termRow['id'] == $selected_term_id) ? 'selected' : ''; ?>>
                                            <?php echo $termRow['name']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <?php if ($isAdmin && $selected_teacher_id): ?>
                                <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                                <?php endif; ?>
                                
                                <?php if ($selected_course_id): ?>
                                <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <div class="<?php echo $isAdmin ? 'col-md-4' : 'col-md-6'; ?>">
                            <?php if (!empty($teacherCourses)): ?>
                            <form id="selectCourseForm" method="GET" action="<?php echo url('/pages/grade/course_grade_scales.php'); ?>" class="form-inline">
                                <div class="form-group">
                                    <label for="course_id" class="mr-2">Ders:</label>
                                    <select class="form-control" id="course_id" name="course_id" onchange="this.form.submit()">
                                        <?php foreach ($teacherCourses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo ($course['id'] == $selected_course_id) ? 'selected' : ''; ?>>
                                            <?php echo $course['code'] . ' - ' . $course['name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if ($selected_term_id): ?>
                                <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                                <?php endif; ?>
                                
                                <?php if ($isAdmin && $selected_teacher_id): ?>
                                <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($selected_course_id && $selected_term_id): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <?php 
                                    $courseName = '';
                                    foreach ($teacherCourses as $c) {
                                        if ($c['id'] == $selected_course_id) {
                                            $courseName = $c['code'] . ' - ' . $c['name'];
                                            break;
                                        }
                                    }
                                    echo $courseName; 
                                ?> Not Ölçeği
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="gradeScaleForm" method="POST" action="<?php echo url('/pages/grade/course_grade_scales.php'); ?>">
                                <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                                <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                                <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="gradeScaleTable">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Harf Notu</th>
                                                <th>Minimum Not</th>
                                                <th>Maksimum Not</th>
                                                <th>Katsayı</th>
                                                <th width="80">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($gradeScales as $index => $scale): ?>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" name="letter[]" value="<?php echo $scale['letter']; ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control min-grade" name="min_grade[]" value="<?php echo $scale['min_grade']; ?>" min="0" max="100" step="0.01" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control max-grade" name="max_grade[]" value="<?php echo $scale['max_grade']; ?>" min="0" max="100" step="0.01" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" name="grade_point[]" value="<?php echo $scale['grade_point']; ?>" min="0" max="4" step="0.01" required>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-info" id="addRow">
                                            <i class="fas fa-plus"></i> Yeni Satır Ekle
                                        </button>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="<?php echo url('/pages/grade/admin_grade_scales.php'); ?>" class="btn btn-secondary mr-2">
                                            <i class="fas fa-arrow-left"></i> Geri
                                        </a>
                                        <button type="submit" name="save_scales" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Kaydet
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Not:
                        <ul>
                            <li>Harf notları büyük ve sıralı olmalıdır (örn. AA, BA, BB, ...).</li>
                            <li>Minimum ve maksimum not değerleri 0-100 arasında olmalıdır.</li>
                            <li>Notlar birbirleriyle çakışmamalıdır, örneğin bir harf notu için maksimum not 80 ise, diğeri için minimum not 80.01 olmalıdır.</li>
                            <li>Katsayı değerleri genellikle 0-4 arasındadır.</li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Lütfen dönem ve ders seçin.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add row button
    document.getElementById('addRow').addEventListener('click', function() {
        const tbody = document.querySelector('#gradeScaleTable tbody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control" name="letter[]" required>
            </td>
            <td>
                <input type="number" class="form-control min-grade" name="min_grade[]" min="0" max="100" step="0.01" required>
            </td>
            <td>
                <input type="number" class="form-control max-grade" name="max_grade[]" min="0" max="100" step="0.01" required>
            </td>
            <td>
                <input type="number" class="form-control" name="grade_point[]" min="0" max="4" step="0.01" required>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        
        // Add remove button event listener to the new row
        newRow.querySelector('.remove-row').addEventListener('click', removeRow);
    });
    
    // Remove row function
    function removeRow() {
        const row = this.closest('tr');
        row.parentNode.removeChild(row);
    }
    
    // Add event listeners to existing remove buttons
    document.querySelectorAll('.remove-row').forEach(button => {
        button.addEventListener('click', removeRow);
    });
    
    // Form validation
    document.getElementById('gradeScaleForm').addEventListener('submit', function(e) {
        const minGrades = Array.from(document.querySelectorAll('.min-grade')).map(input => parseFloat(input.value));
        const maxGrades = Array.from(document.querySelectorAll('.max-grade')).map(input => parseFloat(input.value));
        
        let isValid = true;
        let errorMessage = '';
        
        // Check that at least one row exists
        if (minGrades.length === 0) {
            errorMessage = 'En az bir not ölçeği girmelisiniz.';
            isValid = false;
        }
        
        // Check that all min grades are less than max grades
        for (let i = 0; i < minGrades.length; i++) {
            if (minGrades[i] > maxGrades[i]) {
                document.querySelectorAll('.min-grade')[i].classList.add('is-invalid');
                document.querySelectorAll('.max-grade')[i].classList.add('is-invalid');
                errorMessage = 'Minimum not, maksimum nottan büyük olamaz.';
                isValid = false;
            } else {
                document.querySelectorAll('.min-grade')[i].classList.remove('is-invalid');
                document.querySelectorAll('.max-grade')[i].classList.remove('is-invalid');
            }
        }
        
        // Check for overlapping ranges
        for (let i = 0; i < minGrades.length; i++) {
            for (let j = i + 1; j < minGrades.length; j++) {
                if ((minGrades[i] <= maxGrades[j] && maxGrades[i] >= minGrades[j]) ||
                    (minGrades[j] <= maxGrades[i] && maxGrades[j] >= minGrades[i])) {
                    document.querySelectorAll('.min-grade')[i].classList.add('is-invalid');
                    document.querySelectorAll('.max-grade')[i].classList.add('is-invalid');
                    document.querySelectorAll('.min-grade')[j].classList.add('is-invalid');
                    document.querySelectorAll('.max-grade')[j].classList.add('is-invalid');
                    errorMessage = 'Not aralıkları çakışamaz.';
                    isValid = false;
                }
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });
});
</script>

<?php
// Get content
$content = ob_get_clean();

// Include layout
require_once '../../includes/layout.php';
?> 