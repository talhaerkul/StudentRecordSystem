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
    public $student_id;
    public $phone;
    public $status;
    public $created_at;
    public $updated_at;
    public $last_login;

    // Teacher-specific properties
    public $is_teacher;
    public $title;
    public $specialization;

    // Student-specific properties
    public $is_student;
    public $birthdate;
    public $address;
    public $entry_year;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Register user
    public function register() {
        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                      name = :name,
                      surname = :surname,
                      email = :email,
                      password = :password,
                      role_id = :role_id,
                      department_id = :department_id,
                      student_id = :student_id,
                      phone = :phone,
                      status = :status,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->surname = htmlspecialchars(strip_tags($this->surname));
        $this->email = htmlspecialchars(strip_tags($this->email));
        // Password is already sanitized but not hashed
        $this->role_id = htmlspecialchars(strip_tags($this->role_id));
        $this->department_id = $this->department_id ? htmlspecialchars(strip_tags($this->department_id)) : null;
        $this->student_id = $this->student_id ? htmlspecialchars(strip_tags($this->student_id)) : null;
        $this->phone = $this->phone ? htmlspecialchars(strip_tags($this->phone)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":surname", $this->surname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash); // Use the hashed password
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":status", $this->status);
        
        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Login user
    public function login() {
        // First, get the user based only on email
        $query = "SELECT u.*, u.password as password_hash, r.name as role_name, 
                  COALESCE(d.name, 'No Department') as department_name,
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

        // Bind email value
        $stmt->bindParam(":email", $this->email);

        // Execute query
        $stmt->execute();
        $num = $stmt->rowCount();

        // Debug
        error_log("Found {$num} users with matching email");

        // If user with this email exists
        if ($num > 0) {
            // Get user data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stored_password = $row['password_hash'];
            
            $passwordValid = false;
            
            // Try password_verify first (for hashed passwords)
            if (password_verify($this->password, $stored_password)) {
                $passwordValid = true;
                error_log("Password verified with password_verify");
            } 
            // If that fails, try direct comparison (for legacy plain text passwords)
            else if ($this->password === $stored_password) {
                $passwordValid = true;
                // Consider hashing the password here for future security
                $this->updatePasswordToHash($row['id'], $this->password);
                error_log("Password verified with direct comparison, updated to hashed version");
            }
            
            if ($passwordValid) {
                error_log("User found: ID={$row['id']}, Name={$row['name']} {$row['surname']}");
                
                // Set all properties
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->surname = $row['surname'];
                $this->role_id = $row['role_id'];
                $this->role_name = $row['role_name'];
                $this->department_id = $row['department_id'] ? $row['department_id'] : null;
                $this->department_name = $row['department_name'];
                $this->student_id = $row['student_id'];
                $this->phone = $row['phone'];
                $this->title = $row['title'];
                $this->specialization = $row['specialization'];
                $this->birthdate = $row['birthdate'];
                $this->address = $row['address'];
                $this->scholarship_name = $row['scholarship_name'];
                $this->entry_year = $row['entry_year'];
                $this->is_teacher = $row['is_teacher'];
                $this->is_student = $row['is_student'];
                $this->status = $row['status'];
                $this->last_login = $row['last_login'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                // Update last login
                $this->updateLastLogin();
                
                return true;
            } else {
                error_log("Password verification failed for user ID={$row['id']}");
            }
        } else {
            error_log("No user found with email: {$this->email}");
        }

        return false;
    }

    // Update password to hashed version (migration for legacy plain text passwords)
    private function updatePasswordToHash($user_id, $plain_password) {
        $password_hash = password_hash($plain_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password_hash, updated_at = NOW() 
                  WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
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
        $query = "SELECT u.*, r.name as role_name, d.name as department_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  ORDER BY u.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function readStudents() {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.role_id = 4
                  ORDER BY u.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    // Read one user
    public function readOne() {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
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
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      name = :name,
                      surname = :surname,
                      email = :email,
                      role_id = :role_id,
                      department_id = :department_id,
                      student_id = :student_id,
                      phone = :phone,
                      status = :status,
                      updated_at = NOW()
                  WHERE
                      id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->surname = htmlspecialchars(strip_tags($this->surname));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role_id = htmlspecialchars(strip_tags($this->role_id));
        $this->department_id = $this->department_id ? htmlspecialchars(strip_tags($this->department_id)) : null;
        $this->student_id = $this->student_id ? htmlspecialchars(strip_tags($this->student_id)) : null;
        $this->phone = $this->phone ? htmlspecialchars(strip_tags($this->phone)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":surname", $this->surname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":phone", $this->phone);
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
        // Hash the password before saving
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . "
                  SET password = :password,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
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
        // Transkrip erişimi için role bağlı erişim kontrolü TranscriptController üzerinden yapılıyor
        // Bu metot sadece sorgu çalıştırıyor ve veri döndürüyor
        
        $query = "SELECT t.id as term_id, t.name as term_name, t.start_date, 
                  c.id as course_id, c.code, c.name as course_name, c.credit, 
                  sc.grade, tc.teacher_id,
                  CASE 
                    WHEN sc.grade IS NOT NULL THEN 
                      (SELECT cgs.letter 
                       FROM course_grade_scales cgs 
                       WHERE cgs.course_id = sc.course_id 
                       AND cgs.teacher_id = tc.teacher_id 
                       AND cgs.term_id = sc.term_id 
                       AND sc.grade BETWEEN cgs.min_grade AND cgs.max_grade
                       LIMIT 1)
                    ELSE 'NA'
                  END as letter_grade,
                  CASE 
                    WHEN sc.grade IS NOT NULL THEN 
                      (SELECT cgs.grade_point 
                       FROM course_grade_scales cgs 
                       WHERE cgs.course_id = sc.course_id 
                       AND cgs.teacher_id = tc.teacher_id 
                       AND cgs.term_id = sc.term_id 
                       AND sc.grade BETWEEN cgs.min_grade AND cgs.max_grade
                       LIMIT 1)
                    ELSE 0.0
                  END as point
                  FROM student_courses sc
                  JOIN courses c ON sc.course_id = c.id
                  JOIN terms t ON sc.term_id = t.id
                  JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
                  WHERE sc.student_id = ?
                  ORDER BY t.start_date ASC, c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt;
    }

    // Read all users with specific role
    public function readByRole($role_id) {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.role_id = ?
                  ORDER BY u.surname, u.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $role_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read specific user by ID
    public function readById($user_id) {
        $query = "SELECT u.*, r.name as role_name, d.name as department_name
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.role_id = r.id
                  LEFT JOIN departments d ON u.department_id = d.id
                  WHERE u.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get all students across all courses for a teacher
    public function getTeacherStudents($term_id = null) {
        if (!$this->is_teacher) {
            return false;
        }
        
        $queryBase = "SELECT DISTINCT u.id, u.student_id, u.name, u.surname, u.email, u.department_id, 
                      d.name as department_name, 
                      (SELECT COUNT(*) FROM student_courses sc 
                       JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id 
                       WHERE tc.teacher_id = ? AND sc.student_id = u.id) as course_count
                      FROM student_courses sc
                      JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
                      JOIN users u ON sc.student_id = u.id
                      LEFT JOIN departments d ON u.department_id = d.id
                      WHERE tc.teacher_id = ?";
        
        if ($term_id) {
            $queryBase .= " AND sc.term_id = ?";
        }
        
        $queryBase .= " ORDER BY u.surname ASC, u.name ASC";
        
        $stmt = $this->conn->prepare($queryBase);
        
        if ($term_id) {
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $this->id);
            $stmt->bindParam(3, $term_id);
        } else {
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $this->id);
        }
        
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get student's courses for a specific teacher
    public function getStudentCoursesForTeacher($student_id, $term_id = null) {
        if (!$this->is_teacher) {
            return false;
        }
        
        $query = "SELECT c.id, c.code, c.name, c.credit, t.id as term_id, t.name as term_name, sc.grade
                  FROM student_courses sc
                  JOIN courses c ON sc.course_id = c.id
                  JOIN teacher_courses tc ON sc.course_id = tc.course_id AND sc.term_id = tc.term_id
                  JOIN terms t ON sc.term_id = t.id
                  WHERE tc.teacher_id = ? AND sc.student_id = ?";
        
        if ($term_id) {
            $query .= " AND sc.term_id = ?";
        }
        
        $query .= " ORDER BY t.start_date DESC, c.code ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($term_id) {
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $student_id);
            $stmt->bindParam(3, $term_id);
        } else {
            $stmt->bindParam(1, $this->id);
            $stmt->bindParam(2, $student_id);
        }
        
        $stmt->execute();
        
        return $stmt;
    }
}
?>