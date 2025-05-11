<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../models/User.php';
require_once '../../config/database.php';
require_once '../../service/TranscriptService.php';

// Check if user is logged in and is a teacher
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_TEACHER) {
    header("Location: " . url('/pages/auth/login.php'));
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create TranscriptService instance
$transcriptService = new TranscriptService($db);

// Get teacher ID
$teacher_id = $_SESSION['user_id'];

// Get transcripts for students in teacher's courses
$studentTranscripts = $transcriptService->getTeacherStudentsTranscripts($teacher_id);

// Get user model
$user = new User($db);

// Sayfa içeriğini oluştur
ob_start();
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-gray-800 font-bold"><i class="fas fa-file-alt mr-2 text-teal-600"></i> Öğrenci Transkriptleri
            </h2>
            <p class="text-gray-600">Derslerinizi alan öğrencilerin transkriptlerini görüntüleyin.</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo url('/pages/dashboard.php'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard'a Dön
            </a>
        </div>
    </div>

    <div class="card shadow-lg rounded-lg overflow-hidden">
        <div class="card-header bg-gradient-to-r from-teal-600 to-blue-600 text-white py-3">
            <h4 class="mb-0 font-bold">Öğrenci Listesi</h4>
        </div>

        <div class="card-body p-4">
            <?php if (empty($studentTranscripts)): ?>
            <div class="alert alert-info">
                <p class="mb-0">Derslerinizi alan öğrenci bulunmamaktadır veya kayıtlı not bulunmamaktadır.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="students-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>Öğrenci No</th>
                            <th>Adı Soyadı</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentTranscripts as $transcript): ?>
                        <tr>
                            <td><?php echo $transcript['student_number']; ?></td>
                            <td class="font-semibold"><?php echo $transcript['student_name']; ?></td>
                            <td>
                                <a href="<?php echo url('/pages/transcript/transcript.php?student_id=' . $transcript['student_id']); ?>"
                                    class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-file-alt"></i> Transkripti Görüntüle
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#students-table').DataTable({
        "order": [
            [1, "asc"]
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        }
    });
});
</script>

<?php
// Oluşturulan içeriği al
$content = ob_get_clean();

// Ana layout dosyasını dahil et
require_once '../../includes/layout.php';
?> 