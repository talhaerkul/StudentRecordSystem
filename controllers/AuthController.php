<?php
// Include necessary files
require_once 'config/database.php';
require_once 'models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Login function
    public function login($email, $password) {
        // Set properties
        $this->user->email = $email;
        $this->user->password = $password;
        
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
            
            // Set student/teacher specific data
            if($this->user->role_id == ROLE_STUDENT) {
                    $_SESSION['student_id'] = $this->user->id;
                    $_SESSION['student_number'] = $this->user->student_id;
            } else if($this->user->role_id == ROLE_TEACHER) {
                    $_SESSION['teacher_id'] = $this->user->id;
            }
            
            return true;
        }
        
        return false;
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
        
        // Verify current password
        if(!password_verify($current_password, $this->user->password)) {
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

