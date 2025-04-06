<?php
class Department {
    // Database connection and table name
    private $conn;
    private $table_name = "departments";

    // Object properties
    public $id;
    public $name;
    public $code;
    public $faculty_id;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all departments
    public function readAll() {
        $query = "SELECT d.*, f.name as faculty_name
                  FROM " . $this->table_name . " d
                  LEFT JOIN faculties f ON d.faculty_id = f.id
                  WHERE d.status = 'active'
                  ORDER BY d.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Create department
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name,
                      code = :code,
                      faculty_id = :faculty_id,
                      status = :status,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->faculty_id = htmlspecialchars(strip_tags($this->faculty_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":faculty_id", $this->faculty_id);
        $stmt->bindParam(":status", $this->status);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read one department
    public function readOne() {
        $query = "SELECT d.*, f.name as faculty_name
                  FROM " . $this->table_name . " d
                  LEFT JOIN faculties f ON d.faculty_id = f.id
                  WHERE d.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->name = $row['name'];
            $this->code = $row['code'];
            $this->faculty_id = $row['faculty_id'];
            $this->faculty_name = $row['faculty_name'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update department
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name,
                      code = :code,
                      faculty_id = :faculty_id,
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->faculty_id = htmlspecialchars(strip_tags($this->faculty_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":code", $this->code);
        $stmt->bindParam(":faculty_id", $this->faculty_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete department
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
