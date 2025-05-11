<?php
// Database connection and include necessary files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Start the session
requireLogin();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/pages/auth/login.php'));
    exit();
}

// Get current academic year and semester
$current_year = date('Y');
$current_month = date('n');

// Determine current semester based on month
// Fall: September-December (9-12)
// Spring: January-May (1-5)
// Summer: June-August (6-8)
if ($current_month >= 9 && $current_month <= 12) {
    $current_semester = "Fall";
    $academic_year = $current_year . "-" . ($current_year + 1);
} elseif ($current_month >= 1 && $current_month <= 5) {
    $current_semester = "Spring";
    $academic_year = ($current_year - 1) . "-" . $current_year;
} else {
    $current_semester = "Summer";
    $academic_year = ($current_year - 1) . "-" . $current_year;
}

// Get the selected semester from URL parameter or use current semester
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : $current_semester;

// Sample academic calendar data - in a real application, this would come from a database
$academic_calendar = [
    "Fall" => [
        "Kayıt Dönemi" => "15-30 Ağustos",
        "Derslerin Başlangıcı" => "1 Eylül",
        "Ders Ekleme/Bırakma Dönemi" => "1-7 Eylül",
        "Ara Sınavlar" => "15-22 Ekim",
        "Şükran Günü Tatili" => "22-26 Kasım",
        "Final Sınavları" => "10-17 Aralık",
        "Dönem Sonu" => "17 Aralık",
        "Not Teslim Tarihi" => "20 Aralık"
    ],
    "Spring" => [
        "Kayıt Dönemi" => "5-15 Ocak",
        "Derslerin Başlangıcı" => "20 Ocak",
        "Ders Ekleme/Bırakma Dönemi" => "20-26 Ocak",
        "Bahar Tatili" => "15-22 Mart",
        "Ara Sınavlar" => "25-30 Mart",
        "Final Sınavları" => "10-17 Mayıs",
        "Dönem Sonu" => "17 Mayıs",
        "Not Teslim Tarihi" => "20 Mayıs"
    ],
    "Summer" => [
        "Kayıt Dönemi" => "15-30 Mayıs",
        "Derslerin Başlangıcı" => "1 Haziran",
        "Ders Ekleme/Bırakma Dönemi" => "1-3 Haziran",
        "Ara Sınavlar" => "1-3 Temmuz",
        "Final Sınavları" => "25-27 Temmuz",
        "Dönem Sonu" => "27 Temmuz",
        "Not Teslim Tarihi" => "30 Temmuz"
    ]
];

// Semester names in Turkish
$semester_names = [
    "Fall" => "Güz Dönemi",
    "Spring" => "Bahar Dönemi",
    "Summer" => "Yaz Dönemi"
];

// İçerik oluştur
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Akademik Takvim</h5>
                </div>
                <div class="card-body">
                    <!-- Semester Selection -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="<?php echo url('/pages/academic_calendar.php?semester=Fall'); ?>"
                                    class="btn <?php echo ($selected_semester == 'Fall') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Güz Dönemi
                                </a>
                                <a href="<?php echo url('/pages/academic_calendar.php?semester=Spring'); ?>"
                                    class="btn <?php echo ($selected_semester == 'Spring') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Bahar Dönemi
                                </a>
                                <a href="<?php echo url('/pages/academic_calendar.php?semester=Summer'); ?>"
                                    class="btn <?php echo ($selected_semester == 'Summer') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Yaz Dönemi
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Year Display -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h4 class="text-center"><?php echo $academic_year; ?> Akademik Yılı</h4>
                            <h5 class="text-center"><?php echo $semester_names[$selected_semester]; ?></h5>
                        </div>
                    </div>

                    <!-- Calendar Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="70%">Etkinlik</th>
                                            <th width="30%">Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Display events for the selected semester
                                        foreach ($academic_calendar[$selected_semester] as $event => $date): 
                                        ?>
                                        <tr>
                                            <td><?php echo $event; ?></td>
                                            <td><?php echo $date; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Önemli Bilgiler</h5>
                                <ul>
                                    <li>Ders kayıt işlemleri belirtilen tarihler arasında yapılmalıdır.</li>
                                    <li>Ders ekleme/bırakma işlemleri sadece belirtilen tarihler arasında yapılabilir.
                                    </li>
                                    <li>Sınav tarihleri değişebilir, lütfen dersin öğretim üyesi ile iletişime geçiniz.
                                    </li>
                                    <li>Notların sisteme girilmesi için son tarih belirtilen "Not Teslim Tarihi"
                                        tarihidir.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php // İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../includes/layout.php'; ?>