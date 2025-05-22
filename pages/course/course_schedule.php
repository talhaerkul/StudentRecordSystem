<?php

// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/CourseSchedule.php';
require_once '../../models/Course.php';
require_once '../../models/Term.php';
require_once '../../models/Department.php';
require_once '../../models/User.php';
require_once '../../config/database.php';


// Require login
requireLogin();

// Check permissions - only admin, teachers and students can access
if (!in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER, ROLE_STUDENT])) {
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();


// Initialize objects
$schedule = new CourseSchedule($db);
$course = new Course($db);
$term = new Term($db);
$department = new Department($db);
$user = new User($db);

// Get current term
$term->getCurrentTerm();
$current_term_id = $term->id ?? null;

// Get selected term (default to current)
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : $current_term_id;

// Get selected department (if provided or from user's department)
$selected_department_id = isset($_GET['department_id']) ? $_GET['department_id'] : 
                         (isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null);

// Get selected year/class level (if provided)
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;

// Get all terms for filter
$all_terms = $term->readActive();

// Get all departments for filter
$all_departments = $department->readAll();


// Get all teachers for admin to assign courses
$teachers = [];
if (in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER])) {
    // If teacher, only show themselves in dropdown
    if ($_SESSION['role'] == ROLE_TEACHER) {
        $teachers_result = $user->readById($_SESSION['user_id']);
        if ($teacher = $teachers_result->fetch(PDO::FETCH_ASSOC)) {
            $teachers[] = $teacher;
        }
    } else {
        // Admin can see all teachers
        $teachers_result = $user->readByRole(ROLE_TEACHER);
        while ($teacher = $teachers_result->fetch(PDO::FETCH_ASSOC)) {
            $teachers[] = $teacher;
        }
    }
}

