<?php
// Set response content type to JSON
header('Content-Type: application/json');

// Include necessary files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/GradeScale.php';

// Check if user is authenticated (session is already started in config.php)
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if request parameters are provided
if(!isset($_GET['course_id']) || !isset($_GET['teacher_id']) || !isset($_GET['term_id'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Extract parameters
$course_id = intval($_GET['course_id']);
$teacher_id = intval($_GET['teacher_id']);
$term_id = intval($_GET['term_id']);

// Security check: only teachers can see their own scales or admins can see any scales
if($_SESSION['role'] != ROLE_ADMIN && $_SESSION['user_id'] != $teacher_id) {
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get grade scales
$gradeScale = new GradeScale($db);
$grades = [];

try {
    $stmt = $gradeScale->readByCourseTeacherTerm($course_id, $teacher_id, $term_id);
    
    if (!$stmt) {
        echo json_encode(['error' => 'Database query failed']);
        exit;
    }
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grades[] = [
            'id' => $row['id'],
            'letter' => $row['letter'],
            'min_grade' => $row['min_grade'],
            'max_grade' => $row['max_grade'],
            'grade_point' => $row['grade_point']
        ];
    }
    
    // Sort scales by min_grade in descending order
    usort($grades, function($a, $b) {
        return $b['min_grade'] <=> $a['min_grade'];
    });
    
    // Return JSON response
    echo json_encode($grades);
    
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 