<?php
class CourseRequest {
    // Database connection and table name
    private $conn;
    private $table_name = "student_course_requests";

    // Object properties
    public $id;
    public $student_id;
    public $course_id;
    public $term_id;
    public $status;
    public $requested_at;
    public $processed_at;
    public $processed_by;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new course request
    public function create() {
        // Check if the request already exists
        if ($this->requestExists()) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET student_id = :student_id,
                      course_id = :course_id,
                      term_id = :term_id,
                      status = 'pending',
                      requested_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":term_id", $this->term_id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if request already exists
    public function requestExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE student_id = ? AND course_id = ? AND term_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->student_id);
        $stmt->bindParam(2, $this->course_id);
        $stmt->bindParam(3, $this->term_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
    
    // Get all course requests for a student in a term
    public function getStudentRequests($student_id, $term_id) {
        $query = "SELECT r.*, c.code, c.name, c.credit, c.hours_per_week, 
                  d.name as department_name, 
                  CASE 
                    WHEN r.status = 'pending' THEN 'Beklemede'
                    WHEN r.status = 'approved' THEN 'Onaylandı'
                    WHEN r.status = 'rejected' THEN 'Reddedildi'
                  END as status_text
                  FROM " . $this->table_name . " r
                  JOIN courses c ON r.course_id = c.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE r.student_id = ? AND r.term_id = ?
                  ORDER BY r.requested_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->bindParam(2, $term_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get all pending course requests for teacher's courses in a term
    public function getTeacherPendingRequests($teacher_id, $term_id) {
        $query = "SELECT r.*, 
                  u.name as student_name, u.surname as student_surname, u.student_id as student_number,
                  c.code, c.name as course_name, c.credit, c.hours_per_week,
                  d.name as department_name
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.student_id = u.id
                  JOIN courses c ON r.course_id = c.id
                  JOIN teacher_courses tc ON (c.id = tc.course_id AND tc.term_id = r.term_id)
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE tc.teacher_id = ? AND r.term_id = ? AND r.status = 'pending'
                  ORDER BY c.code, r.requested_at";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $teacher_id);
        $stmt->bindParam(2, $term_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Approve a course request
    public function approve() {
        // First get the request to check status
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'pending') {
            return false;
        }
        
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Update request status
            $query = "UPDATE " . $this->table_name . " 
                      SET status = 'approved', 
                          processed_at = NOW(), 
                          processed_by = :processed_by 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":processed_by", $this->processed_by);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            
            // Enroll student in the course
            $query = "INSERT INTO student_courses (student_id, course_id, term_id, status)
                      VALUES (:student_id, :course_id, :term_id, 'enrolled')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":student_id", $row['student_id']);
            $stmt->bindParam(":course_id", $row['course_id']);
            $stmt->bindParam(":term_id", $row['term_id']);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            $this->conn->rollBack();
            return false;
        }
    }
    
    // Reject a course request
    public function reject() {
        // First get the request to check status
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'pending') {
            return false;
        }
        
        // Update request status
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'rejected', 
                      processed_at = NOW(), 
                      processed_by = :processed_by 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":processed_by", $this->processed_by);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete a course request
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get one course request
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->student_id = $row['student_id'];
            $this->course_id = $row['course_id'];
            $this->term_id = $row['term_id'];
            $this->status = $row['status'];
            $this->requested_at = $row['requested_at'];
            $this->processed_at = $row['processed_at'];
            $this->processed_by = $row['processed_by'];
            return true;
        }
        
        return false;
    }
    
    // Check if a student is already enrolled in a course for a term
    public function isStudentEnrolled() {
        $query = "SELECT 1 FROM student_courses 
                  WHERE student_id = ? AND course_id = ? AND term_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->student_id);
        $stmt->bindParam(2, $this->course_id);
        $stmt->bindParam(3, $this->term_id);
        $stmt->execute();
        
        return ($stmt->rowCount() > 0);
    }
}
?> 