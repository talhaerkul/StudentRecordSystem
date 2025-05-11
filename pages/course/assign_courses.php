<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Course.php';
require_once '../../models/User.php';
require_once '../../models/Term.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require login
requireLogin();

// Check if user is admin
if (!in_array($_SESSION['role'], [ROLE_ADMIN])) {
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
$department = new Department($db);

// Get active terms
$terms_result = $term->readActive();
$terms = [];
while ($term_row = $terms_result->fetch(PDO::FETCH_ASSOC)) {
    $terms[] = $term_row;
}

// Get selected term
$selected_term_id = null;
if (isset($_GET['term_id']) && !empty($_GET['term_id'])) {
    $selected_term_id = $_GET['term_id'];
} else if (!empty($terms)) {
    $selected_term_id = $terms[0]['id'];
}

// Get all departments
$departments_result = $department->readAll();
$departments = [];
while ($department_row = $departments_result->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $department_row;
}

// Get selected department
$selected_department_id = null;
if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $selected_department_id = $_GET['department_id'];
}

// Get teachers
$teachers_result = $user->readByRole(ROLE_TEACHER);
$teachers = [];
while ($teacher = $teachers_result->fetch(PDO::FETCH_ASSOC)) {
    $teachers[] = $teacher;
}

// Get courses
$courses = [];
if ($selected_department_id) {
    $courses_result = $course->readActiveByDepartment($selected_department_id);
} else {
    $courses_result = $course->readActive();
}
while ($course_row = $courses_result->fetch(PDO::FETCH_ASSOC)) {
    $courses[] = $course_row;
}

// Group teachers by department
$teachers_by_dept = [];
foreach($teachers as $t) {
    if (!isset($teachers_by_dept[$t['department_id']])) {
        $teachers_by_dept[$t['department_id']] = [];
    }
    $teachers_by_dept[$t['department_id']][] = $t;
}

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_course'])) {
        // Validate inputs
        if (empty($_POST['teacher_id']) || empty($_POST['course_id']) || empty($_POST['term_id'])) {
            $error = 'Lütfen tüm alanları doldurun.';
        } else {
            // Check if assignment already exists
            $query = "SELECT id FROM teacher_courses 
                     WHERE teacher_id = ? AND course_id = ? AND term_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $_POST['teacher_id']);
            $stmt->bindParam(2, $_POST['course_id']);
            $stmt->bindParam(3, $_POST['term_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Bu ders zaten bu öğretmene ve döneme atanmış.';
            } else {
                // Assign course
                $course->id = $_POST['course_id'];
                if ($course->assignToTeacher($_POST['teacher_id'], $_POST['term_id'])) {
                    $message = 'Ders başarıyla atandı.';
                    
                } else {
                    $error = 'Ders atama işlemi başarısız oldu.';
                }
            }
        }
    } elseif (isset($_POST['remove_assignment'])) {
        if (empty($_POST['assignment_id'])) {
            $error = 'Atama ID bulunamadı.';
        } else {
            // Get assignment details
            $query = "SELECT teacher_id, course_id, term_id FROM teacher_courses WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $_POST['assignment_id']);
            $stmt->execute();
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                // Remove assignment
                $course->id = $assignment['course_id'];
                if ($course->removeFromTeacher($assignment['teacher_id'], $assignment['term_id'])) {
                    $message = 'Ders ataması başarıyla kaldırıldı.';
                 
                } else {
                    $error = 'Ders ataması kaldırılamadı.';
                }
            } else {
                $error = 'Belirtilen atama bulunamadı.';
            }
        }
    }
}

// Get current course assignments for the selected term
$current_assignments = [];
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