// Get all courses for admin/teachers to assign
$courses = [];
if (in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER])) {
    // If teacher, only show courses from their department
    if ($_SESSION['role'] == ROLE_TEACHER && isset($_SESSION['department_id'])) {
        $courses_result = $course->readActiveByDepartment($_SESSION['department_id']);
    } else {
        $courses_result = $selected_department_id ? $course->readActiveByDepartment($selected_department_id) : $course->readActive();
    }
    
    while ($course_item = $courses_result->fetch(PDO::FETCH_ASSOC)) {
        $courses[] = $course_item;
    }
}

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admin and teachers can modify schedules
    if (!in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER])) {
        $error = "Bu işlem için yetkiniz bulunmamaktadır.";
    } else {
        // Add new schedule
        if (isset($_POST['add_schedule'])) {
           
            // Check if teacher can only add schedules for themselves
            if ($_SESSION['role'] == ROLE_TEACHER && $_SESSION['user_id'] != $_POST['teacher_id']) {
                $error = "Öğretmenler sadece kendileri için ders programı ekleyebilir.";
            } else {
                // Set schedule properties
                $schedule->course_id = $_POST['course_id'];
                $schedule->teacher_id = $_POST['teacher_id'];
                $schedule->term_id = $_POST['term_id'];
                $schedule->day_of_week = $_POST['day_of_week'];
                $schedule->start_time = $_POST['start_time'];
                $schedule->end_time = $_POST['end_time'];
                $schedule->classroom = $_POST['classroom'];
                
                // Check for conflicts
                if ($schedule->hasConflict()) {
                    $error = "Çakışma tespit edildi! Aynı zamanda aynı sınıfta başka bir ders bulunuyor veya öğretmen başka bir derste görevli.";
                } else {
                    try {
                        if ($schedule->create()) {
                            $message = "Ders programı başarıyla eklendi.";
                            
                            // Clean redirect to refresh the page
                            header("Location: " . url('/pages/course/course_schedule.php') . "?term_id=" . $selected_term_id . 
                                  ($selected_department_id ? "&department_id=" . $selected_department_id : "") . 
                                  "&success=1");
                            exit;
                        } else {
                            $error = "Ders programı eklenirken bir hata oluştu.";
                        }
                    } catch (Exception $e) {
                        $error = "Ders programı eklenirken bir hata oluştu: " . $e->getMessage();
                    }
                }
            }
        }
        
        // Update existing schedule
        else if (isset($_POST['update_schedule'])) {
           
            
            // Check if teacher can only update schedules for themselves
            if ($_SESSION['role'] == ROLE_TEACHER && $_SESSION['user_id'] != $_POST['teacher_id']) {
                $error = "Öğretmenler sadece kendileri için ders programını güncelleyebilir.";
            } else {
                $schedule->id = $_POST['schedule_id'];
                
                // For teachers, verify they own this schedule before updating
                if ($_SESSION['role'] == ROLE_TEACHER) {
                    $current_schedule = $schedule->readOne($_POST['schedule_id']);
                    if ($current_schedule && $current_schedule['teacher_id'] != $_SESSION['user_id']) {
                        $error = "Bu ders programını düzenleme yetkiniz bulunmamaktadır.";
                        // Skip the rest of the update process
                        goto skipUpdate;
                    }
                }
                
                $schedule->course_id = $_POST['course_id'];
                $schedule->teacher_id = $_POST['teacher_id'];
                $schedule->term_id = $_POST['term_id'];
                $schedule->day_of_week = $_POST['day_of_week'];
                $schedule->start_time = $_POST['start_time'];
                $schedule->end_time = $_POST['end_time'];
                $schedule->classroom = $_POST['classroom'];
                
                // Check for conflicts
                if ($schedule->hasConflict()) {
                    $error = "Çakışma tespit edildi! Aynı zamanda aynı sınıfta başka bir ders bulunuyor veya öğretmen başka bir derste görevli.";
                } else {
                    if ($schedule->update()) {
                        $message = "Ders programı başarıyla güncellendi.";
                        // Clean redirect
                        header("Location: " . url('/pages/course/course_schedule.php') . "?term_id=" . $selected_term_id . 
                              ($selected_department_id ? "&department_id=" . $selected_department_id : "") . 
                              "&success=2");
                        exit;
                    } else {
                        $error = "Ders programı güncellenirken bir hata oluştu.";
                    }
                }
            }
            
            skipUpdate: // Label for skipping update process if validation fails
        }
        
        // Delete schedule
        else if (isset($_POST['delete_schedule'])) {
            $schedule->id = $_POST['schedule_id'];
            
            // For teachers, verify they own this schedule before deleting
            if ($_SESSION['role'] == ROLE_TEACHER) {
                $current_schedule = $schedule->readOne($_POST['schedule_id']);
                if ($current_schedule && $current_schedule['teacher_id'] != $_SESSION['user_id']) {
                    $error = "Bu ders programını silme yetkiniz bulunmamaktadır.";
                    goto skipDelete;
                }
            }
            
            if ($schedule->delete()) {
                $message = "Ders programı başarıyla silindi.";
                // Clean redirect
                header("Location: " . url('/pages/course/course_schedule.php') . "?term_id=" . $selected_term_id . 
                      ($selected_department_id ? "&department_id=" . $selected_department_id : "") . 
                      "&success=3");
                exit;
            } else {
                $error = "Ders programı silinirken bir hata oluştu.";
            }
            
            skipDelete: // Label for skipping delete process if validation fails
        }
    }
}

// Check for success messages from redirects
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case '1':
            $message = "Ders programı başarıyla eklendi.";
            break;
        case '2':
            $message = "Ders programı başarıyla güncellendi.";
            break;
        case '3':
            $message = "Ders programı başarıyla silindi.";
            break;
    }
}

