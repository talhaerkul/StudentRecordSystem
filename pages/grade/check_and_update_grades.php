<?php
// Gerekli dosyaları dahil et
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../models/Course.php';
require_once '../../models/GradeScale.php';

// Veritabanı bağlantısı oluştur
$database = new Database();
$db = $database->getConnection();

// Check if student_courses table has updated_at column
$checkColumn = "SHOW COLUMNS FROM student_courses LIKE 'updated_at'";
$checkStmt = $db->prepare($checkColumn);
$checkStmt->execute();
$hasUpdatedAt = ($checkStmt->rowCount() > 0);

// If updated_at doesn't exist, add it
if (!$hasUpdatedAt) {
    try {
        $addColumn = "ALTER TABLE student_courses ADD COLUMN updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";
        $db->exec($addColumn);
        echo "<p>Added missing 'updated_at' column to student_courses table.</p>";
    } catch (PDOException $e) {
        echo "<p>Warning: " . $e->getMessage() . "</p>";
    }
}

// Öğrencilere atanmış derslerin listesini al
$query = "SELECT sc.student_id, sc.course_id, sc.term_id, sc.grade, 
         u.name as student_name, u.surname as student_surname, 
         c.code as course_code, c.name as course_name,
         tc.teacher_id, 
         t.name as teacher_name, t.surname as teacher_surname
         FROM student_courses sc
         JOIN users u ON sc.student_id = u.id
         JOIN courses c ON sc.course_id = c.id
         JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
         JOIN users t ON tc.teacher_id = t.id
         ORDER BY sc.student_id, sc.course_id, sc.term_id";

$stmt = $db->prepare($query);
$stmt->execute();

echo "<h2>Mevcut Ders Atamaları ve Notlar</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Öğrenci ID</th><th>Öğrenci Adı</th><th>Ders Kodu</th><th>Ders Adı</th><th>Öğretmen</th><th>Not</th></tr>";

$studentCourses = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['student_id'] . "</td>";
    echo "<td>" . $row['student_name'] . " " . $row['student_surname'] . "</td>";
    echo "<td>" . $row['course_code'] . "</td>";
    echo "<td>" . $row['course_name'] . "</td>";
    echo "<td>" . $row['teacher_name'] . " " . $row['teacher_surname'] . "</td>";
    echo "<td>" . ($row['grade'] ? $row['grade'] : 'Not girilmemiş') . "</td>";
    echo "</tr>";
    
    // Daha sonra güncellemek için student_courses verilerini sakla
    $studentCourses[] = [
        'student_id' => $row['student_id'],
        'course_id' => $row['course_id'],
        'term_id' => $row['term_id'],
        'teacher_id' => $row['teacher_id']
    ];
}
echo "</table>";

// Eğer student_courses tablosunda veri yoksa, işleme devam etme
if (empty($studentCourses)) {
    echo "<p>Henüz öğrencilere atanmış ders bulunmamaktadır.</p>";
    exit;
}

// Örnek notları güncelle veya ekle
echo "<h2>Örnek Notlar Eklenecek</h2>";

try {
    // İşlem başlat
    $db->beginTransaction();
    
    $course = new Course($db);
    $gradeScale = new GradeScale($db);
    
    // Eklenen not sayısı
    $updateCount = 0;
    
    foreach ($studentCourses as $sc) {
        // Grade Scale kontrolü ve varsayılan ölçekleri yükleme
        if (!$gradeScale->scalesExist($sc['course_id'], $sc['teacher_id'], $sc['term_id'])) {
            $gradeScale->course_id = $sc['course_id'];
            $gradeScale->teacher_id = $sc['teacher_id'];
            $gradeScale->term_id = $sc['term_id'];
            $gradeScale->createDefaultScales();
            echo "<p>Grade Scale oluşturuldu: Course ID: " . $sc['course_id'] . ", Teacher ID: " . $sc['teacher_id'] . ", Term ID: " . $sc['term_id'] . "</p>";
        }
        
        // Rastgele not ata (60-100 arasında)
        $randomGrade = rand(60, 100);
        
        // Notu güncelle
        $course->id = $sc['course_id'];
        
        // Use direct SQL query instead of Course::updateStudentGrade method
        $updateGradeQuery = "UPDATE student_courses SET grade = ? WHERE student_id = ? AND course_id = ? AND term_id = ?";
        $updateStmt = $db->prepare($updateGradeQuery);
        $updateStmt->bindParam(1, $randomGrade);
        $updateStmt->bindParam(2, $sc['student_id']);
        $updateStmt->bindParam(3, $sc['course_id']);
        $updateStmt->bindParam(4, $sc['term_id']);
        $result = $updateStmt->execute();
        
        if ($result) {
            echo "<p>Not güncellendi: Öğrenci ID: " . $sc['student_id'] . ", Ders ID: " . $sc['course_id'] . ", Dönem ID: " . $sc['term_id'] . ", Not: " . $randomGrade . "</p>";
            $updateCount++;
        } else {
            echo "<p>Not güncelleme başarısız: Öğrenci ID: " . $sc['student_id'] . ", Ders ID: " . $sc['course_id'] . ", Dönem ID: " . $sc['term_id'] . "</p>";
        }
    }
    
    // Değişiklikleri kaydet
    $db->commit();
    
    echo "<p><strong>Toplam " . $updateCount . " not başarıyla güncellendi.</strong></p>";
    echo "<p>Şimdi <a href='" . url('/pages/grade/transcript.php') . "' target='_blank'>transkript sayfasına</a> giderek notlarınızı görüntüleyebilirsiniz.</p>";
    
} catch (Exception $e) {
    // Hata durumunda geri al
    $db->rollBack();
    echo "<p>Hata: " . $e->getMessage() . "</p>";
}
?> 