if ($selected_term_id) {
    // First get total number of records for pagination
    $count_query = "SELECT COUNT(*) as total FROM teacher_courses tc
                   JOIN courses c ON tc.course_id = c.id
                   JOIN terms t ON tc.term_id = t.id
                   WHERE tc.term_id = ? AND c.status = 'active' AND t.status = 'active'";
                   
    // Add department filter if selected
    if ($selected_department_id) {
        $count_query .= " AND c.department_id = ?";
    }
    
    $count_stmt = $db->prepare($count_query);
    
    if ($selected_department_id) {
        $count_stmt->bindParam(1, $selected_term_id);
        $count_stmt->bindParam(2, $selected_department_id);
    } else {
        $count_stmt->bindParam(1, $selected_term_id);
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);
    
    // Now get the actual records with pagination
    $query = "SELECT tc.id, c.code, c.name as course_name, c.credit, 
              CONCAT(u.name, ' ', u.surname) as teacher_name, t.name as term_name,
              d.name as department_name, tc.teacher_id, tc.course_id, tc.term_id, c.department_id
              FROM teacher_courses tc
              JOIN courses c ON tc.course_id = c.id
              JOIN users u ON tc.teacher_id = u.id
              JOIN terms t ON tc.term_id = t.id
              LEFT JOIN departments d ON c.department_id = d.id
              WHERE tc.term_id = ? AND c.status = 'active' AND t.status = 'active'";
    
    // Add department filter if selected
    if ($selected_department_id) {
        $query .= " AND c.department_id = ?";
    }
    
    $query .= " ORDER BY department_name, course_name, teacher_name";
    $query .= " LIMIT $start_from, $records_per_page";
    
    $stmt = $db->prepare($query);
    
    if ($selected_department_id) {
        $stmt->bindParam(1, $selected_term_id);
        $stmt->bindParam(2, $selected_department_id);
    } else {
        $stmt->bindParam(1, $selected_term_id);
    }
    
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_assignments[] = $row;
    }
}

// Build JavaScript objects as strings for dynamic teacher loading
$teachers_js = "var teachersByDepartment = {};\n";
foreach($teachers_by_dept as $dept_id => $dept_teachers) {
    $teachers_js .= "teachersByDepartment[" . $dept_id . "] = [";
    foreach($dept_teachers as $t) {
        $teachers_js .= "{id: " . $t['id'] . ", name: '" . addslashes($t['name'] . ' ' . $t['surname']) . "'},";
    }
    $teachers_js .= "];\n";
}

// Map courses to their departments
$courses_js = "var courseDepartments = {};\n";
foreach($courses as $c) {
    $courses_js .= "courseDepartments[" . $c['id'] . "] = " . $c['department_id'] . ";\n";
}

// Sayfa başlığı
$page_title = 'Ders Atama Yönetimi';

