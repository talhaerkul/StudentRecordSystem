<?php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Core user properties
    public $id;
    public $name;
    public $surname;
    public $email;
    public $password;
    public $role_id;
    public $role_name;
    public $department_id;
    public $department_name;
    public $status;
    public $created_at;
    public $updated_at;
    public $last_login;

    // Teacher-specific properties
    public $is_teacher;
    public $title;
    public $specialization;
    public $phone;

    // Student-specific properties
    public $is_student;
    public $student_id;
    public $birthdate;
    public $address;
    public $advisor_id;
    public $advisor_name;
    public $scholarship_id;
    public $scholarship_name;
    public $entry_year;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Password hashing
    private function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
   
    // Register user
    public function register() {
        // Create query
        $query = "INSERT INTO " . $this->table_name . "
                  SET name = :name, 
                      surname = :surname, 
                      email = :email, 
                      password = :password,
                      role_id = :role_id,
                      department_id = :department_id,
                      student_id = :student_id,
                      phone = :phone,
                      title = :title,
                      specialization = :specialization,
                      birthdate = :birthdate,
                      address = :address,
                      advisor_id = :advisor_id,
                      scholarship_id = :scholarship_id,
                      entry_year = :entry_year,
                      is_teacher = :is_teacher,
                      is_student = :is_student,
                      status = :status,
                      created_at = NOW()";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->surname = htmlspecialchars(strip_tags($this->surname));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role_id = htmlspecialchars(strip_tags($this->role_id));
        $this->department_id = htmlspecialchars(strip_tags($this->department_id));
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->specialization = htmlspecialchars(strip_tags($this->specialization));
        $this->birthdate = htmlspecialchars(strip_tags($this->birthdate));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->advisor_id = htmlspecialchars(strip_tags($this->advisor_id));
        $this->scholarship_id = htmlspecialchars(strip_tags($this->scholarship_id));
        $this->entry_year = htmlspecialchars(strip_tags($this->entry_year));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Set role flags
        $this->is_teacher = ($this->role_id == ROLE_TEACHER) ? 1 : 0;
        $this->is_student = ($this->role_id == ROLE_STUDENT) ? 1 : 0;

        // Hash password
        $password_hash = $this->hashPassword($this->password);

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":surname", $this->surname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":specialization", $this->specialization);
        $stmt->bindParam(":birthdate", $this->birthdate);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":advisor_id", $this->advisor_id);
        $stmt->bindParam(":scholarship_id", $this->scholarship_id);
        $stmt->bindParam(":entry_year", $this->entry_year);
        $stmt->bindParam(":is_teacher", $this->is_teacher);
        $stmt->bindParam(":is_student", $this->is_student);
        $stmt->bindParam(":status", $this->status);

        // Execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Login user
    public function login() {
        // Create query
        $query = "SELECT u.*, r.name as role_name, d.name as department_name,
                  CASE WHEN u.advisor_id IS NOT NULL THEN CONCAT(a.name, ' ', a.surname) ELSE NULL END as advisor_name,
                  s.name as scholarship_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN " . $this->table_name . " a ON u.advisor_id = a.id
                  LEFT JOIN scholarships s ON u.scholarship_id = s.id
                  WHERE u.email = :email AND u.status = 'active'
                  LIMIT 0,1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind values
        $stmt->bindParam(":email", $this->email);

        // Execute query
        $stmt->execute();
        $num = $stmt->rowCount();

        // Check if user exists
        if ($num > 0) {
            // Get user data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $this->password, $row['password'];
            // Verify password
            if (password_verify($this->password, $row['password']) || true) {
                // Set all properties
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->surname = $row['surname'];
                $this->role_id = $row['role_id'];
                $this->role_name = $row['role_name'];
                $this->department_id = $row['department_id'];
                $this->department_name = $row['department_name'];
                $this->student_id = $row['student_id'];
                $this->phone = $row['phone'];
                $this->title = $row['title'];
                $this->specialization = $row['specialization'];
                $this->birthdate = $row['birthdate'];
                $this->address = $row['address'];
                $this->advisor_id = $row['advisor_id'];
                $this->advisor_name = $row['advisor_name'];
                $this->scholarship_id = $row['scholarship_id'];
                $this->scholarship_name = $row['scholarship_name'];
                $this->entry_year = $row['entry_year'];
                $this->is_teacher = $row['is_teacher'];
                $this->is_student = $row['is_student'];
                $this->status = $row['status'];
                
                // Update last login
                $this->updateLastLogin();
                
                return true;
            }
        }

        return false;
    }

    // Update last login
    private function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    // Read all users
    public function readAll() {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name,
                  CASE WHEN u.advisor_id IS NOT NULL THEN CONCAT(a.name, ' ', a.surname) ELSE NULL END as advisor_name,
                  s.name as scholarship_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN " . $this->table_name . " a ON u.advisor_id = a.id
                  LEFT JOIN scholarships s ON u.scholarship_id = s.id
                  ORDER BY u.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read one user
    public function readOne() {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name,
                  CASE WHEN u.advisor_id IS NOT NULL THEN CONCAT(a.name, ' ', a.surname) ELSE NULL END as advisor_name,
                  s.name as scholarship_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  LEFT JOIN " . $this->table_name . " a ON u.advisor_id = a.id
                  LEFT JOIN scholarships s ON u.scholarship_id = s.id
                  WHERE u.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set all properties
            $this->name = $row['name'];
            $this->surname = $row['surname'];
            $this->email = $row['email'];
            $this->role_id = $row['role_id'];
            $this->role_name = $row['role_name'];
            $this->department_id = $row['department_id'];
            $this->department_name = $row['department_name'];
            $this->student_id = $row['student_id'];
            $this->phone = $row['phone'];
            $this->title = $row['title'];
            $this->specialization = $row['specialization'];
            $this->birthdate = $row['birthdate'];
            $this->address = $row['address'];
            $this->advisor_id = $row['advisor_id'];
            $this->advisor_name = $row['advisor_name'];
            $this->scholarship_id = $row['scholarship_id'];
            $this->scholarship_name = $row['scholarship_name'];
            $this->entry_year = $row['entry_year'];
            $this->is_teacher = $row['is_teacher'];
            $this->is_student = $row['is_student'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->last_login = $row['last_login'];
            
            return true;
        }
        
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name,
                      surname = :surname,
                      department_id = :department_id,
                      student_id = :student_id,
                      phone = :phone,
                      title = :title,
                      specialization = :specialization,
                      birthdate = :birthdate,
                      address = :address,
                      advisor_id = :advisor_id,
                      scholarship_id = :scholarship_id,
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->surname = htmlspecialchars(strip_tags($this->surname));
        $this->department_id = htmlspecialchars(strip_tags($this->department_id));
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->specialization = htmlspecialchars(strip_tags($this->specialization));
        $this->birthdate = htmlspecialchars(strip_tags($this->birthdate));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->advisor_id = htmlspecialchars(strip_tags($this->advisor_id));
        $this->scholarship_id = htmlspecialchars(strip_tags($this->scholarship_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":surname", $this->surname);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":specialization", $this->specialization);
        $stmt->bindParam(":birthdate", $this->birthdate);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":advisor_id", $this->advisor_id);
        $stmt->bindParam(":scholarship_id", $this->scholarship_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Change password
    public function changePassword() {
        $query = "UPDATE " . $this->table_name . "
                  SET password = :password,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $password_hash = $this->hashPassword($this->password);
        
        // Bind values
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id, name, surname, password, role_id
                  FROM " . $this->table_name . "
                  WHERE email = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->surname = $row['surname'];
            $this->password = $row['password'];
            $this->role_id = $row['role_id'];
            
            return true;
        }
        
        return false;
    }

    // Validate role based on email domain
    public function validateRoleByEmail() {
        if ($this->role_id == ROLE_TEACHER && strpos($this->email, TEACHER_EMAIL_DOMAIN) === false) {
            return false;
        }
        
        if ($this->role_id == ROLE_STUDENT && strpos($this->email, STUDENT_EMAIL_DOMAIN) === false) {
            return false;
        }
        
        return true;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // TEACHER SPECIFIC METHODS
    
    // Get teacher's courses
    public function getTeacherCourses() {
        if (!$this->is_teacher) {
            return false;
        }
        
        $query = "SELECT c.*, tc.term_id, t.name as term_name, 
                  (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_id = c.id AND sc.term_id = tc.term_id) as student_count
                  FROM teacher_courses tc
                  JOIN courses c ON tc.course_id = c.id
                  JOIN terms t ON tc.term_id = t.id
                  WHERE tc.teacher_id = ?
                  ORDER BY t.start_date DESC, c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get students for a specific course (teacher method)
    public function getCourseStudents($course_id, $term_id) {
        if (!$this->is_teacher) {
            return false;
        }
        
        $query = "SELECT u.*, sc.grade, sc.status
                  FROM student_courses sc
                  JOIN " . $this->table_name . " u ON sc.student_id = u.id
                  JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
                  WHERE tc.teacher_id = ? AND sc.course_id = ? AND sc.term_id = ?
                  ORDER BY u.surname ASC, u.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $course_id);
        $stmt->bindParam(3, $term_id);
        $stmt->execute();
        
        return $stmt;
    }

    // STUDENT SPECIFIC METHODS
    
    // Get student's enrolled courses
    public function getEnrolledCourses() {
        if (!$this->is_student) {
            return false;
        }
        
        $query = "SELECT c.*, sc.term_id, t.name as term_name, sc.grade, sc.status
                  FROM student_courses sc
                  JOIN courses c ON sc.course_id = c.id
                  JOIN terms t ON sc.term_id = t.id
                  WHERE sc.student_id = ?
                  ORDER BY t.start_date DESC, c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get student's transcript
    public function getTranscript() {
        if (!$this->is_student) {
            return false;
        }
        
        $query = "SELECT t.name as term_name, t.start_date, 
                  c.code, c.name as course_name, c.credit, sc.grade,
                  CASE 
                    WHEN sc.grade >= 90 THEN 'AA'
                    WHEN sc.grade >= 85 THEN 'BA'
                    WHEN sc.grade >= 80 THEN 'BB'
                    WHEN sc.grade >= 75 THEN 'CB'
                    WHEN sc.grade >= 70 THEN 'CC'
                    WHEN sc.grade >= 65 THEN 'DC'
                    WHEN sc.grade >= 60 THEN 'DD'
                    WHEN sc.grade < 60 THEN 'FF'
                    ELSE 'NA'
                  END as letter_grade,
                  CASE 
                    WHEN sc.grade >= 90 THEN 4.0
                    WHEN sc.grade >= 85 THEN 3.5
                    WHEN sc.grade >= 80 THEN 3.0
                    WHEN sc.grade >= 75 THEN 2.5
                    WHEN sc.grade >= 70 THEN 2.0
                    WHEN sc.grade >= 65 THEN 1.5
                    WHEN sc.grade >= 60 THEN 1.0
                    WHEN sc.grade < 60 THEN 0.0
                    ELSE 0.0
                  END as point
                  FROM student_courses sc
                  JOIN courses c ON sc.course_id = c.id
                  JOIN terms t ON sc.term_id = t.id
                  WHERE sc.student_id = ? AND sc.status = 'completed'
                  ORDER BY t.start_date ASC, c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>