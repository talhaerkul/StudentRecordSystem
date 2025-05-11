<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../controllers/TranscriptController.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_ADMIN) {
    header("Location: " . url('/pages/auth/login.php'));
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create TranscriptController instance
$transcriptController = new TranscriptController();

// Get student ID from URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    $_SESSION['alert'] = "Öğrenci ID'si belirtilmedi.";
    $_SESSION['alert_type'] = "danger";
    header("Location: " . url('/pages/transcript/admin_transcripts.php'));
    exit;
}

$student_id = intval($_GET['student_id']);

// Create User model instance
$user = new User($db);
$user->id = $student_id;

// Check if student exists
if (!$user->readOne() || $user->role_id != ROLE_STUDENT) {
    $_SESSION['alert'] = "Öğrenci bulunamadı veya geçersiz öğrenci ID'si.";
    $_SESSION['alert_type'] = "danger";
    header("Location: " . url('/pages/transcript/admin_transcripts.php'));
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $success = true;
    $message = "Notlar başarıyla güncellendi.";
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        foreach ($_POST['grades'] as $course_id => $course_data) {
            foreach ($course_data as $term_id => $grade) {
                $term_id = intval($term_id);
                $course_id = intval($course_id);
                
                // Handle empty grades
                if (empty($grade) || $grade === '') {
                    // Update with NULL grade (enrolled status)
                    $query = "UPDATE student_courses 
                              SET grade = NULL, 
                                  status = 'enrolled'
                              WHERE student_id = ? AND course_id = ? AND term_id = ?";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $student_id);
                    $stmt->bindParam(2, $course_id);
                    $stmt->bindParam(3, $term_id);
                } else {
                    // Convert to float and update with grade value
                    $grade_value = floatval($grade);
                    
                    // Determine status based on grade
                    $status = ($grade_value < 60) ? 'failed' : 'completed';
                    
                    $query = "UPDATE student_courses 
                              SET grade = ?, 
                                  status = ?
                              WHERE student_id = ? AND course_id = ? AND term_id = ?";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $grade_value);
                    $stmt->bindParam(2, $status);
                    $stmt->bindParam(3, $student_id);
                    $stmt->bindParam(4, $course_id);
                    $stmt->bindParam(5, $term_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Not güncellenirken hata oluştu.");
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['alert'] = $message;
        $_SESSION['alert_type'] = "success";
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollBack();
        
        $_SESSION['alert'] = $e->getMessage();
        $_SESSION['alert_type'] = "danger";
    }
    
    // Redirect to avoid form resubmission
    header("Location: " . url('/pages/transcript/edit_transcript.php?student_id=' . $student_id));
    exit;
}

// Get student courses with grades
$query = "SELECT sc.*, c.name as course_name, c.code, c.credit, 
          t.name as term_name, t.start_date, t.end_date
          FROM student_courses sc
          JOIN courses c ON sc.course_id = c.id
          JOIN terms t ON sc.term_id = t.id
          WHERE sc.student_id = ?
          ORDER BY t.start_date ASC, c.code ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $student_id);
$stmt->execute();

// Set page title
$page_title = $user->name . ' ' . $user->surname . ' - Transkript Düzenleme';

// İçerik oluştur
ob_start();
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-gray-800 font-bold">
                <i class="fas fa-edit mr-2 text-indigo-600"></i> Transkript Düzenleme
            </h2>
            <p class="text-gray-600">
                <strong><?php echo $user->name . ' ' . $user->surname; ?></strong>
                (<?php echo $user->student_id; ?>) adlı öğrencinin transkriptini düzenleyin.
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo url('/pages/transcript/admin_transcripts.php'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Listeye Dön
            </a>
            <a href="<?php echo url('/pages/transcript/transcript.php?student_id=' . $student_id); ?>"
                class="btn btn-info">
                <i class="fas fa-file-alt mr-1"></i> Transkripti Görüntüle
            </a>
        </div>
    </div>

    <div class="card shadow-lg rounded-lg overflow-hidden">
        <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
            <h4 class="mb-0 font-bold">Not Düzenleme</h4>
        </div>

        <div class="card-body p-4">
            <form method="POST"
                action="<?php echo url('/pages/transcript/edit_transcript.php?student_id=' . $student_id); ?>">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-gray-100">
                            <tr>
                                <th>Dönem</th>
                                <th>Ders Kodu</th>
                                <th>Ders Adı</th>
                                <th>Kredi</th>
                                <th>Not</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_term = '';
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                
                                // Display term header if new term
                                if ($current_term != $row['term_name']):
                                    $current_term = $row['term_name'];
                                    echo '<tr class="bg-gray-200">';
                                    echo '<td colspan="6" class="font-semibold">' . $current_term . '</td>';
                                    echo '</tr>';
                                endif;
                            ?>
                            <tr>
                                <td><?php echo $row['term_name']; ?></td>
                                <td><?php echo $row['code']; ?></td>
                                <td><?php echo $row['course_name']; ?></td>
                                <td><?php echo $row['credit']; ?></td>
                                <td>
                                    <input type="number" min="0" max="100" step="0.01"
                                        name="grades[<?php echo $row['course_id']; ?>][<?php echo $row['term_id']; ?>]"
                                        value="<?php echo $row['grade']; ?>" class="form-control">
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo ($row['status'] == 'completed') ? 'success' : 
                                            (($row['status'] == 'failed') ? 'danger' : 
                                                (($row['status'] == 'enrolled') ? 'info' : 'warning')); 
                                    ?>">
                                        <?php 
                                            echo ($row['status'] == 'completed') ? 'Tamamlandı' : 
                                                (($row['status'] == 'failed') ? 'Başarısız' : 
                                                    (($row['status'] == 'enrolled') ? 'Devam Ediyor' : 'Bırakıldı')); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-right">
                    <button type="submit" name="update_grades" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>