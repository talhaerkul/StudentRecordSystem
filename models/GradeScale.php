<?php
class GradeScale {
    // Database connection and table name
    private $conn;
    private $table_name = "course_grade_scales";

    // Object properties
    public $id;
    public $course_id;
    public $teacher_id;
    public $term_id;
    public $letter;
    public $min_grade;
    public $max_grade;
    public $grade_point;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all grade scales for a specific course-teacher-term combination
    public function readByCourseTeacherTerm($course_id, $teacher_id, $term_id) {
        $query = "SELECT gs.* FROM " . $this->table_name . " gs
                  JOIN courses c ON gs.course_id = c.id
                  JOIN terms t ON gs.term_id = t.id
                  WHERE gs.course_id = ? AND gs.teacher_id = ? AND gs.term_id = ?
                  AND c.status = 'active' AND t.status = 'active'
                  ORDER BY gs.min_grade DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $course_id);
        $stmt->bindParam(2, $teacher_id);
        $stmt->bindParam(3, $term_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get letter grade for a specific grade and course-teacher-term combination
    public function getLetterGrade($grade, $course_id, $teacher_id, $term_id) {
        $query = "SELECT gs.letter, gs.grade_point FROM " . $this->table_name . " gs
                  JOIN courses c ON gs.course_id = c.id
                  JOIN terms t ON gs.term_id = t.id
                  WHERE gs.course_id = ? AND gs.teacher_id = ? AND gs.term_id = ?
                  AND ? BETWEEN gs.min_grade AND gs.max_grade
                  AND c.status = 'active' AND t.status = 'active'
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $course_id);
        $stmt->bindParam(2, $teacher_id);
        $stmt->bindParam(3, $term_id);
        $stmt->bindParam(4, $grade);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            return $row;
        }
        
        return ['letter' => 'NA', 'grade_point' => 0];
    }

    // Update or create grade scales for a specific course-teacher-term combination
    public function updateScales($scales) {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Delete existing scales for this course-teacher-term
            $deleteQuery = "DELETE FROM " . $this->table_name . " 
                            WHERE course_id = ? AND teacher_id = ? AND term_id = ?";
            
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(1, $this->course_id);
            $deleteStmt->bindParam(2, $this->teacher_id);
            $deleteStmt->bindParam(3, $this->term_id);
            $deleteStmt->execute();
            
            // Insert new scales
            $insertQuery = "INSERT INTO " . $this->table_name . " 
                            (course_id, teacher_id, term_id, letter, min_grade, max_grade, grade_point, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $insertStmt = $this->conn->prepare($insertQuery);
            
            foreach ($scales as $scale) {
                $insertStmt->bindParam(1, $this->course_id);
                $insertStmt->bindParam(2, $this->teacher_id);
                $insertStmt->bindParam(3, $this->term_id);
                $insertStmt->bindParam(4, $scale['letter']);
                $insertStmt->bindParam(5, $scale['min_grade']);
                $insertStmt->bindParam(6, $scale['max_grade']);
                $insertStmt->bindParam(7, $scale['grade_point']);
                $insertStmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction if something went wrong
            $this->conn->rollBack();
            return false;
        }
    }

    // Copy default scales to a course-teacher-term
    public function createDefaultScales() {
        $defaultScales = [
            ['letter' => 'AA', 'min_grade' => 90, 'max_grade' => 100, 'grade_point' => 4.0],
            ['letter' => 'BA', 'min_grade' => 85, 'max_grade' => 89.99, 'grade_point' => 3.5],
            ['letter' => 'BB', 'min_grade' => 80, 'max_grade' => 84.99, 'grade_point' => 3.0],
            ['letter' => 'CB', 'min_grade' => 75, 'max_grade' => 79.99, 'grade_point' => 2.5],
            ['letter' => 'CC', 'min_grade' => 70, 'max_grade' => 74.99, 'grade_point' => 2.0],
            ['letter' => 'DC', 'min_grade' => 65, 'max_grade' => 69.99, 'grade_point' => 1.5],
            ['letter' => 'DD', 'min_grade' => 60, 'max_grade' => 64.99, 'grade_point' => 1.0],
            ['letter' => 'FF', 'min_grade' => 0, 'max_grade' => 59.99, 'grade_point' => 0.0]
        ];
        
        return $this->updateScales($defaultScales);
    }

    // Check if scales exist for a specific course-teacher-term
    public function scalesExist($course_id, $teacher_id, $term_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " cgs
                  JOIN courses c ON cgs.course_id = c.id
                  JOIN terms t ON cgs.term_id = t.id
                  WHERE cgs.course_id = ? AND cgs.teacher_id = ? AND cgs.term_id = ?
                  AND c.status = 'active' AND t.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $course_id);
        $stmt->bindParam(2, $teacher_id);
        $stmt->bindParam(3, $term_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($row['count'] > 0);
    }

    // Get all grade scales for admin view
    public function readAllScalesForAdmin() {
        $query = "SELECT cgs.*, 
                  c.code as course_code, c.name as course_name,
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  t.name as term_name
                  FROM " . $this->table_name . " cgs
                  JOIN courses c ON cgs.course_id = c.id
                  JOIN users u ON cgs.teacher_id = u.id
                  JOIN terms t ON cgs.term_id = t.id
                  WHERE c.status = 'active' AND t.status = 'active'
                  ORDER BY t.start_date DESC, c.code ASC, cgs.letter ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?> 