// Get schedules based on user role and filters
$schedules = [];
if ($selected_term_id) {
    if ($_SESSION['role'] == ROLE_ADMIN) {
        // Admin can see all schedules, filtered by department and year if selected
        if ($selected_department_id && $selected_year) {
            $result = $schedule->readByYearAndDepartmentAndTerm($selected_year, $selected_department_id, $selected_term_id);
        } else if ($selected_department_id) {
            $result = $schedule->readByDepartmentAndTerm($selected_department_id, $selected_term_id);
        } else if ($selected_year) {
            $result = $schedule->readByYearAndTerm($selected_year, $selected_term_id);
        } else {
            $result = $schedule->readByTerm($selected_term_id);
        }
    } 
    else if ($_SESSION['role'] == ROLE_TEACHER) {
        // Teachers see their own schedule
        $result = $schedule->readByTeacherAndTerm($_SESSION['user_id'], $selected_term_id);
    }
    else if ($_SESSION['role'] == ROLE_STUDENT) {
        // Students see schedules for courses they're enrolled in
        $result = $schedule->readByStudentAndTerm($_SESSION['user_id'], $selected_term_id);
    }
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $schedules[] = $row;
    }
}

// Turkish day names for display
$day_names = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma'
];

// Organize schedules by day for better display
$schedule_by_day = [];
foreach ($schedules as $s) {
    $day = $s['day_of_week'];
    if (!isset($schedule_by_day[$day])) {
        $schedule_by_day[$day] = [];
    }
    $schedule_by_day[$day][] = $s;
}

// Sort each day's schedules by start time
foreach ($schedule_by_day as $day => $day_schedules) {
    usort($day_schedules, function($a, $b) {
        return strcmp($a['start_time'], $b['start_time']);
    });
    $schedule_by_day[$day] = $day_schedules;
}

// Set page title
$page_title = 'Ders Programı';

