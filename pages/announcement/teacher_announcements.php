<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection and include the announcement model
require_once '../../config/database.php';
require_once '../../models/Announcement.php';
require_once '../../includes/auth_check.php';
require_once '../../models/Role.php';
require_once '../../models/Department.php';
require_once '../../models/Course.php';
require_once '../../service/announcement_service.php';

// Start the session
requireLogin();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_TEACHER) {
    header('Location: /pages/dashboard.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get teacher ID and department_id
$teacher_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_dept = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

// Instantiate database and service
$database = new Database();
$db = $database->getConnection();
$announcementService = new AnnouncementService($db);

// Get roles, departments, and courses for dropdowns
$roles = $announcementService->getRoles();
$departments = $announcementService->getDepartments();

// Get courses for the teacher's department only
if ($user_dept) {
    $course = new Course($db);
    $courses = $course->readByDepartment($user_dept);
} else {
    // If teacher doesn't have a department, get all courses
    $courses = $announcementService->getCourses();
}

// Prepare courses for the form and JavaScript
$courses_by_dept = [];
$teacher_courses = [];
while($course = $courses->fetch(PDO::FETCH_ASSOC)) {
    $teacher_courses[] = $course;
}

// Reset course PDO statement if needed
if ($user_dept) {
    $course = new Course($db);
    $courses = $course->readByDepartment($user_dept);
} else {
    $courses = $announcementService->getCourses();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submitted - POST data: " . print_r($_POST, true));
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['title']) && isset($_POST['content'])) {
                    $announcementData = [
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'user_id' => $teacher_id, // Always set to current teacher
                        'role_id' => null, // Set to null since role selection was removed
                        'department_id' => $user_dept, // Automatically use teacher's department
                        'course_id' => !empty($_POST['course_id']) ? $_POST['course_id'] : null,
                        'start_date' => $_POST['start_date'],
                        'end_date' => $_POST['end_date']
                    ];
                    
                    if ($announcementService->createAnnouncement($announcementData)) {
                        $_SESSION['alert'] = "Duyuru başarıyla oluşturuldu.";
                        $_SESSION['alert_type'] = "success";
                    } else {
                        $_SESSION['alert'] = "Duyuru oluşturulurken bir hata oluştu.";
                        $_SESSION['alert_type'] = "danger";
                    }
                }
                break;
            case 'update':
                // Update existing announcement - first check if it belongs to this teacher
                $announcement = $announcementService->getAnnouncementById($_POST['id']);
                
                // Only allow editing if this is the teacher's own announcement
                if ($announcement && $announcement->user_id == $teacher_id) {
                    $announcementData = [
                        'id' => $_POST['id'],
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'role_id' => null, // Set to null since role selection was removed
                        'department_id' => $user_dept, // Automatically use teacher's department
                        'course_id' => !empty($_POST['course_id']) ? $_POST['course_id'] : null,
                        'start_date' => $_POST['start_date'],
                        'end_date' => $_POST['end_date'],
                        'status' => isset($_POST['status']) ? $_POST['status'] : 'active' // Default to 'active' if not set
                    ];

                    if ($announcementService->updateAnnouncement($announcementData)) {
                        $_SESSION['alert'] = "Duyuru başarıyla güncellendi.";
                        $_SESSION['alert_type'] = "success";
                    } else {
                        $_SESSION['alert'] = "Duyuru güncellenirken bir hata oluştu.";
                        $_SESSION['alert_type'] = "danger";
                    }
                } else {
                    $_SESSION['alert'] = "Bu duyuruyu düzenleme yetkiniz bulunmamaktadır.";
                    $_SESSION['alert_type'] = "danger";
                }
                break;
            case 'delete':
                // Delete announcement - first check if it belongs to this teacher
                $announcement = $announcementService->getAnnouncementById($_POST['id']);
                
                // Only allow deleting if this is the teacher's own announcement
                if ($announcement && $announcement->user_id == $teacher_id) {
                    if ($announcementService->deleteAnnouncement($_POST['id'])) {
                        $_SESSION['alert'] = "Duyuru başarıyla silindi.";
                        $_SESSION['alert_type'] = "success";
                    } else {
                        $_SESSION['alert'] = "Duyuru silinirken bir hata oluştu.";
                        $_SESSION['alert_type'] = "danger";
                    }
                } else {
                    $_SESSION['alert'] = "Bu duyuruyu silme yetkiniz bulunmamaktadır.";
                    $_SESSION['alert_type'] = "danger";
                }
                break;
        }
    }

    // Redirect to prevent form resubmission
    header('Location: teacher_announcements.php');
    exit();
}

