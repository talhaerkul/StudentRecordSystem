<?php
// Include necessary files
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../models/User.php';
require_once '../../models/Course.php';
require_once '../../models/Term.php';
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

// Get all courses assigned to the teacher
$stmt = $user->getTeacherCourses();

// Get terms for filter
$term = new Term($db);
$terms_stmt = $term->readActive();
$terms = [];
while ($term_row = $terms_stmt->fetch(PDO::FETCH_ASSOC)) {
    $terms[] = $term_row;
}

// Sayfa başlığı
$page_title = 'Derslerim';

// Get selected term filter
$selected_term_id = isset($_GET['term_id']) ? $_GET['term_id'] : '';

// Sayfa içeriğini oluştur
ob_start();
?>

<!-- Filter Section -->
<div class="card shadow-lg rounded-lg mb-4">
    <div class="card-header bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-filter mr-2"></i> Filtrele</h5>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline justify-content-between align-items-end">
            <div class="form-group mb-2">
                <label for="term_id" class="mr-2">Dönem:</label>
                <select name="term_id" id="term_id" class="form-control">
                    <option value="">Tüm Dönemler</option>
                    <?php foreach ($terms as $term_item): ?>
                    <option value="<?php echo $term_item['id']; ?>"
                        <?php echo ($selected_term_id == $term_item['id']) ? 'selected' : ''; ?>>
                        <?php echo $term_item['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex">
                <a href="<?php echo url('/pages/course/my_students.php'); ?>" class="btn btn-outline-success mr-2">
                    <i class="fas fa-users mr-2"></i> Öğrencilerim
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i> Filtrele
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card shadow-lg rounded-lg">
    <div class="card-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-book mr-2"></i> Derslerim</h5>
    </div>
    <div class="card-body">
        <?php if ($stmt->rowCount() > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="bg-gray-200">
                    <tr>
                        <th>Ders Kodu</th>
                        <th>Ders Adı</th>
                        <th>Dönem</th>
                        <th>Kredi</th>
                        <th>Öğrenci Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            // Skip if term filter is set and doesn't match
                            if (!empty($selected_term_id) && $row['term_id'] != $selected_term_id) {
                                continue;
                            }
                        ?>
                    <tr>
                        <td><?php echo $row['code']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['term_name']; ?></td>
                        <td><?php echo $row['credit']; ?></td>
                        <td>
                            <span class="badge badge-primary px-2 py-1 rounded-pill">
                                <?php echo $row['student_count']; ?> öğrenci
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="<?php echo url('/pages/grade/grades.php?course_id=' . $row['id'] . '&term_id=' . $row['term_id']); ?>"
                                    class="btn btn-sm btn-outline-primary" title="Not Girişi">
                                    <i class="fas fa-clipboard-check"></i> Not Girişi
                                </a>
                                <a href="<?php echo url('/pages/grade/course_grade_scales.php?course_id=' . $row['id'] . '&term_id=' . $row['term_id']); ?>"
                                    class="btn btn-sm btn-outline-info" title="Not Ölçekleri">
                                    <i class="fas fa-chart-bar"></i> Not Ölçekleri
                                </a>
                                <a href="<?php echo url('/pages/course/course_schedule.php?course_id=' . $row['id'] . '&term_id=' . $row['term_id']); ?>"
                                    class="btn btn-sm btn-outline-secondary" title="Ders Programı">
                                    <i class="fas fa-calendar-alt"></i> Program
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle mr-2"></i> Henüz size atanmış bir ders bulunmamaktadır. Ders atamaları bölüm
            başkanları veya yöneticiler tarafından yapılmaktadır.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Card with helpful information -->
<div class="card shadow-lg rounded-lg mt-4">
    <div class="card-header bg-gradient-to-r from-blue-500 to-teal-500 text-white py-3">
        <h5 class="mb-0 font-semibold"><i class="fas fa-info-circle mr-2"></i> Bilgi</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-light border">
            <h6 class="alert-heading font-weight-bold"><i class="fas fa-lightbulb text-warning mr-2"></i> Ders İşlemleri
                Hakkında</h6>
            <p>Derslerinize ait aşağıdaki işlemleri gerçekleştirebilirsiniz:</p>
            <ul class="mb-0 pl-4">
                <li><strong>Not Girişi</strong> - Öğrencilerin sınav, ödev ve diğer değerlendirme notlarını
                    girebilirsiniz.</li>
                <li><strong>Not Ölçekleri</strong> - Ders için harf notu hesaplama ölçeklerini düzenleyebilirsiniz.</li>
                <li><strong>Program</strong> - Ders programını görüntüleyebilirsiniz.</li>
                <li><strong><a href="<?php echo url('/pages/course/my_students.php'); ?>">Öğrencilerim</a></strong> -
                    Tüm derslerinize kayıtlı öğrencileri görüntüleyebilirsiniz.</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include_once '../../includes/layout.php';
?>