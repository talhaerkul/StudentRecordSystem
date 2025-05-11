<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
require_once '../../models/Term.php';
require_once '../../models/Course.php';
require_once '../../models/Department.php';
require_once '../../config/database.php';

// Require login and ensure user is a teacher
requireLogin();
if ($_SESSION['role'] != ROLE_TEACHER) {
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Get current user information
$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->is_teacher = true;

// Get terms for filter
$term = new Term($db);
$terms_stmt = $term->readActive();
$terms = [];
while ($term_row = $terms_stmt->fetch(PDO::FETCH_ASSOC)) {
    $terms[] = $term_row;
}

// Get departments for filter
$department = new Department($db);
$departments_stmt = $department->readAll();
$departments = [];
while ($dept_row = $departments_stmt->fetch(PDO::FETCH_ASSOC)) {
    $departments[] = $dept_row;
}

// Get filter parameters
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : '';
$selected_department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get students across all teacher's courses
$students_stmt = $user->getTeacherStudents($selected_term_id);
$students = [];

while ($row = $students_stmt->fetch(PDO::FETCH_ASSOC)) {
    // Apply department filter if set
    if (!empty($selected_department_id) && $row['department_id'] != $selected_department_id) {
        continue;
    }
    
    // Apply search filter if set
    if (!empty($search_query) && 
        stripos($row['name'], $search_query) === false && 
        stripos($row['surname'], $search_query) === false && 
        stripos($row['student_id'], $search_query) === false) {
        continue;
    }
    
    $students[] = $row;
}

// Determine if we're viewing a specific student's details
$view_student_id = isset($_GET['view_student_id']) ? $_GET['view_student_id'] : null;
$student_courses = [];
$student_details = null;

if ($view_student_id) {
    // Get student details
    $student = new User($db);
    $student->id = $view_student_id;
    
    $student_result = $student->readOne();
    if ($student_result) {
        $student_details = [
            'id' => $student->id,
            'student_id' => $student->student_id,
            'name' => $student->name,
            'surname' => $student->surname,
            'email' => $student->email,
            'department_id' => $student->department_id,
            'department_name' => $student->department_name
        ];
        
        // Get courses this student is taking with this teacher
        $courses_stmt = $user->getStudentCoursesForTeacher($view_student_id, $selected_term_id);
        while ($course = $courses_stmt->fetch(PDO::FETCH_ASSOC)) {
            $student_courses[] = $course;
        }
    }
}

// Page title
$page_title = 'Öğrencilerim';

// Create page content
ob_start();
?>

<!-- Back button if viewing student details -->
<?php if ($view_student_id): ?>
<div class="mb-4">
    <a href="<?php echo url('/pages/course/my_students.php' . 
        (!empty($selected_term_id) ? '?term_id=' . $selected_term_id : '')); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-2"></i> Öğrenci Listesine Dön
    </a>
</div>
<?php endif; ?>

