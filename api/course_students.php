<?php
// Suppress all PHP errors and notices from output
ini_set('display_errors', '0');
error_reporting(0);

// Include necessary files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/GradeScale.php';

// Set content type to JSON for all responses
header('Content-Type: application/json');

// Error handler function to ensure we always return JSON
function returnError($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Only handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    returnError('Method not allowed', 405);
}

// Check if session is already active before starting it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for logged in user and teacher role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_TEACHER) {
    returnError('Unauthorized access', 403);
}

// Get the teacher ID
$teacherId = $_SESSION['user_id'];

// Validate input data
if (!isset($_GET['course_id'], $_GET['term_id']) || 
    empty($_GET['course_id']) || 
    empty($_GET['term_id'])) {
    
    returnError('Missing or invalid parameters');
}

// Get input parameters
$courseId = intval($_GET['course_id']);
$termId = intval($_GET['term_id']);

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify that the teacher is assigned to this course and term
    $query = "SELECT 1 FROM teacher_courses tc
              JOIN courses c ON tc.course_id = c.id
              JOIN terms t ON tc.term_id = t.id
              WHERE tc.teacher_id = ? AND tc.course_id = ? AND tc.term_id = ?
              AND c.status = 'active' AND t.status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $teacherId);
    $stmt->bindParam(2, $courseId);
    $stmt->bindParam(3, $termId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        returnError('You are not assigned to this course for the selected term');
    }
    
    // Check if grade scales exist for this course-teacher-term, create defaults if not
    $gradeScale = new GradeScale($db);
    if (!$gradeScale->scalesExist($courseId, $teacherId, $termId)) {
        $gradeScale->course_id = $courseId;
        $gradeScale->teacher_id = $teacherId;
        $gradeScale->term_id = $termId;
        $gradeScale->createDefaultScales();
    }
    
    // Get students enrolled in this course and term
    $query = "SELECT u.id, u.student_id, u.name, u.surname, sc.grade
              FROM student_courses sc
              JOIN users u ON sc.student_id = u.id
              JOIN courses c ON sc.course_id = c.id
              JOIN terms t ON sc.term_id = t.id
              WHERE sc.course_id = ? AND sc.term_id = ?
              AND c.status = 'active' AND t.status = 'active'
              ORDER BY u.surname, u.name";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $courseId);
    $stmt->bindParam(2, $termId);
    $stmt->execute();
    
    $students = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $students[] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'surname' => $row['surname'],
            'grade' => $row['grade']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => '',
        'data' => $students
    ]);
    
} catch (Exception $e) {
    returnError('Database error: ' . $e->getMessage(), 500);
}
?>