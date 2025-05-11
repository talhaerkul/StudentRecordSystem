<?php
class Course {
    // Database connection and table name
    private $conn;
    private $table_name = "courses";

    // Object properties
    public $id;
    public $code;
    public $name;
    public $description;
    public $credit;
    public $department_id;
    public $year;
    public $hours_per_week;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all courses
    public function readAll() {
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  ORDER BY c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read only active courses
    public function readActive() {
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.status = 'active'
                  ORDER BY c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all courses with pagination
    public function readAllPaginated($page = 1, $records_per_page = 10) {
        // Calculate the starting position
        $start = ($page - 1) * $records_per_page;
        
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  ORDER BY c.code ASC
                  LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $start, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read active courses with pagination
    public function readActivePaginated($page = 1, $records_per_page = 10) {
        // Calculate the starting position
        $start = ($page - 1) * $records_per_page;
        
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.status = 'active'
                  ORDER BY c.code ASC
                  LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $start, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count total records for pagination
    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    // Count active records for pagination
    public function countActive() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Read courses by department
    public function readByDepartment($department_id) {
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.department_id = ?
                  ORDER BY c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $department_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read active courses by department
    public function readActiveByDepartment($department_id) {
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.department_id = ? AND c.status = 'active'
                  ORDER BY c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $department_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Create course
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET code = :code,
                      name = :name,
                      description = :description,
                      credit = :credit,
                      department_id = :department_id,
                      year = :year,
                      hours_per_week = :hours_per_week,
                      status = :status,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->credit = htmlspecialchars(strip_tags($this->credit));
        $this->department_id = htmlspecialchars(strip_tags($this->department_id));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->hours_per_week = htmlspecialchars(strip_tags($this->hours_per_week));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":credit", $this->credit);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":year", $this->year);
        $stmt->bindParam(":hours_per_week", $this->hours_per_week);
        $stmt->bindParam(":status", $this->status);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read one course
    public function readOne() {
        $query = "SELECT c.*, d.name as department_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->code = $row['code'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->credit = $row['credit'];
            $this->department_id = $row['department_id'];
            $this->department_name = $row['department_name'];
            $this->year = $row['year'];
            $this->hours_per_week = $row['hours_per_week'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update course
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET code = :code,
                      name = :name,
                      description = :description,
                      credit = :credit,
                      department_id = :department_id,
                      year = :year,
                      hours_per_week = :hours_per_week,
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->credit = htmlspecialchars(strip_tags($this->credit));
        $this->department_id = htmlspecialchars(strip_tags($this->department_id));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->hours_per_week = htmlspecialchars(strip_tags($this->hours_per_week));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":credit", $this->credit);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":year", $this->year);
        $stmt->bindParam(":hours_per_week", $this->hours_per_week);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete course
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if course code exists
    public function codeExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE code = ? AND id != ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Değişkenleri oluştur
        $code = $this->code;
        $id_value = $this->id ? $this->id : 0;
        
        // Değişkenleri referans olarak bindParam'a geçir
        $stmt->bindParam(1, $code);
        $stmt->bindParam(2, $id_value);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
    
    // Assign course to teacher
    public function assignToTeacher($teacher_id, $term_id) {
        // First check if both course and term are active
        $query = "SELECT c.id as course_id, t.id as term_id 
                  FROM " . $this->table_name . " c
                  JOIN terms t ON t.id = ?
                  WHERE c.id = ? AND c.status = 'active' AND t.status = 'active'";
        
        $checkStmt = $this->conn->prepare($query);
        $checkStmt->bindParam(1, $term_id);
        $checkStmt->bindParam(2, $this->id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() == 0) {
            // Either course or term is inactive
            return false;
        }
        
        $query = "INSERT INTO teacher_courses (teacher_id, course_id, term_id, created_at)
                  VALUES (?, ?, ?, NOW())";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $teacher_id);
        $stmt->bindParam(2, $this->id);
        $stmt->bindParam(3, $term_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Remove course assignment from teacher
    public function removeFromTeacher($teacher_id, $term_id) {
        $query = "DELETE FROM teacher_courses 
                  WHERE teacher_id = ? AND course_id = ? AND term_id = ?";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $teacher_id);
        $stmt->bindParam(2, $this->id);
        $stmt->bindParam(3, $term_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Enroll student in course
    public function enrollStudent($student_id, $term_id) {
        // First check if both course and term are active
        $query = "SELECT c.id as course_id, t.id as term_id 
                  FROM " . $this->table_name . " c
                  JOIN terms t ON t.id = ?
                  WHERE c.id = ? AND c.status = 'active' AND t.status = 'active'";
        
        $checkStmt = $this->conn->prepare($query);
        $checkStmt->bindParam(1, $term_id);
        $checkStmt->bindParam(2, $this->id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() == 0) {
            // Either course or term is inactive
            return false;
        }
        
        $query = "INSERT INTO student_courses (student_id, course_id, term_id, status, created_at)
                  VALUES (?, ?, ?, 'active', NOW())";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $student_id);
        $stmt->bindParam(2, $this->id);
        $stmt->bindParam(3, $term_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update student grade
    public function updateStudentGrade($student_id, $term_id, $grade) {
        try {
            // First check if both course and term are active
            $checkQuery = "SELECT c.id as course_id, t.id as term_id 
                      FROM " . $this->table_name . " c
                      JOIN terms t ON t.id = ?
                      WHERE c.id = ? AND c.status = 'active' AND t.status = 'active'";
            
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(1, $term_id);
            $checkStmt->bindParam(2, $this->id);
            $checkStmt->execute();
            
            if($checkStmt->rowCount() == 0) {
                // Either course or term is inactive
                return false;
            }
            
            // First, check if updated_at column exists in the student_courses table
            $columnCheckQuery = "SHOW COLUMNS FROM student_courses LIKE 'updated_at'";
            $columnCheckStmt = $this->conn->query($columnCheckQuery);
            
            if ($columnCheckStmt->rowCount() > 0) {
                // Use query with updated_at if the column exists
                $query = "UPDATE student_courses
                          SET grade = ?, updated_at = NOW()
                          WHERE student_id = ? AND course_id = ? AND term_id = ?";
            } else {
                // Use query without updated_at if the column doesn't exist
                $query = "UPDATE student_courses
                          SET grade = ?
                          WHERE student_id = ? AND course_id = ? AND term_id = ?";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $grade);
            $stmt->bindParam(2, $student_id);
            $stmt->bindParam(3, $this->id);
            $stmt->bindParam(4, $term_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log error but don't expose PDO exceptions directly
            error_log("Grade update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get course schedule
    public function getSchedule($term_id) {
        $query = "SELECT cs.*, r.name as room_name, r.building
                  FROM course_schedule cs
                  JOIN rooms r ON cs.room_id = r.id
                  WHERE cs.course_id = ? AND cs.term_id = ?
                  ORDER BY cs.day_of_week, cs.start_time";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $term_id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>