<?php if (!$view_student_id): ?>
<!-- Filter Section -->
<div class="card shadow-lg rounded-lg mb-4">
    <div class="card-header bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-filter mr-2"></i> Filtrele</h5>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline">
            <div class="row w-100">
                <div class="col-md-4 mb-3">
                    <label for="term_id" class="mr-2">Dönem:</label>
                    <select name="term_id" id="term_id" class="form-control w-100">
                        <option value="">Tüm Dönemler</option>
                        <?php foreach ($terms as $term_item): ?>
                        <option value="<?php echo $term_item['id']; ?>"
                            <?php echo ($selected_term_id == $term_item['id']) ? 'selected' : ''; ?>>
                            <?php echo $term_item['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="search" class="mr-2">Ara:</label>
                    <div class="input-group w-100">
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="İsim, Soyisim veya Öğrenci No"
                            value="<?php echo htmlspecialchars($search_query); ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card shadow-lg rounded-lg">
    <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-users mr-2"></i> Öğrencilerim</h5>
    </div>
    <div class="card-body">
        <?php if (count($students) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="bg-gray-200">
                    <tr>
                        <th>Öğrenci No</th>
                        <th>Öğrenci Adı</th>
                        <th>Bölüm</th>
                        <th>Ders Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td><?php echo $student['surname'] . ' ' . $student['name']; ?></td>
                        <td><?php echo $student['department_name']; ?></td>
                        <td>
                            <span class="badge badge-primary px-2 py-1 rounded-pill">
                                <?php echo $student['course_count']; ?> ders
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="<?php echo url('/pages/course/my_students.php?view_student_id=' . $student['id'] . 
                                    (!empty($selected_term_id) ? '&term_id=' . $selected_term_id : '')); ?>"
                                    class="btn btn-sm btn-outline-primary" title="Öğrenci Detayları">
                                    <i class="fas fa-user-graduate"></i> Detaylar
                                </a>
                                <a href="mailto:<?php echo $student['email']; ?>" class="btn btn-sm btn-outline-success"
                                    title="E-posta Gönder">
                                    <i class="fas fa-envelope"></i> E-posta
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <p class="text-muted">Toplam <?php echo count($students); ?> öğrenci listeleniyor.</p>
        </div>
        <?php else: ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle mr-2"></i>
            <?php if (!empty($selected_term_id) || !empty($selected_department_id) || !empty($search_query)): ?>
            Seçilen kriterlere uygun öğrenci bulunamadı. Lütfen farklı filtre kriterleri deneyin.
            <?php else: ?>
            Henüz size atanmış bir derste kayıtlı öğrenci bulunmamaktadır.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Student Details -->
<?php if ($student_details): ?>
<div class="card shadow-lg rounded-lg mb-4">
    <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
        <h5 class="mb-0 font-semibold">
            <i class="fas fa-user-graduate mr-2"></i> Öğrenci Bilgileri
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h4><?php echo $student_details['surname'] . ' ' . $student_details['name']; ?></h4>
                <p><strong>Öğrenci No:</strong> <?php echo $student_details['student_id']; ?></p>
                <p><strong>Bölüm:</strong> <?php echo $student_details['department_name']; ?></p>
            </div>
            <div class="col-md-6 text-md-right">
                <p><strong>E-posta:</strong> <?php echo $student_details['email']; ?></p>
                <a href="mailto:<?php echo $student_details['email']; ?>" class="btn btn-outline-success mt-2">
                    <i class="fas fa-envelope mr-2"></i> E-posta Gönder
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Student Courses -->
<div class="card shadow-lg rounded-lg">
    <div class="card-header bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3">
        <h5 class="mb-0 font-semibold">
            <i class="fas fa-book mr-2"></i> Öğrencinin Aldığı Derslerim
        </h5>
    </div>
    <div class="card-body">
        <?php if (count($student_courses) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="bg-gray-200">
                    <tr>
                        <th>Ders Kodu</th>
                        <th>Ders Adı</th>
                        <th>Dönem</th>
                        <th>Kredi</th>
                        <th>Not</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_courses as $course): ?>
                    <tr>
                        <td><?php echo $course['code']; ?></td>
                        <td><?php echo $course['name']; ?></td>
                        <td><?php echo $course['term_name']; ?></td>
                        <td><?php echo $course['credit']; ?></td>
                        <td>
                            <?php if ($course['grade']): ?>
                            <span class="badge badge-success px-2 py-1"><?php echo $course['grade']; ?></span>
                            <?php else: ?>
                            <span class="badge badge-secondary px-2 py-1">Not girilmemiş</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo url('/pages/grade/grades.php?course_id=' . $course['id'] . 
                                '&term_id=' . $course['term_id']); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-clipboard-check mr-1"></i> Not Girişi
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle mr-2"></i> Bu dönemde öğrencinin sizden aldığı ders bulunmamaktadır.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-danger shadow">
    <i class="fas fa-exclamation-circle mr-2"></i> Öğrenci bulunamadı.
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Help Info -->
<div class="card shadow-lg rounded-lg mt-4">
    <div class="card-header bg-gradient-to-r from-blue-500 to-teal-500 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-info-circle mr-2"></i> Bilgi</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-light border">
            <h6 class="alert-heading font-weight-bold">
                <i class="fas fa-lightbulb text-warning mr-2"></i> Öğrencileriniz Hakkında
            </h6>
            <p>Bu sayfada tüm derslerinizde kayıtlı olan öğrencileri görebilirsiniz.</p>
            <ul class="mb-0 pl-4">
                <li>Dönem ve arama filtreleri ile öğrencileri filtreleyebilirsiniz.</li>
                <li>Öğrenci detaylarına erişerek, aldığı derslerinizi ve notlarını görüntüleyebilirsiniz.</li>
                <li>Öğrencilerinize doğrudan e-posta gönderebilirsiniz.</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include_once '../../includes/layout.php';
?>