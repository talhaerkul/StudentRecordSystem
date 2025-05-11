<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../models/User.php';
require_once '../../config/database.php';
require_once '../../service/TranscriptService.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: " . url('/pages/auth/login.php'));
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create TranscriptService instance
$transcriptService = new TranscriptService($db);

// Get requested student ID, default to current user
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : $_SESSION['user_id'];

// Check access permissions
if (!$transcriptService->hasAccessToTranscript($_SESSION['user_id'], $_SESSION['role'], $student_id)) {
    $_SESSION['alert'] = "Bu transkripte erişim izniniz bulunmamaktadır.";
    $_SESSION['alert_type'] = "danger";
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Get student transcript
$transcript = $transcriptService->getStudentTranscript($student_id);

// If failed to get transcript, redirect to dashboard
if (!$transcript) {
    $_SESSION['alert'] = "Transkript bilgileri alınamadı.";
    $_SESSION['alert_type'] = "danger";
    header("Location: " . url('/pages/dashboard.php'));
    exit;
}

// Get student information
$user = new User($transcriptService->db);
$user->id = $student_id;
$user->readOne();

// Calculate GPA
$total_points = 0;
$total_credits = 0;
$gpa = 0;

// Set page title
$page_title = 'Öğrenci Transkript';

// İçerik oluştur
ob_start();
?>

<div class="container mt-5">
    <div class="card shadow-lg rounded-lg overflow-hidden">
        <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 font-bold"><i class="fas fa-file-alt mr-2"></i> Öğrenci Transkripti</h4>
                <button onclick="window.print()" class="btn btn-light btn-sm shadow-sm">
                    <i class="fas fa-print mr-1"></i> Yazdır
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-gray-700 font-semibold">Öğrenci Bilgileri</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="150">Adı Soyadı:</th>
                            <td class="font-semibold"><?php echo $user->name . ' ' . $user->surname; ?></td>
                        </tr>
                        <tr>
                            <th>Öğrenci No:</th>
                            <td><?php echo $user->student_id; ?></td>
                        </tr>
                        <tr>
                            <th>Bölüm:</th>
                            <td><?php echo $user->department_name; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6 text-right">
                    <img src="../../assets/avatar.png" alt="Logo" class="img-fluid"
                        style="max-height: 100px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover bg-white">
                    <thead class="bg-gray-100">
                        <tr class="bg-gray-200">
                            <th colspan="7" class="text-center">TRANSKRİPT</th>
                        </tr>
                        <tr>
                            <th>Dönem</th>
                            <th>Ders Kodu</th>
                            <th>Ders Adı</th>
                            <th>Kredi</th>
                            <th>Not</th>
                            <th>Harf Notu</th>
                            <th>Puan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_term = '';
                        $term_credits = 0;
                        $term_points = 0;
                        
                        while($row = $transcript->fetch(PDO::FETCH_ASSOC)):
                            // Calculate term GPA
                            if($current_term != $row['term_name'] && $current_term != '') {
                                $term_gpa = $term_credits > 0 ? round($term_points / $term_credits, 2) : 0;
                                echo '<tr class="bg-gray-100">';
                                echo '<td colspan="3" class="text-right font-semibold">Dönem Ortalaması:</td>';
                                echo '<td class="font-semibold">' . $term_credits . '</td>';
                                echo '<td colspan="2"></td>';
                                echo '<td class="font-semibold">' . $term_gpa . '</td>';
                                echo '</tr>';
                                echo '<tr><td colspan="7" class="p-1"></td></tr>';
                                
                                // Reset term values
                                $term_credits = 0;
                                $term_points = 0;
                            }
                            
                            // Set current term
                            $current_term = $row['term_name'];
                            
                            // Only include in GPA calculation if it has a valid grade
                            if (isset($row['grade']) && $row['grade'] !== null) {
                                $term_credits += $row['credit'];
                                $term_points += ($row['point'] * $row['credit']);
                                $total_credits += $row['credit'];
                                $total_points += ($row['point'] * $row['credit']);
                            }
                        ?>
                        <tr>
                            <td><?php echo $row['term_name']; ?></td>
                            <td><?php echo $row['code']; ?></td>
                            <td><?php echo $row['course_name']; ?></td>
                            <td><?php echo $row['credit']; ?></td>
                            <td>
                                <?php 
                                if (isset($row['grade']) && $row['grade'] !== null) {
                                    echo $row['grade']; 
                                } else {
                                    echo '<span class="badge badge-info">Devam Ediyor</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($row['letter_grade']) && $row['letter_grade'] !== 'NA') {
                                    echo $row['letter_grade']; 
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($row['point']) && $row['point'] > 0) {
                                    echo $row['point']; 
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; 
                        
                        // Display last term GPA
                        if($current_term != '') {
                            $term_gpa = $term_credits > 0 ? round($term_points / $term_credits, 2) : 0;
                            echo '<tr class="bg-gray-100">';
                            echo '<td colspan="3" class="text-right font-semibold">Dönem Ortalaması:</td>';
                            echo '<td class="font-semibold">' . $term_credits . '</td>';
                            echo '<td colspan="2"></td>';
                            echo '<td class="font-semibold">' . $term_gpa . '</td>';
                            echo '</tr>';
                        }
                        
                        // Calculate overall GPA
                        $gpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
                        ?>

                        <tr class="bg-indigo-100">
                            <td colspan="3" class="text-right font-bold">GENEL ORTALAMA:</td>
                            <td class="font-bold"><?php echo $total_credits; ?></td>
                            <td colspan="2"></td>
                            <td class="font-bold"><?php echo $gpa; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-gray-100">
                        <h5 class="mb-0 font-semibold">Not Açıklamaları</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 text-muted">
                            <i class="fas fa-info-circle"></i> Her dersin kendi not ölçeği bulunabilir. Aşağıda en son alınan derslerin not ölçekleri görüntülenmektedir.
                        </p>
                        
                        <?php
                        // Get courses with unique teacher-course combinations
                        $coursesQuery = "SELECT DISTINCT c.id, c.code, c.name, tc.teacher_id, t.id as term_id, 
                                        u.name as teacher_name, u.surname as teacher_surname, t.created_at
                                        FROM student_courses sc 
                                        JOIN courses c ON sc.course_id = c.id 
                                        JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
                                        JOIN users u ON tc.teacher_id = u.id
                                        JOIN terms t ON sc.term_id = t.id
                                        WHERE sc.student_id = ?
                                        ORDER BY t.created_at DESC, c.code ASC
                                        LIMIT 3";
                        
                        $coursesStmt = $transcriptService->db->prepare($coursesQuery);
                        $coursesStmt->bindParam(1, $student_id);
                        $coursesStmt->execute();
                        
                        $courseCount = $coursesStmt->rowCount();
                        $courseWidth = $courseCount > 0 ? 12 / $courseCount : 12;
                        ?>
                        
                        <div class="row">
                            <?php 
                            while($course = $coursesStmt->fetch(PDO::FETCH_ASSOC)):
                                // Get grade scales for this course-teacher-term
                                $scalesQuery = "SELECT * FROM course_grade_scales 
                                                WHERE course_id = ? AND teacher_id = ? AND term_id = ?
                                                ORDER BY min_grade DESC";
                                
                                $scalesStmt = $transcriptService->db->prepare($scalesQuery);
                                $scalesStmt->bindParam(1, $course['id']);
                                $scalesStmt->bindParam(2, $course['teacher_id']);
                                $scalesStmt->bindParam(3, $course['term_id']);
                                $scalesStmt->execute();
                            ?>
                            <div class="col-md-<?php echo $courseWidth; ?>">
                                <h6 class="font-semibold"><?php echo $course['code'] . ' - ' . $course['name']; ?></h6>
                                <p class="text-muted small">Öğr. Gör. <?php echo $course['teacher_name'] . ' ' . $course['teacher_surname']; ?></p>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Harf</th>
                                            <th>Aralık</th>
                                            <th>Katsayı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($scale = $scalesStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <th><?php echo $scale['letter']; ?></th>
                                            <td><?php echo number_format($scale['min_grade'], 2) . '-' . number_format($scale['max_grade'], 2); ?></td>
                                            <td><?php echo number_format($scale['grade_point'], 1); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endwhile; ?>
                            
                            <?php if($courseCount == 0): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Henüz ders alınmamış.
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($_SESSION['role'] == ROLE_ADMIN): ?>
            <div class="mt-4 text-right">
                <a href="<?php echo url('/pages/transcript/admin_transcripts.php'); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Tüm Transkriptlere Dön
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>