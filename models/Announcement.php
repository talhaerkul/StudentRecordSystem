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

    // Read all announcements
    public function readAll() {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.status = 'active'
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read announcements by user role
    public function readByUserRole($user_role, $user_dept) {
        $query = "SELECT a.*, u.name as user_name, u.surname as user_surname, 
                  r.name as role_name, d.name as department_name, c.name as course_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN departments d ON a.department_id = d.id
                  LEFT JOIN courses c ON a.course_id = c.id
                  WHERE a.status = 'active' 
                  AND (a.role_id IS NULL OR a.role_id = ?)
                  AND (a.department_id IS NULL OR a.department_id = ?)
                  AND NOW() BETWEEN a.start_date AND a.end_date
                  ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_role);
        $stmt->bindParam(2, $user_dept);
        $stmt->execute();
        
        return $stmt;
    }

    // Create announcement
    public function create() {
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
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read one announcement
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

    // Update announcement
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

    // Delete announcement
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
}
?>

