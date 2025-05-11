<?php
// Include necessary files with absolute paths
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        
        // Initialize session security
        $this->initSessionSecurity();
    }

    // Initialize session security
    private function initSessionSecurity() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    // Login function
    public function login($email, $password, $remember = false) {
        // Check for brute force attempts
        if ($this->isBruteForce($email)) {
            $_SESSION['alert'] = "Çok fazla başarısız giriş denemesi. Lütfen daha sonra tekrar deneyin.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Set properties
        $this->user->email = $email;
        $this->user->password = $password;
        
        // Debug: Log login attempt
        error_log("Login attempt for: $email with password: $password");
        
        // Attempt login
        if($this->user->login()) {
            // Set session variables
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['name'] = $this->user->name . ' ' . $this->user->surname;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $this->user->role_id;
            $_SESSION['role_name'] = $this->user->role_name;
            $_SESSION['department_id'] = $this->user->department_id;
            $_SESSION['department_name'] = $this->user->department_name;
            $_SESSION['last_activity'] = time();
            
            // Debug: Log successful login
            error_log("User logged in successfully: {$this->user->id} - {$this->user->name} {$this->user->surname}");
            
            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberMeCookie($this->user->id);
            }
            
            // Set student/teacher specific data
            if($this->user->role_id == ROLE_STUDENT) {
                $_SESSION['student_id'] = $this->user->id;
                $_SESSION['student_number'] = $this->user->student_id;
            } else if($this->user->role_id == ROLE_TEACHER) {
                $_SESSION['teacher_id'] = $this->user->id;
            }
            
            // Clear failed login attempts
            $this->clearFailedAttempts($email);
            
            return true;
        }
        
        // Debug: Log failed login
        error_log("Login failed for: $email - Password verification failed");
        
        // Record failed login attempt
        $this->recordFailedAttempt($email);
        
        return false;
    }

    // Check for brute force attempts
    private function isBruteForce($email) {
        try {
            $query = "SELECT COUNT(*) as attempts FROM login_attempts 
                     WHERE email = :email AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['attempts'] >= 5; // Allow 5 attempts in 15 minutes
        } catch (PDOException $e) {
            // If table doesn't exist, return false (no brute force check)
            if ($e->getCode() == '42S02') { // Table doesn't exist
                return false;
            }
            throw $e;
        }
    }

    // Record failed login attempt
    private function recordFailedAttempt($email) {
        try {
            $query = "INSERT INTO login_attempts (email, timestamp) VALUES (:email, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
        } catch (PDOException $e) {
            // If table doesn't exist, silently fail
            if ($e->getCode() == '42S02') { // Table doesn't exist
                return;
            }
            throw $e;
        }
    }

    // Clear failed login attempts
    private function clearFailedAttempts($email) {
        try {
            $query = "DELETE FROM login_attempts WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
        } catch (PDOException $e) {
            // If table doesn't exist, silently fail
            if ($e->getCode() == '42S02') { // Table doesn't exist
                return;
            }
            throw $e;
        }
    }

    // Set remember me cookie
    private function setRememberMeCookie($user_id) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $query = "INSERT INTO remember_tokens (user_id, token, expires_at) 
                 VALUES (:user_id, :token, FROM_UNIXTIME(:expiry))";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expiry", $expiry);
        $stmt->execute();
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }

    // Logout function
    public function logout() {
        // Unset all session variables
        session_unset();
        
        // Destroy the session
        session_destroy();
        
        return true;
    }

    // Register function
    public function register($data) {
        // Validate data
        if(!$this->validateRegistrationData($data)) {
            return false;
        }
        
        // Check if email already exists
        $this->user->email = $data['email'];
        if($this->user->emailExists()) {
            $_SESSION['alert'] = "Bu e-posta adresi zaten kullanılmaktadır.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Set user data
        $this->user->name = $data['name'];
        $this->user->surname = $data['surname'];
        $this->user->email = $data['email'];
        $this->user->password = $data['password'];
        $this->user->role_id = $data['role_id'];
        $this->user->department_id = $data['department_id'];
        $this->user->student_id = $data['student_id'] ?? null;
        $this->user->status = 'active';
        
        // Validate role based on email domain
        if(!$this->user->validateRoleByEmail()) {
            $_SESSION['alert'] = "E-posta adresi kullanıcı rolü ile uyumlu değil.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Register user
        if($this->user->register()) {
            // Create student or teacher record if needed
            if($this->user->role_id == ROLE_STUDENT) {
                $this->createStudentRecord($this->user);
            } else if($this->user->role_id == ROLE_TEACHER) {
                $this->createTeacherRecord($this->user);
            }
            
            return true;
        }
        
        return false;
    }

    // Validate registration data
    private function validateRegistrationData($data) {
        // Check required fields
        if(empty($data['name']) || empty($data['surname']) || empty($data['email']) || 
           empty($data['password']) || empty($data['password_confirm']) || empty($data['role_id'])) {
            $_SESSION['alert'] = "Lütfen tüm zorunlu alanları doldurunuz.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Check if passwords match
        if($data['password'] !== $data['password_confirm']) {
            $_SESSION['alert'] = "Şifreler eşleşmiyor.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Check password length
        if(strlen($data['password']) < 6) {
            $_SESSION['alert'] = "Şifre en az 6 karakter olmalıdır.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Check if email is valid
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert'] = "Geçerli bir e-posta adresi giriniz.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Validate email domains
        if($data['role_id'] == ROLE_STUDENT && strpos($data['email'], STUDENT_EMAIL_DOMAIN) === false) {
            $_SESSION['alert'] = "Öğrenci hesapları için " . STUDENT_EMAIL_DOMAIN . " uzantılı e-posta kullanmalısınız.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        if($data['role_id'] == ROLE_TEACHER && strpos($data['email'], TEACHER_EMAIL_DOMAIN) === false) {
            $_SESSION['alert'] = "Öğretmen hesapları için " . TEACHER_EMAIL_DOMAIN . " uzantılı e-posta kullanmalısınız.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        return true;
    }

    // Create student record
    private function createStudentRecord($user) {
        // Get user ID of the newly created user
        $this->user->email = $user->email;
        $this->user->emailExists();
        
        $student = new Student($this->db);
        $student->user_id = $this->user->id;
        $student->student_id = $user->student_id;
        $student->name = $user->name;
        $student->surname = $user->surname;
        $student->department_id = $user->department_id;
        $student->status = 'active';
        $student->entry_year = date('Y');
        
        return $student->create();
    }

    // Create teacher record
    private function createTeacherRecord($user) {
        // Get user ID of the newly created user
        $this->user->email = $user->email;
        $this->user->emailExists();
        
        $teacher = new Teacher($this->db);
        $teacher->user_id = $this->user->id;
        $teacher->name = $user->name;
        $teacher->surname = $user->surname;
        $teacher->department_id = $user->department_id;
        $teacher->status = 'active';
        
        return $teacher->create();
    }

    // Change password
    public function changePassword($user_id, $current_password, $new_password, $confirm_password) {
        // Validate data
        if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['alert'] = "Lütfen tüm alanları doldurunuz.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        if($new_password !== $confirm_password) {
            $_SESSION['alert'] = "Yeni şifreler eşleşmiyor.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        if(strlen($new_password) < 6) {
            $_SESSION['alert'] = "Yeni şifre en az 6 karakter olmalıdır.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Get user
        $this->user->id = $user_id;
        if(!$this->user->readOne()) {
            $_SESSION['alert'] = "Kullanıcı bulunamadı.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Verify current password - direct compare now, no hashing
        if($current_password != $this->user->password) {
            $_SESSION['alert'] = "Mevcut şifre hatalı.";
            $_SESSION['alert_type'] = "danger";
            return false;
        }
        
        // Update password
        $this->user->password = $new_password;
        if($this->user->changePassword()) {
            $_SESSION['alert'] = "Şifreniz başarıyla değiştirildi.";
            $_SESSION['alert_type'] = "success";
            return true;
        }
        
        $_SESSION['alert'] = "Şifre değiştirme işlemi başarısız oldu.";
        $_SESSION['alert_type'] = "danger";
        return false;
    }

    
}
?>