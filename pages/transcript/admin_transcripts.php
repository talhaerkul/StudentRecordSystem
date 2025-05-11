<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../models/User.php';
require_once '../../config/database.php';
require_once '../../service/TranscriptService.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_ADMIN) {
    header("Location: " . url('/pages/auth/login.php'));
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create TranscriptService instance
$transcriptService = new TranscriptService($db);

// Create User model instance
$user = new User($db);

// Get all students
$students = $user->readStudents();

// İçerik oluştur
ob_start();
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-gray-800 font-bold"><i class="fas fa-file-alt mr-2 text-indigo-600"></i> Transkript Yönetimi
            </h2>
            <p class="text-gray-600">Tüm öğrencilerin transkriptlerini görüntüleyin ve düzenleyin.</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo url('/pages/dashboard.php'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard'a Dön
            </a>
        </div>
    </div>

    <div class="card shadow-lg rounded-lg overflow-hidden">
        <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
            <h4 class="mb-0 font-bold">Öğrenci Listesi</h4>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover" id="students-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th>ID</th>
                            <th>Öğrenci No</th>
                            <th>Adı Soyadı</th>
                            <th>Bölüm</th>
                            <th>E-posta</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $students->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['student_id']; ?></td>
                            <td class="font-semibold"><?php echo $row['name'] . ' ' . $row['surname']; ?></td>
                            <td><?php echo $row['department_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <span
                                    class="badge badge-<?php echo ($row['status'] == 'active') ? 'success' : 'danger'; ?>">
                                    <?php echo ($row['status'] == 'active') ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo url('/pages/transcript/transcript.php?student_id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-file-alt"></i> Görüntüle
                                    </a>
                                    <a href="<?php echo url('/pages/transcript/edit_transcript.php?student_id=' . $row['id']); ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#students-table').DataTable({
        "order": [
            [2, "asc"]
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
        }
    });
});
</script>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?>