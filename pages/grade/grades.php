<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
require_once '../../models/Course.php';
require_once '../../models/Term.php';
require_once '../../models/GradeScale.php';
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

// Check if course_id and term_id are provided
if (!isset($_GET['course_id']) || !isset($_GET['term_id'])) {
    header("Location: " . url('/pages/course/my_courses.php'));
    exit;
}

$course_id = $_GET['course_id'];
$term_id = $_GET['term_id'];

// Verify that the teacher is assigned to this course for this term
$query = "SELECT c.*, t.name as term_name 
          FROM teacher_courses tc
          JOIN courses c ON tc.course_id = c.id
          JOIN terms t ON tc.term_id = t.id
          WHERE tc.teacher_id = ? AND tc.course_id = ? AND tc.term_id = ?";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $user->id);
$stmt->bindParam(2, $course_id);
$stmt->bindParam(3, $term_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    // Teacher is not assigned to this course/term
    header("Location: " . url('/pages/course/my_courses.php'));
    exit;
}

$course_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure grade scales exist for this course-teacher-term
$gradeScale = new GradeScale($db);
if (!$gradeScale->scalesExist($course_id, $user->id, $term_id)) {
    $gradeScale->course_id = $course_id;
    $gradeScale->teacher_id = $user->id;
    $gradeScale->term_id = $term_id;
    $gradeScale->createDefaultScales();
}

// Get grade scales for reference
$scales_stmt = $gradeScale->readByCourseTeacherTerm($course_id, $user->id, $term_id);
$grade_scales = [];
while ($scale = $scales_stmt->fetch(PDO::FETCH_ASSOC)) {
    $grade_scales[] = $scale;
}

// Sayfa başlığı
$page_title = 'Not Girişi: ' . $course_info['code'] . ' - ' . $course_info['name'];

// Sayfa içeriğini oluştur
ob_start();
?>