// İçerik oluştur
ob_start();
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
                    <img src="../../assets/avatar.png" alt="Kullanıcı Avatarı" class="img-fluid rounded-circle mb-2"
                        style="max-width: 100px;">
                    <h5><?php echo $_SESSION['name']; ?></h5>
                    <p class="text-muted"><?php echo $_SESSION['role_name']; ?></p>
                </div>

                <div class="list-group">
                    <a href="<?php echo url('/pages/auth/profile.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card"></i> Profil Bilgileri
                    </a>
                    <a href="<?php echo url('/pages/auth/change_password.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-key"></i> Şifre Değiştir
                    </a>
                    <a href="<?php echo url('/pages/auth/logout.php'); ?>"
                        class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-link"></i> Hızlı Erişim</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo url('/pages/course/courses.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-book"></i> Dersler
                    </a>
                    <a href="<?php echo url('/pages/course/course_schedule.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt"></i> Ders Programı
                    </a>
                    <a href="<?php echo url('/pages/term/terms.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-clock"></i> Dönemler
                    </a>
                    <a href="<?php echo url('/pages/department/departments.php'); ?>"
                        class="list-group-item list-group-item-action">
                        <i class="fas fa-building"></i> Bölümler
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="col-md-9">
        <!-- Filter card -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtrele</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo url('/pages/course/assign_courses.php'); ?>" class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="term_id">Dönem</label>
                            <select class="form-control" id="term_id" name="term_id">
                                <?php foreach ($terms as $term): ?>
                                <option value="<?php echo $term['id']; ?>"
                                    <?php echo ($term['id'] == $selected_term_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($term['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="department_id">Bölüm</label>
                            <select class="form-control" id="department_id" name="department_id">
                                <option value="">Tümü</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                    <?php echo ($dept['id'] == $selected_department_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center mt-4">
                        <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Assign Course Card -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Ders Ata</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo url('/pages/course/assign_courses.php'); ?>">
                            <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                            <input type="hidden" name="department_id" value="<?php echo $selected_department_id; ?>">

                            <div class="form-group">
                                <label for="course_id">Ders</label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <option value="">Ders Seçin</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="teacher_id">Öğretmen</label>
                                <select class="form-control" id="teacher_id" name="teacher_id" required>
                                    <option value="">Öğretmen Seçin</option>
                                </select>
                            </div>

                            <button type="submit" name="assign_course" class="btn btn-success btn-block">
                                <i class="fas fa-check"></i> Ders Ata
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Assignments -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Mevcut Ders Atamaları</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($current_assignments)): ?>
                        <div class="alert alert-info">
                            Bu dönem için henüz ders ataması yapılmamış.
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
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['code']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['credit']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td>
                                            <form method="POST"
                                                action="<?php echo url('/pages/course/assign_courses.php'); ?>"
                                                class="d-inline"
                                                onsubmit="return confirm('Bu ders atamasını kaldırmak istediğinizden emin misiniz?');">
                                                <input type="hidden" name="assignment_id"
                                                    value="<?php echo $assignment['id']; ?>">
                                                <input type="hidden" name="term_id"
                                                    value="<?php echo $selected_term_id; ?>">
                                                <input type="hidden" name="department_id"
                                                    value="<?php echo $selected_department_id; ?>">
                                                <button type="submit" name="remove_assignment"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Kaldır
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($current_assignments) && $total_pages > 1): ?>
                        <nav aria-label="Sayfalama">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="<?php echo url('/pages/course/assign_courses.php?term_id=' . $selected_term_id . '&department_id=' . $selected_department_id . '&page=' . ($current_page - 1)); ?>">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="<?php echo url('/pages/course/assign_courses.php?term_id=' . $selected_term_id . '&department_id=' . $selected_department_id . '&page=1'); ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="<?php echo url('/pages/course/assign_courses.php?term_id=' . $selected_term_id . '&department_id=' . $selected_department_id . '&page=' . $i); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="<?php echo url('/pages/course/assign_courses.php?term_id=' . $selected_term_id . '&department_id=' . $selected_department_id . '&page=' . $total_pages); ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="<?php echo url('/pages/course/assign_courses.php?term_id=' . $selected_term_id . '&department_id=' . $selected_department_id . '&page=' . ($current_page + 1)); ?>">
                                        Sonraki <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add this JavaScript to update teacher dropdown based on selected course
$additional_js = <<<EOT
<script>
$(document).ready(function() {
    // Create object with teachers grouped by department
    $teachers_js
    
    // Create object with course department mapping
    $courses_js
    
    // Function to update teacher dropdown based on selected course
    function updateTeacherDropdown() {
        var courseId = $('#course_id').val();
        var departmentId = courseDepartments[courseId];
        
        console.log('Selected course ID:', courseId);
        console.log('Course department ID:', departmentId);
        
        // Clear and disable teacher dropdown if no course selected
        if (!courseId) {
            $('#teacher_id').empty().append('<option value="">Öğretmen Seçin</option>');
            return;
        }
        
        // Get teachers for this department
        var teachers = teachersByDepartment[departmentId] || [];
        console.log('Available teachers:', teachers.length);
        
        // Update teacher dropdown
        $('#teacher_id').empty();
        $('#teacher_id').append('<option value="">Öğretmen Seçin</option>');
        
        $.each(teachers, function(i, teacher) {
            $('#teacher_id').append('<option value="' + teacher.id + '">' + teacher.name + '</option>');
        });
    }
    
    // Wire up course select change event
    $('#course_id').on('change', function() {
        updateTeacherDropdown();
    });
    
    // Initialize teacher dropdown
    updateTeacherDropdown();
});
</script>
EOT;

// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>