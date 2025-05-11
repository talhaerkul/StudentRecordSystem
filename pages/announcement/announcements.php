<?php
// Database connection and include the announcement model and service
require_once '../../config/database.php';
require_once '../../models/announcement.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Role.php';
require_once '../../models/Department.php';
require_once '../../models/Course.php';
require_once '../../service/announcement_service.php';

// Start the session
requireLogin();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if role_id and department_id are set in the session
$user_role = $_SESSION['role'];
$user_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

// Determine if the user is an admin
$isAdmin = ($user_role == ROLE_ADMIN);

// Instantiate database and announcement service
$database = new Database();
$db = $database->getConnection();
$announcementService = new AnnouncementService($db);

// Get roles, departments, and courses for dropdowns
$roles = $announcementService->getRoles();
$departments = $announcementService->getDepartments();
$courses = $announcementService->getCourses();

// Prepare courses by department for JavaScript
$courses_by_dept = [];
while($course = $courses->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($courses_by_dept[$course['department_id']])) {
        $courses_by_dept[$course['department_id']] = [];
    }
    $courses_by_dept[$course['department_id']][] = $course;
}

// Reset course PDO statement for later use if needed
$courses = $announcementService->getCourses();

// Handle form submissions - only admin can modify/delete announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    error_log("Form submitted - POST data: " . print_r($_POST, true));
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['title']) && isset($_POST['content'])) {
                    $announcementData = [
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'user_id' => $_SESSION['user_id'],
                        'role_id' => null,
                        'department_id' => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                        'course_id' => !empty($_POST['course_id']) ? $_POST['course_id'] : null,
                        'start_date' => $_POST['start_date'],
                        'end_date' => $_POST['end_date']
                    ];
                    
                    if ($announcementService->createAnnouncement($announcementData)) {
                        error_log("Announcement created successfully");
                        header("Location: announcements.php");
                        exit();
                    } else {
                        error_log("Failed to create announcement");
                        $error = "Failed to create announcement";
                    }
                }
                break;
            case 'update':
                // Update existing announcement
                $announcementData = [
                    'id' => $_POST['id'],
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'role_id' => null,
                    'department_id' => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                    'course_id' => !empty($_POST['course_id']) ? $_POST['course_id'] : null,
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
                ];

                if ($announcementService->updateAnnouncement($announcementData)) {
                    $_SESSION['alert'] = "Duyuru başarıyla güncellendi.";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert'] = "Duyuru güncellenirken bir hata oluştu.";
                    $_SESSION['alert_type'] = "danger";
                }
                break;
            case 'delete':
                // Delete announcement
                $id = $_POST['id'];

                if ($announcementService->deleteAnnouncement($id)) {
                    $_SESSION['alert'] = "Duyuru başarıyla silindi.";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert'] = "Duyuru silinirken bir hata oluştu.";
                    $_SESSION['alert_type'] = "danger";
                }
                break;
        }
    }

    // Redirect to prevent form resubmission
    header('Location: announcements.php');
    exit();
}

// Get announcements
if ($isAdmin) {
    // Admin sees all announcements
    $announcements = $announcementService->getAllAnnouncementsForAdmin();
} else {
    // Students and teachers see only active and valid dated announcements
    $announcements = $announcementService->getAllAnnouncements();
}

// For editing permissions, we still need to check roles
$canEdit = ($user_role == ROLE_ADMIN);

// Read one announcement for editing (only for admin)
$announcement = null;
if (isset($_GET['edit']) && $isAdmin) {
    error_log("Edit parameter detected: " . $_GET['edit']);
    $announcement = $announcementService->getAnnouncementById($_GET['edit']);
    error_log("Announcement data loaded for editing: " . print_r($announcement, true));
} elseif (isset($_GET['edit']) && !$isAdmin) {
    // Redirect non-admin users trying to edit
    $_SESSION['alert'] = "Duyuru düzenleme izniniz bulunmamaktadır.";
    $_SESSION['alert_type'] = "danger";
    header('Location: announcements.php');
    exit();
}

