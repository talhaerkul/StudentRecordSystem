<?php
class Announcement {
    // Database connection and table name
    private $conn;
    private $table_name = "announcements";

    // Object properties
    public $id;
    public $title;
    public $content;
    public $user_id;
    public $role_id;
    public $department_id;
    public $course_id;
    public $start_date;
    public $end_date;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all announcements - only active and valid date range announcements for students and teachers
    public function readAll() {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.status = 'active'
                  AND (a.start_date IS NULL OR a.start_date <= NOW())
                  AND (a.end_date IS NULL OR a.end_date >= NOW())
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read all announcements for admin - both active and inactive, regardless of date
    public function readAllForAdmin() {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  ORDER BY a.status DESC, a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read announcements by user role - this function is kept for role-based filtering
    public function readByUserRole($role_id, $department_id, $status = 'active') {
        error_log("Reading announcements for role_id: " . $role_id . ", department_id: " . ($department_id ? $department_id : "NULL") . ", status: " . $status);
        
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, r.name as role_name, d.name as department_name, c.name as course_name 
                 FROM " . $this->table_name . " a
                 LEFT JOIN users u ON a.user_id = u.id
                 LEFT JOIN roles r ON a.role_id = r.id
                 LEFT JOIN departments d ON a.department_id = d.id
                 LEFT JOIN courses c ON a.course_id = c.id
                 WHERE a.status = :status 
                 AND (a.role_id = :role_id OR a.role_id IS NULL)
                 AND (
                     a.department_id IS NULL 
                     OR (:department_id IS NULL AND a.department_id IS NULL)
                     OR (:department_id IS NOT NULL AND a.department_id = :department_id)
                 )";
        
        // Add date constraints for active announcements for non-admin users
        if ($status == 'active' && $role_id != ROLE_ADMIN) {
            $query .= " AND (a.start_date IS NULL OR a.start_date <= NOW())
                       AND (a.end_date IS NULL OR a.end_date >= NOW())";
        }
                 
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":role_id", $role_id);
        $stmt->bindParam(":department_id", $department_id);
        $stmt->bindParam(":status", $status);
        
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error executing query: " . $e->getMessage());
        }
        
        return $stmt;
    }

    // Create announcement - role-based security is maintained here
    public function create() {
        error_log("Creating new announcement: " . $this->title);
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET title = :title,
                      content = :content,
                      user_id = :user_id,
                      role_id = :role_id,
                      department_id = :department_id,
                      course_id = :course_id,
                      start_date = :start_date,
                      end_date = :end_date,
                      status = :status,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->role_id = $this->role_id ? htmlspecialchars(strip_tags($this->role_id)) : null;
        $this->department_id = $this->department_id ? htmlspecialchars(strip_tags($this->department_id)) : null;
        $this->course_id = $this->course_id ? htmlspecialchars(strip_tags($this->course_id)) : null;
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":status", $this->status);
        
        // Execute query
        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                error_log("Announcement created successfully with ID: " . $this->id);
                return true;
            } else {
                error_log("Error creating announcement: " . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Exception creating announcement: " . $e->getMessage());
            return false;
        }
    }

    // Read one announcement - available to all
    public function readOne() {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->user_id = $row['user_id'];
            $this->user_name = $row['user_name'] . ' ' . $row['user_surname'];
            $this->role_id = $row['role_id'];
            $this->role_name = $row['role_name'];
            $this->department_id = $row['department_id'];
            $this->department_name = $row['department_name'];
            $this->course_id = $row['course_id'];
            $this->course_name = $row['course_name'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update announcement - role check should happen in controller
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title,
                      content = :content,
                      role_id = :role_id,
                      department_id = :department_id,
                      course_id = :course_id,
                      start_date = :start_date,
                      end_date = :end_date,
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->role_id = $this->role_id ? htmlspecialchars(strip_tags($this->role_id)) : null;
        $this->department_id = $this->department_id ? htmlspecialchars(strip_tags($this->department_id)) : null;
        $this->course_id = $this->course_id ? htmlspecialchars(strip_tags($this->course_id)) : null;
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":role_id", $this->role_id);
        $stmt->bindParam(":department_id", $this->department_id);
        $stmt->bindParam(":course_id", $this->course_id);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete announcement - role check should happen in controller
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

    // Read announcements by creator ID - allows users to see announcements they created
    // Teachers will see all their own announcements regardless of dates
    public function readByCreatorId($user_id, $status = 'active') {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.user_id = ? AND a.status = ?
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $status);
        $stmt->execute();
        
        return $stmt;
    }
}
?>