// Create page content
ob_start();
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-0">
                <div
                    class="card-header d-flex justify-content-between align-items-center bg-gradient-primary text-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt mr-2"></i> Ders Programı
                    </h5>
                </div>
                <div class="card-body p-0">
                    <!-- Alert Messages -->
                    <div class="p-4">
                        <?php if($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Filters -->
                        <form method="get" id="filterForm" class="row g-3 mb-4 p-4 bg-light rounded">
                            <div class="col-md-4">
                                <label for="term_id" class="form-label fw-bold">
                                    <i class="fas fa-clock mr-1"></i> Dönem
                                </label>
                                <select name="term_id" id="term_id" class="form-control form-control-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <?php while($row = $all_terms->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo ($selected_term_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo $row['name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <?php if(in_array($_SESSION['role'], [ROLE_ADMIN])): ?>
                            <div class="col-md-4">
                                <label for="department_id" class="form-label fw-bold">
                                    <i class="fas fa-building mr-1"></i> Bölüm
                                </label>
                                <select name="department_id" id="department_id"
                                    class="form-control form-control-sm shadow-sm" onchange="this.form.submit()">
                                    <option value="">Tüm Bölümler</option>
                                    <?php while($row = $all_departments->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo ($selected_department_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo $row['name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-12 d-flex align-items-end justify-content-end">
                                <?php if(in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER]) && $selected_term_id): ?>
                                <button type="button" class="btn btn-success shadow-sm" data-toggle="modal"
                                    data-target="#addScheduleModal">
                                    <i class="fas fa-plus mr-2"></i> Yeni Ders Ekle
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Schedule Content -->
                        <div class="content-container pb-4">
                            <!-- Schedule Display -->
                            <?php if(!empty($schedules)): ?>
                            <!-- Weekly calendar view -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="mb-3">
                                        <i class="fas fa-calendar-week mr-2"></i>
                                        <span class="border-bottom border-primary pb-1">Haftalık Görünüm</span>
                                    </h5>

                                    <div class="table-responsive shadow rounded">
                                        <table class="table table-bordered schedule-table">
                                            <thead>
                                                <tr class="bg-light">
                                                    <th style="width: 10%;" class="text-center align-middle">Saat \ Gün
                                                    </th>
                                                    <?php foreach($day_names as $day_id => $day_name): ?>
                                                    <?php if($day_id >= 1 && $day_id <= 5): // Pazartesi-Cuma arası göster ?>
                                                    <th style="width: <?php echo 90/5 ?>%;"
                                                        class="text-center align-middle bg-gradient-day-<?php echo $day_id; ?>">
                                                        <div class="fw-bold"><i class="fas fa-calendar-day mr-1"></i>
                                                            <?php echo $day_name; ?></div>
                                                    </th>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                // Define time slots (8:00 to 18:00 with 2-hour intervals)
                                                $time_slots = [
                                                    '08:00' => '10:00',
                                                    '10:00' => '12:00',
                                                    '12:00' => '14:00',
                                                    '14:00' => '16:00',
                                                    '16:00' => '18:00',
                                                    '18:00' => '20:00',
                                                ];
                                                
                                                foreach($time_slots as $start => $end): 
                                                ?>
                                                <tr>
                                                    <td class="text-center bg-light align-middle">
                                                        <i class="far fa-clock mr-1"></i>
                                                        <strong><?php echo $start; ?></strong>
                                                        <br>―<br>
                                                        <strong><?php echo $end; ?></strong>
                                                    </td>

                                                    <?php foreach($day_names as $day_id => $day_name): ?>
                                                    <?php if($day_id >= 1 && $day_id <= 5): // Pazartesi-Cuma arası göster ?>
                                                    <td class="position-relative p-1">
                                                        <?php 
                                                            if(isset($schedule_by_day[$day_id])) {
                                                                // Find classes that fall within this time slot
                                                                $classes_in_slot = array_filter($schedule_by_day[$day_id], function($class) use ($start, $end) {
                                                                    $class_start = date('H:i', strtotime($class['start_time']));
                                                                    $class_end = date('H:i', strtotime($class['end_time']));
                                                                    
                                                                    // Check if class overlaps with this time slot
                                                                    return 
                                                                        // Class starts during the slot
                                                                        ($class_start >= $start && $class_start < $end) || 
                                                                        // Class ends during the slot
                                                                        ($class_end > $start && $class_end <= $end) ||
                                                                        // Class spans the entire slot
                                                                        ($class_start <= $start && $class_end >= $end);
                                                                });
                                                                
                                                                foreach($classes_in_slot as $class):
                                                                    // Generate a unique color for each course
                                                                    $course_colors = [
                                                                        'rgba(41, 128, 185, 0.15)', // blue
                                                                        'rgba(39, 174, 96, 0.15)',  // green
                                                                        'rgba(142, 68, 173, 0.15)', // purple
                                                                        'rgba(230, 126, 34, 0.15)', // orange
                                                                        'rgba(231, 76, 60, 0.15)',  // red
                                                                        'rgba(52, 73, 94, 0.15)',   // dark blue
                                                                        'rgba(22, 160, 133, 0.15)', // teal
                                                                        'rgba(241, 196, 15, 0.15)', // yellow
                                                                    ];
                                                                    
                                                                    $course_id = $class['course_id'];
                                                                    $color_index = $course_id % count($course_colors);
                                                                    $bg_color = $course_colors[$color_index];
                                                                    
                                                                    // Darken color for border
                                                                    $border_color = str_replace('0.15', '0.7', $bg_color);
                                                                ?>
                                                        <div class="schedule-item p-2 mb-1 rounded shadow-sm"
                                                            style="background-color: <?php echo $bg_color; ?>; border-left: 4px solid <?php echo $border_color; ?>;">
                                                            <div class="fw-bold text-dark">
                                                                <?php echo $class['course_code']; ?>
                                                            </div>
                                                            <div class="small text-muted">
                                                                <i class="far fa-clock mr-1"></i>
                                                                <?php echo date('H:i', strtotime($class['start_time'])) . ' - ' . date('H:i', strtotime($class['end_time'])); ?>
                                                            </div>
                                                            <div class="font-weight-medium">
                                                                <?php echo $class['course_name']; ?></div>
                                                            <div class="small mt-1">
                                                                <i class="fas fa-user-tie mr-1 text-secondary"></i>
                                                                <?php echo $class['teacher_name']; ?>
                                                            </div>
                                                            <div class="small">
                                                                <i
                                                                    class="fas fa-map-marker-alt mr-1 text-secondary"></i>
                                                                <?php echo $class['classroom']; ?>
                                                            </div>
                                                            <?php if(in_array($_SESSION['role'], [ROLE_ADMIN]) || 
                                                                      ($_SESSION['role'] == ROLE_TEACHER && $class['teacher_id'] == $_SESSION['user_id'])): ?>
                                                            <div class="mt-2 text-center border-top pt-2">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary btn-edit-schedule mr-1"
                                                                    data-id="<?php echo $class['id']; ?>"
                                                                    data-course-id="<?php echo $class['course_id']; ?>"
                                                                    data-teacher-id="<?php echo $class['teacher_id']; ?>"
                                                                    data-day="<?php echo $class['day_of_week']; ?>"
                                                                    data-start="<?php echo $class['start_time']; ?>"
                                                                    data-end="<?php echo $class['end_time']; ?>"
                                                                    data-classroom="<?php echo $class['classroom']; ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#editScheduleModal">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger btn-delete-schedule"
                                                                    data-id="<?php echo $class['id']; ?>"
                                                                    data-course="<?php echo $class['course_code'] . ' - ' . $class['course_name']; ?>"
                                                                    data-toggle="modal"
                                                                    data-target="#deleteScheduleModal">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; 
                                                            } // end if isset schedule_by_day
                                                        ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <?php else: ?>
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?php if(!$selected_term_id): ?>
                                Lütfen bir dönem seçiniz.
                                <?php else: ?>
                                Bu dönem için kayıtlı ders programı bulunamadı.
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Schedule Modal -->
<?php if(in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER]) && $selected_term_id): ?>
<div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addScheduleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addScheduleModalLabel"><i class="fas fa-plus-circle mr-2"></i> Yeni Ders
                    Programı Ekle</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="addScheduleForm"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?term_id=' . $selected_term_id . ($selected_department_id ? '&department_id=' . $selected_department_id : ''); ?>">
                <div class="modal-body">
                    <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <input type="hidden" name="teacher_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-<?php echo ($_SESSION['role'] == ROLE_TEACHER) ? '12' : '6'; ?>">
                            <label for="course_id" class="form-label">Ders <span class="text-danger">*</span></label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">Ders Seçiniz</option>
                                <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo $c['code'] . ' - ' . $c['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if($_SESSION['role'] != ROLE_TEACHER): ?>
                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label">Öğretmen <span
                                    class="text-danger">*</span></label>
                            <select name="teacher_id" id="teacher_id" class="form-control" required>
                                <option value="">Öğretmen Seçiniz</option>
                                <?php foreach($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo $t['name'] . ' ' . $t['surname']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="day_of_week" class="form-label">Gün <span class="text-danger">*</span></label>
                            <select name="day_of_week" id="day_of_week" class="form-control" required>
                                <?php foreach($day_names as $day_id => $day_name): ?>
                                <option value="<?php echo $day_id; ?>"><?php echo $day_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="start_time" class="form-label">Başlangıç Saati <span
                                    class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label for="end_time" class="form-label">Bitiş Saati <span
                                    class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="classroom" class="form-label">Sınıf <span class="text-danger">*</span></label>
                            <input type="text" name="classroom" id="classroom" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" name="add_schedule" class="btn btn-success" id="addScheduleBtn">
                        <i class="fas fa-save mr-2"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Schedule Modal -->
<?php if(in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_TEACHER])): ?>
<div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editScheduleModalLabel"><i class="fas fa-edit mr-2"></i> Ders Programı
                    Düzenle</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="editScheduleForm"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?term_id=' . $selected_term_id . ($selected_department_id ? '&department_id=' . $selected_department_id : ''); ?>">
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <input type="hidden" name="term_id" value="<?php echo $selected_term_id; ?>">
                    <?php if($_SESSION['role'] == ROLE_TEACHER): ?>
                    <input type="hidden" name="teacher_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-<?php echo ($_SESSION['role'] == ROLE_TEACHER) ? '12' : '6'; ?>">
                            <label for="edit_course_id" class="form-label">Ders</label>
                            <select name="course_id" id="edit_course_id" class="form-control" required>
                                <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo $c['code'] . ' - ' . $c['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if($_SESSION['role'] != ROLE_TEACHER): ?>
                        <div class="col-md-6">
                            <label for="edit_teacher_id" class="form-label">Öğretmen</label>
                            <select name="teacher_id" id="edit_teacher_id" class="form-control" required>
                                <?php foreach($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo $t['name'] . ' ' . $t['surname']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_day_of_week" class="form-label">Gün</label>
                            <select name="day_of_week" id="edit_day_of_week" class="form-control" required>
                                <?php foreach($day_names as $day_id => $day_name): ?>
                                <option value="<?php echo $day_id; ?>"><?php echo $day_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="edit_start_time" class="form-label">Başlangıç Saati</label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label for="edit_end_time" class="form-label">Bitiş Saati</label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                        </div>

                        <div class="col-md-2">
                            <label for="edit_classroom" class="form-label">Sınıf</label>
                            <input type="text" name="classroom" id="edit_classroom" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" name="update_schedule" class="btn btn-primary" id="updateScheduleBtn">
                        <i class="fas fa-save mr-2"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Schedule Modal -->
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" role="dialog" aria-labelledby="deleteScheduleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteScheduleModalLabel"><i class="fas fa-trash-alt mr-2"></i> Ders
                    Programı Sil</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="deleteScheduleForm"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?term_id=' . $selected_term_id . ($selected_department_id ? '&department_id=' . $selected_department_id : ''); ?>">
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="delete_schedule_id">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Bu ders programını silmek istediğinize emin misiniz?
                    </div>
                    <p><strong id="delete_course_name"></strong></p>
                    <p class="text-danger"><small><i class="fas fa-info-circle mr-1"></i> Bu işlem geri
                            alınamaz!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" name="delete_schedule" class="btn btn-danger" id="deleteScheduleBtn">
                        <i class="fas fa-trash mr-2"></i> Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom CSS for schedule display -->
<style>
.schedule-table th,
.schedule-table td {
    padding: 0.5rem;
}

.schedule-table td {
    height: 130px;
    vertical-align: top;
}

.schedule-item {
    font-size: 0.85rem;
    transition: all 0.3s;
    border-radius: 6px;
}

.schedule-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
}

/* Day gradient backgrounds */
.bg-gradient-day-1 {
    background: linear-gradient(180deg, rgba(52, 152, 219, 0.15) 0%, rgba(52, 152, 219, 0.05) 100%);
}

.bg-gradient-day-2 {
    background: linear-gradient(180deg, rgba(46, 204, 113, 0.15) 0%, rgba(46, 204, 113, 0.05) 100%);
}

.bg-gradient-day-3 {
    background: linear-gradient(180deg, rgba(155, 89, 182, 0.15) 0%, rgba(155, 89, 182, 0.05) 100%);
}

.bg-gradient-day-4 {
    background: linear-gradient(180deg, rgba(241, 196, 15, 0.15) 0%, rgba(241, 196, 15, 0.05) 100%);
}

.bg-gradient-day-5 {
    background: linear-gradient(180deg, rgba(231, 76, 60, 0.15) 0%, rgba(231, 76, 60, 0.05) 100%);
}

/* Card and button styles */
.card {
    border-radius: 0.5rem;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
}

.btn {
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-outline-primary:hover,
.btn-outline-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Modal styling */
.modal-content {
    border-radius: 0.5rem;
    overflow: hidden;
}

.modal-header {
    border-bottom: none;
}

.modal-footer {
    border-top: none;
}

/* Filter form styling */
#filterForm {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-control {
    border-radius: 0.25rem;
}

/* Accordion styling */
.accordion-button:not(.collapsed) {
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0, 0, 0, 0.125);
}
</style>