<div class="container-fluid px-0">
    <!-- Back to courses button -->
    <div class="mb-4">
        <a href="<?php echo url('/pages/course/my_courses.php'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Derslerime Dön
        </a>
    </div>

    <!-- Course Info Card -->
    <div class="card shadow-lg rounded-lg mb-4">
        <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
            <h5 class="mb-0 font-semibold">
                <i class="fas fa-book-open mr-2"></i> Ders Bilgileri
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Ders Kodu:</strong> <?php echo $course_info['code']; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Ders Adı:</strong> <?php echo $course_info['name']; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Dönem:</strong> <?php echo $course_info['term_name']; ?></p>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <p><strong>Kredi:</strong> <?php echo $course_info['credit']; ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Ders Saati:</strong> <?php echo $course_info['hours_per_week']; ?> saat/hafta</p>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url('/pages/grade/course_grade_scales.php?course_id=' . $course_id . '&term_id=' . $term_id); ?>"
                        class="btn btn-sm btn-outline-info">
                        <i class="fas fa-chart-bar"></i> Not Ölçeğini Görüntüle
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Card -->
    <div class="card shadow-lg rounded-lg">
        <div class="card-header bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3">
            <h5 class="mb-0 font-semibold">
                <i class="fas fa-clipboard-check mr-2"></i> Öğrenci Notları
            </h5>
        </div>
        <div class="card-body">
            <div id="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Yükleniyor...</span>
                </div>
                <p class="mt-2">Öğrenci listesi yükleniyor, lütfen bekleyin...</p>
            </div>

            <div id="errorContainer" class="d-none text-center py-4">
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle mr-2"></i> Yükleme Hatası</h5>
                    <p id="errorMessage" class="mt-3 mb-3"></p>
                    <button id="retryButton" class="btn btn-primary">
                        <i class="fas fa-sync-alt mr-2"></i> Tekrar Dene
                    </button>
                </div>
            </div>

            <div id="alertContainer"></div>

            <div id="studentsContainer" class="d-none">
                <form id="gradeForm">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Öğrenci No</th>
                                    <th width="40%">Öğrenci Adı</th>
                                    <th width="20%">Not</th>
                                    <th width="20%">Harf Notu</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                <!-- Student data will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary px-4" id="saveButton">
                            <i class="fas fa-save mr-2"></i> Notları Kaydet
                        </button>
                    </div>
                </form>
            </div>

            <div id="noStudents" class="alert alert-info d-none">
                <i class="fas fa-info-circle mr-2"></i> Bu derse kayıtlı öğrenci bulunmamaktadır.
            </div>
        </div>
    </div>

    <!-- Grade Scale Reference Card -->
    <div class="card shadow-lg rounded-lg mt-4">
        <div class="card-header bg-gradient-to-r from-blue-500 to-teal-500 text-white py-3">
            <h5 class="mb-0 font-semibold"><i class="fas fa-info-circle mr-2"></i> Not Ölçeği Referansı</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Harf Notu</th>
                            <th>Minimum Not</th>
                            <th>Maksimum Not</th>
                            <th>Katsayı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grade_scales as $scale): ?>
                        <tr>
                            <td><strong
                                    class="<?php echo ($scale['letter'] == 'FF') ? 'text-danger' : ''; ?>"><?php echo $scale['letter']; ?></strong>
                            </td>
                            <td><?php echo $scale['min_grade']; ?></td>
                            <td><?php echo $scale['max_grade']; ?></td>
                            <td><?php echo $scale['grade_point']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseId = <?php echo $course_id; ?>;
    const termId = <?php echo $term_id; ?>;
    const gradeScales = <?php echo json_encode($grade_scales); ?>;

    // Load students
    loadStudents();

    // Handle form submission
    document.getElementById('gradeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveGrades();
    });

    // Add event listener for retry button
    document.getElementById('retryButton').addEventListener('click', function() {
        // Hide error container and show loading
        document.getElementById('errorContainer').classList.add('d-none');
        document.getElementById('loading').classList.remove('d-none');

        // Try loading students again
        loadStudents();
    });

    // Function to load students
    function loadStudents() {
        fetch(`<?php echo url('/api/course_students.php'); ?>?course_id=${courseId}&term_id=${termId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(
                        `Server responded with status: ${response.status} ${response.statusText}`);
                }
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON, handle non-JSON responses
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // If parsing fails, show the error with some of the response text
                    const preview = text.substr(0, 150) + (text.length > 150 ? '...' : '');
                    throw new Error(
                        `API returned invalid JSON: ${e.message}.<br><br><strong>Response preview:</strong><br><pre class="bg-light p-2 mt-2 small">${preview.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>`
                        );
                }

                document.getElementById('loading').classList.add('d-none');

                if (data.success && data.data.length > 0) {
                    document.getElementById('studentsContainer').classList.remove('d-none');
                    renderStudentTable(data.data);
                } else {
                    document.getElementById('noStudents').classList.remove('d-none');
                    if (data.message) {
                        showAlert('info', data.message);
                    }
                }
            })
            .catch(error => {
                document.getElementById('loading').classList.add('d-none');

                // Show error in the error container
                const errorContainer = document.getElementById('errorContainer');
                const errorMessage = document.getElementById('errorMessage');

                errorMessage.innerHTML = `Öğrenci listesi yüklenirken bir hata oluştu:<br>${error.message}`;
                errorContainer.classList.remove('d-none');

                console.error('Error loading students:', error);
            });
    }

    // Function to render student table
    function renderStudentTable(students) {
        const tableBody = document.getElementById('studentTableBody');
        tableBody.innerHTML = '';

        students.forEach((student, index) => {
            const row = document.createElement('tr');

            // Find letter grade
            let letterGrade = '';
            if (student.grade !== null) {
                const scale = getLetterGrade(student.grade);
                letterGrade = scale ? scale.letter : '';
            }

            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${student.student_id || ''}</td>
                <td>${student.surname} ${student.name}</td>
                <td>
                    <input type="number" 
                           class="form-control grade-input" 
                           name="grades[${student.id}]" 
                           value="${student.grade || ''}" 
                           min="0" 
                           max="100" 
                           step="0.1"
                           data-student-id="${student.id}"
                           onchange="updateLetterGrade(this)">
                </td>
                <td>
                    <span class="letter-grade ${letterGrade === 'FF' ? 'text-danger font-weight-bold' : ''}">${letterGrade}</span>
                </td>
            `;

            tableBody.appendChild(row);
        });
    }

    // Function to save grades
    function saveGrades() {
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Kaydediliyor...';

        // Collect all grades
        const gradeInputs = document.querySelectorAll('.grade-input');
        const grades = {};

        gradeInputs.forEach(input => {
            const studentId = input.dataset.studentId;
            const grade = input.value.trim();
            if (grade !== '') {
                grades[studentId] = grade;
            }
        });

        // Debug output to console
        console.log("Sending grades:", grades);
        console.log("Course ID:", courseId);
        console.log("Term ID:", termId);

        // Prepare form data
        const formData = new FormData();
        formData.append('course_id', courseId);
        formData.append('term_id', termId);
        formData.append('grades', JSON.stringify(grades));

        // Send to API
        fetch('<?php echo url('/api/save_grades.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        // Try to parse error response as JSON if possible
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message ||
                                `HTTP error! Status: ${response.status}`);
                        } catch (e) {
                            if (e instanceof SyntaxError) {
                                // If not valid JSON, show raw response (truncated)
                                const preview = text.substr(0, 150) + (text.length > 150 ? '...' :
                                    '');
                                throw new Error(`Server error (${response.status}): ${preview}`);
                            }
                            throw e; // Rethrow if it's our custom error
                        }
                    });
                }
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON, handle non-JSON responses
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    const preview = text.substr(0, 150) + (text.length > 150 ? '...' : '');
                    throw new Error(
                        `API returned invalid JSON: ${e.message}.<br><br><strong>Response preview:</strong><br><pre class="bg-light p-2 mt-2 small">${preview.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>`
                        );
                }

                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Notları Kaydet';

                if (data.success) {
                    showAlert('success', 'Notlar başarıyla kaydedildi.');
                } else {
                    showAlert('danger', 'Notlar kaydedilirken bir hata oluştu: ' + (data.message ||
                        'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save mr-2"></i> Notları Kaydet';
                showAlert('danger', 'Notlar kaydedilirken bir hata oluştu: ' + error.message);
                console.error('Error saving grades:', error);
            });
    }

    // Function to display alert
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Kapat">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

        alertContainer.innerHTML = '';
        alertContainer.appendChild(alert);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alertContainer.removeChild(alert), 500);
        }, 5000);
    }

    // Expose function to window for inline event handlers
    window.updateLetterGrade = function(input) {
        const grade = parseFloat(input.value);
        const letterGradeSpan = input.closest('tr').querySelector('.letter-grade');

        if (!isNaN(grade)) {
            const scale = getLetterGrade(grade);
            if (scale) {
                letterGradeSpan.textContent = scale.letter;
                letterGradeSpan.className = 'letter-grade ' + (scale.letter === 'FF' ?
                    'text-danger font-weight-bold' : '');
            } else {
                letterGradeSpan.textContent = '';
                letterGradeSpan.className = 'letter-grade';
            }
        } else {
            letterGradeSpan.textContent = '';
            letterGradeSpan.className = 'letter-grade';
        }
    };

    // Helper function to find letter grade for a numeric grade
    function getLetterGrade(numericGrade) {
        return gradeScales.find(scale =>
            parseFloat(numericGrade) >= parseFloat(scale.min_grade) &&
            parseFloat(numericGrade) <= parseFloat(scale.max_grade)
        );
    }
});
</script>

<?php
$content = ob_get_clean();

// Include layout
include_once '../../includes/layout.php';
?>