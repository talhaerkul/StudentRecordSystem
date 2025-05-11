<?php
// Include necessary files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class TranscriptController {
    private $user;
    public $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    /**
     * Get transcript for a specific student
     * 
     * @param int $student_id The student ID
     * @return PDOStatement|bool The transcript data or false on failure
     */
    public function getStudentTranscript($student_id) {
        // Prepare the user object
        $this->user->id = $student_id;
        
        // Check if user exists
        if (!$this->user->readOne()) {
            return false;
        }
        
        // Get the transcript
        return $this->user->getTranscript();
    }

    /**
     * Get transcripts for all students taught by a teacher
     * 
     * @param int $teacher_id The teacher ID
     * @return array An array of student transcripts
     */
    public function getTeacherStudentsTranscripts($teacher_id) {
        // Set teacher ID
        $this->user->id = $teacher_id;
        
        // Check if user exists and is a teacher
        if (!$this->user->readOne() || $this->user->role_id != ROLE_TEACHER) {
            return [];
        }
        
        // Get all students taught by this teacher
        $query = "SELECT DISTINCT sc.student_id
                  FROM teacher_courses tc
                  JOIN student_courses sc ON tc.course_id = sc.course_id AND tc.term_id = sc.term_id
                  WHERE tc.teacher_id = ?";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $teacher_id);
        $stmt->execute();
        
        $transcripts = [];
        
        // Get transcript for each student
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $student_id = $row['student_id'];
            $student_transcript = $this->getStudentTranscript($student_id);
            
            if ($student_transcript) {
                // Get student info
                $student = new User($this->db);
                $student->id = $student_id;
                $student->readOne();
                
                $transcripts[] = [
                    'student_id' => $student_id,
                    'student_name' => $student->name . ' ' . $student->surname,
                    'student_number' => $student->student_id,
                    'transcript' => $student_transcript
                ];
            }
        }
        
        return $transcripts;
    }

    /**
     * Get transcripts for all students (admin only)
     * 
     * @return array An array of all student transcripts
     */
    public function getAllStudentsTranscripts() {
        // Get all students
        $query = "SELECT id FROM users WHERE role_id = ?";
        $stmt = $this->db->prepare($query);
        $role_id = ROLE_STUDENT;
        $stmt->bindParam(1, $role_id);
        $stmt->execute();
        
        $transcripts = [];
        
        // Get transcript for each student
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $student_id = $row['id'];
            $student_transcript = $this->getStudentTranscript($student_id);
            
            if ($student_transcript) {
                // Get student info
                $student = new User($this->db);
                $student->id = $student_id;
                $student->readOne();
                
                $transcripts[] = [
                    'student_id' => $student_id,
                    'student_name' => $student->name . ' ' . $student->surname,
                    'student_number' => $student->student_id,
                    'transcript' => $student_transcript
                ];
            }
        }
        
        return $transcripts;
    }

    /**
     * Check if a user has access to a student's transcript
     * 
     * @param int $user_id The user ID requesting access
     * @param int $role_id The user's role ID
     * @param int $student_id The student ID whose transcript is being accessed
     * @return bool Whether the user has access
     */
    public function hasAccessToTranscript($user_id, $role_id, $student_id) {
        // Admin has access to all transcripts
        if ($role_id == ROLE_ADMIN) {
            return true;
        }
        
        // Students can only access their own transcripts
        if ($role_id == ROLE_STUDENT) {
            return $user_id == $student_id;
        }
        
        // Teachers can access transcripts of students in their courses
        if ($role_id == ROLE_TEACHER) {
            $query = "SELECT COUNT(*) as count
                      FROM teacher_courses tc
                      JOIN student_courses sc ON tc.course_id = sc.course_id AND tc.term_id = sc.term_id
                      WHERE tc.teacher_id = ? AND sc.student_id = ?";
                      
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $student_id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['count'] > 0;
        }
        
        return false;
    }
}
?>