<?php
// Add this JavaScript to the additional_js variable so it gets placed after jQuery and Bootstrap are loaded

// Group teachers by department
$teachers_by_dept = [];
foreach($teachers as $t) {
    if (!isset($teachers_by_dept[$t['department_id']])) {
        $teachers_by_dept[$t['department_id']] = [];
    }
    $teachers_by_dept[$t['department_id']][] = $t;
}

// Build JavaScript objects as strings
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

$additional_js = <<<EOT
<script>
$(document).ready(function() {
    console.log('Document ready');
    
    // Edit schedule modal logic
    $('.btn-edit-schedule').on('click', function() {
        console.log('Edit button clicked');
        var id = $(this).data('id');
        var courseId = $(this).data('course-id');
        var teacherId = $(this).data('teacher-id');
        var day = $(this).data('day');
        var start = $(this).data('start');
        var end = $(this).data('end');
        var classroom = $(this).data('classroom');
        
        console.log('Setting form values:', {
            id: id,
            courseId: courseId,
            teacherId: teacherId,
            day: day,
            start: start,
            end: end,
            classroom: classroom
        });
        
        $('#edit_schedule_id').val(id);
        $('#edit_course_id').val(courseId);
        
        // Only set teacher_id if the select exists (admin)
        if ($('#edit_teacher_id').length) {
            $('#edit_teacher_id').val(teacherId);
        }
        
        $('#edit_day_of_week').val(day);
        $('#edit_start_time').val(start);
        $('#edit_end_time').val(end);
        $('#edit_classroom').val(classroom);
    });
    
    // Delete schedule modal logic
    $('.btn-delete-schedule').on('click', function() {
        console.log('Delete button clicked');
        var id = $(this).data('id');
        var course = $(this).data('course');
        
        console.log('Setting delete values:', {
            id: id,
            course: course
        });
        
        $('#delete_schedule_id').val(id);
        $('#delete_course_name').text(course);
    });
    
    // Add Schedule form validation
    $('#addScheduleForm').on('submit', function(e) {
        var startTime = $('#start_time').val();
        var endTime = $('#end_time').val();
        
        console.log('Validating form - Start time: ' + startTime + ', End time: ' + endTime);
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('Başlangıç saati bitiş saatinden önce olmalıdır!');
            return false;
        }
        
        return true;
    });
    
    // Link the "Yeni Ders Ekle" button to the modal
    $('.btn-add-schedule').on('click', function() {
        $('#addScheduleModal').modal('show');
    });
    
    // Debug output of modals
    console.log('Available modals:');
    $('.modal').each(function() {
        console.log(' - Modal ID: ' + $(this).attr('id'));
    });
    
    // Count buttons
    console.log('Edit buttons count: ' + $('.btn-edit-schedule').length);
    console.log('Delete buttons count: ' + $('.btn-delete-schedule').length);
    
    // Create object with teachers grouped by department
    $teachers_js
    
    // Create object with course department mapping
    $courses_js
    
    // Function to update teacher dropdown based on selected course
    function updateTeacherDropdown(courseSelect, teacherSelect) {
        // Skip if teacher select doesn't exist (teacher role)
        if (!$(teacherSelect).length) {
            return;
        }
        
        var courseId = $(courseSelect).val();
        var departmentId = courseDepartments[courseId];
        
        console.log('Selected course ID:', courseId);
        console.log('Course department ID:', departmentId);
        
        // Clear and disable teacher dropdown if no course selected
        if (!courseId) {
            $(teacherSelect).empty().append('<option value="">Öğretmen Seçiniz</option>').prop('disabled', true);
            return;
        }
        
        // Get teachers for this department
        var teachers = teachersByDepartment[departmentId] || [];
        console.log('Available teachers:', teachers.length);
        
        // Update teacher dropdown
        $(teacherSelect).empty().prop('disabled', false);
        $(teacherSelect).append('<option value="">Öğretmen Seçiniz</option>');
        
        $.each(teachers, function(i, teacher) {
            $(teacherSelect).append('<option value="' + teacher.id + '">' + teacher.name + '</option>');
        });
    }
    
    // Wire up course select change events
    $('#course_id').on('change', function() {
        updateTeacherDropdown('#course_id', '#teacher_id');
    });
    
    $('#edit_course_id').on('change', function() {
        updateTeacherDropdown('#edit_course_id', '#edit_teacher_id');
    });
    
    // Initialize teacher dropdowns
    updateTeacherDropdown('#course_id', '#teacher_id');
});
</script>
EOT;

$content = ob_get_clean();

// Load the main layout template
include '../../includes/layout.php';
?>