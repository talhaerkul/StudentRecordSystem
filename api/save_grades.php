<?php
// Suppress all PHP errors and notices from output
ini_set('display_errors', '0');
error_reporting(0);

// Include necessary files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Course.php';
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

// Log for debugging (write to file instead of output)
function logDebug($message, $data = null) {
    $logFile = '../logs/grades_api.log';
    $logMessage = date('[Y-m-d H:i:s]') . ' ' . $message;
    
    if ($data !== null) {
        $logMessage .= ': ' . json_encode($data);
    }
    
    // Make sure log directory exists
    if (!file_exists('../logs')) {
        mkdir('../logs', 0777, true);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Log the received POST data
logDebug('POST data received', $_POST);

// Validate input data
if (!isset($_POST['course_id']) || !isset($_POST['term_id']) || !isset($_POST['grades'])) {
    returnError('Missing parameters: Required course_id, term_id, and grades');
}

if (empty($_POST['course_id']) || empty($_POST['term_id'])) {
    returnError('Empty course_id or term_id');
}

// Get input parameters
$courseId = intval($_POST['course_id']);
$termId = intval($_POST['term_id']);

// Process grades data - it's a JSON string, not an array
try {
    $gradesJson = $_POST['grades'];
    if (!is_string($gradesJson)) {
        returnError('Invalid grades format: expected JSON string');
    }
    
    $grades = json_decode($gradesJson, true);
    if ($grades === null && json_last_error() !== JSON_ERROR_NONE) {
        returnError('Invalid JSON data: ' . json_last_error_msg());
    }
    
    if (!is_array($grades)) {
        returnError('Grades must be a JSON object');
    }
    
    logDebug('Grades parsed', $grades);
} catch (Exception $e) {
    returnError('Error processing grades data: ' . $e->getMessage());
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify that the teacher is assigned to this course and term
    $query = "SELECT 1 FROM teacher_courses 
              WHERE teacher_id = ? AND course_id = ? AND term_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $teacherId);
    $stmt->bindParam(2, $courseId);
    $stmt->bindParam(3, $termId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        returnError('You are not assigned to this course for the selected term');
    }
    
    // Initialize course object
    $course = new Course($db);
    $course->id = $courseId;
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update each student's grade
    $successCount = 0;
    $totalUpdates = 0;
    $errors = [];
    
    foreach ($grades as $studentId => $grade) {
        // Validate grade and student ID
        if (!is_numeric($grade) || $grade < 0 || $grade > 100) {
            $errors[] = "Invalid grade value for student ID $studentId: $grade";
            continue;
        }
        
        if (!is_numeric($studentId) || intval($studentId) <= 0) {
            $errors[] = "Invalid student ID: $studentId";
            continue;
        }
        
        $totalUpdates++;
        $course->id = $courseId;
        $result = $course->updateStudentGrade(intval($studentId), $termId, $grade);
        
        if ($result) {
            $successCount++;
        } else {
            $errors[] = "Failed to update grade for student ID $studentId";
        }
    }
    
    // Log results
    logDebug('Grade update results', [
        'totalUpdates' => $totalUpdates,
        'successCount' => $successCount,
        'errors' => $errors
    ]);
    
    // Check if any updates were successful
    if ($totalUpdates === 0) {
        $db->rollBack();
        returnError('No grades to update');
    }
    
    if ($successCount === $totalUpdates) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'All grades updated successfully',
            'updated' => $successCount
        ]);
    } else {
        $db->rollBack();
        returnError('Some grades failed to update: ' . implode("; ", $errors));
    }
    
} catch (Exception $e) {
    // If an error occurs, rollback the transaction
    if (isset($db)) {
        $db->rollBack();
    }
    
    logDebug('Exception', $e->getMessage());
    returnError('Database error: ' . $e->getMessage(), 500);
}
?>