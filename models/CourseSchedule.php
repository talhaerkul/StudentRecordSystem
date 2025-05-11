<?php
class CourseSchedule {
    // Database connection and table name
    private $conn;
    private $table_name = "course_schedule";

    // Object properties
    public $id;
    public $course_id;
    public $teacher_id;
    public $term_id;
    public $day_of_week;
    public $start_time;
    public $end_time;
    public $classroom;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all course schedules for a specific term
    public function readByTerm($term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, 
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all course schedules for a specific department in a term
    public function readByDepartmentAndTerm($department_id, $term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, 
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND c.department_id = ? AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->bindParam(2, $department_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all course schedules for a specific year/class level in a department and term
    public function readByYearAndDepartmentAndTerm($year, $department_id, $term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, c.year as course_year,
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND c.department_id = ? AND c.year = ? 
                  AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->bindParam(2, $department_id);
        $stmt->bindParam(3, $year);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all course schedules for a specific year/class level in a term
    public function readByYearAndTerm($year, $term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, c.year as course_year,
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND c.year = ? AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->bindParam(2, $year);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all course schedules for a specific teacher in a term
    public function readByTeacherAndTerm($teacher_id, $term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, 
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND s.teacher_id = ? AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->bindParam(2, $teacher_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all course schedules for a specific student in a term
    public function readByStudentAndTerm($student_id, $term_id) {
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, 
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  LEFT JOIN student_courses sc ON (sc.course_id = c.id AND sc.term_id = s.term_id)
                  WHERE s.term_id = ? AND sc.student_id = ? AND c.status = 'active' AND t.status = 'active'
                  ORDER BY s.day_of_week, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $term_id);
        $stmt->bindParam(2, $student_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Create course schedule
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET course_id = :course_id,
                      teacher_id = :teacher_id,
                      term_id = :term_id,
                      day_of_week = :day_of_week,
                      start_time = :start_time,
                      end_time = :end_time,
                      classroom = :classroom,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->teacher_id = htmlspecialchars(strip_tags($this->teacher_id));
        $this->term_id = htmlspecialchars(strip_tags($this->term_id));
        $this->day_of_week = htmlspecialchars(strip_tags($this->day_of_week));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->classroom = htmlspecialchars(strip_tags($this->classroom));
        
        // Bind values
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":teacher_id", $this->teacher_id);
        $stmt->bindParam(":term_id", $this->term_id);
        $stmt->bindParam(":day_of_week", $this->day_of_week);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":classroom", $this->classroom);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read one course schedule
    public function readOne($id = null) {
        // Use the parameter if provided, otherwise use the instance property
        $schedule_id = $id ? $id : $this->id;
        
        $query = "SELECT s.*, c.code as course_code, c.name as course_name, 
                  CONCAT(u.name, ' ', u.surname) as teacher_name,
                  d.name as department_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN courses c ON s.course_id = c.id
                  LEFT JOIN users u ON s.teacher_id = u.id
                  LEFT JOIN departments d ON c.department_id = d.id
                  LEFT JOIN terms t ON s.term_id = t.id
                  WHERE s.id = ? AND c.status = 'active' AND t.status = 'active'
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $schedule_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            if (!$id) {
                // Only set instance properties if we're not just checking
                $this->course_id = $row['course_id'];
                $this->teacher_id = $row['teacher_id'];
                $this->term_id = $row['term_id'];
                $this->day_of_week = $row['day_of_week'];
                $this->start_time = $row['start_time'];
                $this->end_time = $row['end_time'];
                $this->classroom = $row['classroom'];
                $this->course_code = $row['course_code'];
                $this->course_name = $row['course_name'];
                $this->teacher_name = $row['teacher_name'];
                $this->department_name = $row['department_name'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
            }
            
            return $row;
        }
        
        return false;
    }

    // Update course schedule
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET course_id = :course_id,
                      teacher_id = :teacher_id,
                      term_id = :term_id,
                      day_of_week = :day_of_week,
                      start_time = :start_time,
                      end_time = :end_time,
                      classroom = :classroom,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->teacher_id = htmlspecialchars(strip_tags($this->teacher_id));
        $this->term_id = htmlspecialchars(strip_tags($this->term_id));
        $this->day_of_week = htmlspecialchars(strip_tags($this->day_of_week));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->classroom = htmlspecialchars(strip_tags($this->classroom));
        
        // Bind values
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":teacher_id", $this->teacher_id);
        $stmt->bindParam(":term_id", $this->term_id);
        $stmt->bindParam(":day_of_week", $this->day_of_week);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":classroom", $this->classroom);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete course schedule
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

    // Check for scheduling conflicts
    public function hasConflict() {
        // Check for classroom conflict
        $query1 = "SELECT s.id FROM " . $this->table_name . " s
                  JOIN courses c ON s.course_id = c.id
                  JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND s.day_of_week = ? AND s.classroom = ? AND s.id != ?
                  AND ((s.start_time <= ? AND s.end_time > ?) OR 
                       (s.start_time < ? AND s.end_time >= ?) OR
                       (s.start_time >= ? AND s.end_time <= ?))
                  AND c.status = 'active' AND t.status = 'active'";
        
        $stmt1 = $this->conn->prepare($query1);
        $id_value = $this->id ? $this->id : 0;
        
        $stmt1->bindParam(1, $this->term_id);
        $stmt1->bindParam(2, $this->day_of_week);
        $stmt1->bindParam(3, $this->classroom);
        $stmt1->bindParam(4, $id_value);
        $stmt1->bindParam(5, $this->start_time);
        $stmt1->bindParam(6, $this->start_time);
        $stmt1->bindParam(7, $this->end_time);
        $stmt1->bindParam(8, $this->end_time);
        $stmt1->bindParam(9, $this->start_time);
        $stmt1->bindParam(10, $this->end_time);
        
        $stmt1->execute();
        
        if($stmt1->rowCount() > 0) {
            return true; // Classroom conflict
        }
        
        // Check for teacher conflict
        $query2 = "SELECT s.id FROM " . $this->table_name . " s
                  JOIN courses c ON s.course_id = c.id
                  JOIN terms t ON s.term_id = t.id
                  WHERE s.term_id = ? AND s.day_of_week = ? AND s.teacher_id = ? AND s.id != ?
                  AND ((s.start_time <= ? AND s.end_time > ?) OR 
                       (s.start_time < ? AND s.end_time >= ?) OR
                       (s.start_time >= ? AND s.end_time <= ?))
                  AND c.status = 'active' AND t.status = 'active'";
        
        $stmt2 = $this->conn->prepare($query2);
        
        $stmt2->bindParam(1, $this->term_id);
        $stmt2->bindParam(2, $this->day_of_week);
        $stmt2->bindParam(3, $this->teacher_id);
        $stmt2->bindParam(4, $id_value);
        $stmt2->bindParam(5, $this->start_time);
        $stmt2->bindParam(6, $this->start_time);
        $stmt2->bindParam(7, $this->end_time);
        $stmt2->bindParam(8, $this->end_time);
        $stmt2->bindParam(9, $this->start_time);
        $stmt2->bindParam(10, $this->end_time);
        
        $stmt2->execute();
        
        if($stmt2->rowCount() > 0) {
            return true; // Teacher conflict
        }
        
        // Check if teacher belongs to the same department as the course
        $query3 = "SELECT 1 FROM courses c
                  JOIN users u ON u.id = ?
                  WHERE c.id = ? AND c.department_id = u.department_id
                  AND c.status = 'active'";
        
        $stmt3 = $this->conn->prepare($query3);
        $stmt3->bindParam(1, $this->teacher_id);
        $stmt3->bindParam(2, $this->course_id);
        $stmt3->execute();
        
        if($stmt3->rowCount() == 0) {
            return true; // Teacher is not from the same department as the course
        }
        
        return false; // No conflicts
    }
}
?>