// İçerik oluştur
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Duyurular</h5>
                    <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-light btn-sm" id="newAnnouncementBtn">
                        <i class="fas fa-plus"></i> Yeni Duyuru
                    </button>
                    <?php elseif ($user_role == ROLE_TEACHER): ?>
                    <a href="teacher_announcements.php" class="btn btn-light btn-sm">
                        <i class="fas fa-bullhorn"></i> Öğretmen Duyurularım
                    </a>
                    <?php endif; ?>
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

                    <?php if ($isAdmin): ?>
                    <!-- Admin sees all announcements at once -->
                    <?php 
                    // Use the announcements variable that already contains all announcements for admin
                    if($announcements->rowCount() > 0): 
                    ?>
                    <div class="row">
                        <?php while($row = $announcements->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 <?php echo $row['status'] == 'inactive' ? 'border-danger' : ''; ?>">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['title']); ?></h5>
                                    <?php if($row['status'] == 'inactive'): ?>
                                    <span class="badge badge-danger">Pasif</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                                    <div class="announcement-meta">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($row['user_name'] . ' ' . $row['user_surname']); ?><br>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?><br>
                                            <?php if($row['role_name']): ?>
                                            <i class="fas fa-user-tag"></i>
                                            <?php echo htmlspecialchars($row['role_name']); ?><br>
                                            <?php endif; ?>
                                            <?php if($row['department_name']): ?>
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($row['department_name']); ?><br>
                                            <?php endif; ?>
                                            <?php if($row['course_name']): ?>
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($row['course_name']); ?><br>
                                            <?php endif; ?>
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            if($row['start_date']) echo 'Başlangıç: ' . date('d.m.Y H:i', strtotime($row['start_date'])) . '<br>';
                                            if($row['end_date']) echo 'Bitiş: ' . date('d.m.Y H:i', strtotime($row['end_date']));
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <div class="btn-group">
                                        <a href="announcements.php?edit=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Bu duyuruyu silmek istediğinizden emin misiniz?');">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Duyuru bulunmamaktadır.
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <!-- Non-admin users see only active and valid dated announcements -->
                    <?php 
                    // Get active announcements
                    if($announcements->rowCount() > 0): 
                    ?>
                    <div class="row">
                        <?php while($row = $announcements->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['title']); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($row['content'])); ?>
                                    </p>
                                    <div class="announcement-meta">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($row['user_name'] . ' ' . $row['user_surname']); ?><br>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?><br>
                                            <?php if($row['role_name']): ?>
                                            <i class="fas fa-user-tag"></i>
                                            <?php echo htmlspecialchars($row['role_name']); ?><br>
                                            <?php endif; ?>
                                            <?php if($row['department_name']): ?>
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($row['department_name']); ?><br>
                                            <?php endif; ?>
                                            <?php if($row['course_name']): ?>
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($row['course_name']); ?><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Aktif duyuru bulunmamaktadır.
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Modal - Only for Admin -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="announcementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bullhorn"></i>
                    <?php echo isset($_GET['edit']) ? 'Duyuruyu Düzenle' : 'Yeni Duyuru'; ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="announcementForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo $announcement->id; ?>">
                    <input type="hidden" name="action" value="update">
                    <?php else: ?>
                    <input type="hidden" name="action" value="create">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">Başlık</label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="<?php echo isset($announcement->title) ? htmlspecialchars($announcement->title) : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="content">İçerik</label>
                        <textarea class="form-control" id="content" name="content" rows="5"
                            required><?php echo isset($announcement->content) ? htmlspecialchars($announcement->content) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department_id">Bölüm</label>
                                <select class="form-control" id="department_id" name="department_id">
                                    <option value="">Tüm Bölümler</option>
                                    <?php while($row = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo (isset($announcement->department_id) && $announcement->department_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="course_id">Ders</label>
                                <select class="form-control" id="course_id" name="course_id">
                                    <option value="">Tüm Dersler</option>
                                    <?php if(isset($announcement->course_id)): ?>
                                    <?php 
                                    // For edit mode, we need to show the currently selected course
                                    $course = new Course($db);
                                    $course->id = $announcement->course_id;
                                    if($course->readOne()):
                                    ?>
                                    <option value="<?php echo $course->id; ?>" selected>
                                        <?php echo $course->code . ' - ' . $course->name; ?>
                                    </option>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Başlangıç Tarihi</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date"
                                    value="<?php echo isset($announcement->start_date) ? date('Y-m-d\TH:i', strtotime($announcement->start_date)) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">Bitiş Tarihi</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date"
                                    value="<?php echo isset($announcement->end_date) ? date('Y-m-d\TH:i', strtotime($announcement->end_date)) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['edit'])): ?>
                    <div class="form-group" id="statusField">
                        <label for="status">Durum</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active"
                                <?php echo (isset($announcement->status) && $announcement->status == 'active') ? 'selected' : ''; ?>>
                                Aktif</option>
                            <option value="inactive"
                                <?php echo (isset($announcement->status) && $announcement->status == 'inactive') ? 'selected' : ''; ?>>
                                Pasif</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo isset($_GET['edit']) ? 'Güncelle' : 'Oluştur'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($isAdmin): ?>
    // Form validation
    const form = document.getElementById('announcementForm');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const newAnnouncementBtn = document.getElementById('newAnnouncementBtn');

    // Course selection based on department
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');

    // Create object with courses grouped by department
    var coursesByDepartment = <?php echo json_encode($courses_by_dept); ?>;

    // Function to update course dropdown based on selected department
    function updateCourseDropdown() {
        const departmentId = departmentSelect.value;

        // Clear and reset course dropdown
        courseSelect.innerHTML = '<option value="">Tüm Dersler</option>';

        // If a department is selected, populate with courses from that department
        if (departmentId && coursesByDepartment[departmentId]) {
            coursesByDepartment[departmentId].forEach(function(course) {
                const option = document.createElement('option');
                option.value = course.id;
                option.textContent = course.code + ' - ' + course.name;
                courseSelect.appendChild(option);
            });
        }

        // If editing and there's a selected course, try to restore it
        <?php if (isset($_GET['edit']) && isset($announcement->course_id)): ?>
        const selectedCourseId = '<?php echo $announcement->course_id; ?>';
        for (let i = 0; i < courseSelect.options.length; i++) {
            if (courseSelect.options[i].value === selectedCourseId) {
                courseSelect.options[i].selected = true;
                break;
            }
        }
        <?php endif; ?>
    }

    // Wire up department select change event
    departmentSelect.addEventListener('change', updateCourseDropdown);

    // Initialize course dropdown
    updateCourseDropdown();

    // Handle new announcement button click
    newAnnouncementBtn.addEventListener('click', function() {
        // Clear any edit parameters from URL
        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Reset form fields
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        document.getElementById('department_id').value = '';
        document.getElementById('course_id').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';

        // Update course dropdown based on empty department
        updateCourseDropdown();

        // Set action to create
        document.querySelector('input[name="action"]').value = 'create';

        // Remove id field if it exists
        const idField = document.querySelector('input[name="id"]');
        if (idField) {
            idField.remove();
        }

        // Remove status field if it exists
        const statusField = document.querySelector('#statusField');
        if (statusField) {
            statusField.remove();
        }

        // Update modal title
        document.querySelector('.modal-title').innerHTML =
            '<i class="fas fa-bullhorn"></i> Yeni Duyuru';

        // Update submit button text
        document.querySelector('button[type="submit"]').textContent = 'Oluştur';

        // Show modal
        $('#announcementModal').modal('show');
    });

    form.addEventListener('submit', function(e) {
        if (new Date(startDate.value) > new Date(endDate.value)) {
            alert('Bitiş tarihi başlangıç tarihinden önce olamaz!');
            e.preventDefault();
        }
    });

    // Auto-open modal if edit parameter is present
    <?php if (isset($_GET['edit'])): ?>
    $('#announcementModal').modal('show');
    <?php endif; ?>
    <?php endif; ?>
});
</script>

<?php // İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';; ?>