// Get announcements created by the teacher
// Öğretmenler kendi oluşturdukları tüm duyuruları görebilirler
$active_announcements = $announcementService->getAnnouncementsByCreator($teacher_id, 'active');
$inactive_announcements = $announcementService->getAnnouncementsByCreator($teacher_id, 'inactive');

// Read one announcement for editing
$announcement = null;
if (isset($_GET['edit'])) {
    $announcement = $announcementService->getAnnouncementById($_GET['edit']);
    
    // Redirect if trying to edit someone else's announcement
    if (!$announcement || $announcement->user_id != $teacher_id) {
        $_SESSION['alert'] = "Bu duyuruyu düzenleme yetkiniz bulunmamaktadır.";
        $_SESSION['alert_type'] = "danger";
        header('Location: teacher_announcements.php');
        exit();
    }
}

// İçerik oluştur
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Öğretmen Duyuruları</h5>
                    <button type="button" class="btn btn-light btn-sm" id="newAnnouncementBtn">
                        <i class="fas fa-plus"></i> Yeni Duyuru
                    </button>
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

                    <!-- Tabs for Active and Inactive Announcements -->
                    <ul class="nav nav-tabs mb-3" id="announcementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active" role="tab"
                                aria-controls="active" aria-selected="true">
                                <i class="fas fa-check-circle"></i> Aktif Duyurular
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="inactive-tab" data-toggle="tab" href="#inactive" role="tab"
                                aria-controls="inactive" aria-selected="false">
                                <i class="fas fa-times-circle"></i> Pasif Duyurular
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="announcementTabsContent">
                        <!-- Active Announcements Tab -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                            <?php 
                            // Get active announcements
                            if($active_announcements->rowCount() > 0): 
                            ?>
                            <div class="row">
                                <?php while($row = $active_announcements->fetch(PDO::FETCH_ASSOC)): ?>
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
                                        <div class="card-footer bg-light">
                                            <div class="btn-group">
                                                <a href="teacher_announcements.php?edit=<?php echo $row['id']; ?>"
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
                                <i class="fas fa-info-circle"></i> Aktif duyurunuz bulunmamaktadır.
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Inactive announcements tab -->
                        <div class="tab-pane fade" id="inactive" role="tabpanel" aria-labelledby="inactive-tab">
                            <?php 
                            // Get inactive announcements
                            if($inactive_announcements->rowCount() > 0): 
                            ?>
                            <div class="row">
                                <?php while($row = $inactive_announcements->fetch(PDO::FETCH_ASSOC)): ?>
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
                                        <div class="card-footer bg-light">
                                            <div class="btn-group">
                                                <a href="teacher_announcements.php?edit=<?php echo $row['id']; ?>"
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
                                <i class="fas fa-info-circle"></i> Pasif duyurunuz bulunmamaktadır.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
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
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="course_id">Ders</label>
                                <select class="form-control" id="course_id" name="course_id">
                                    <option value="">Tüm Dersler</option>
                                    <?php foreach($teacher_courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"
                                        <?php echo (isset($announcement->course_id) && $announcement->course_id == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('announcementForm');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const newAnnouncementBtn = document.getElementById('newAnnouncementBtn');

    // Handle new announcement button click
    newAnnouncementBtn.addEventListener('click', function() {
        // Clear any edit parameters from URL
        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Reset form fields
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        document.getElementById('course_id').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';

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
});
</script>

<?php // İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';