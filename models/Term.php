<?php
class Term {
    // Database connection and table name
    private $conn;
    private $table_name = "terms";

    // Object properties
    public $id;
    public $name;
    public $start_date;
    public $end_date;
    public $status;
    public $is_current;
    public $course_selection_start;
    public $course_selection_end;
    public $is_course_selection_active;
    public $created_at;
    public $updated_at;

    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all terms
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY start_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Read active terms
    public function readActive() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'active' 
                  ORDER BY start_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Create term
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name,
                      start_date = :start_date,
                      end_date = :end_date,
                      status = :status,
                      course_selection_start = :course_selection_start,
                      course_selection_end = :course_selection_end,
                      is_course_selection_active = :is_course_selection_active,
                      created_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->course_selection_start = $this->course_selection_start ? htmlspecialchars(strip_tags($this->course_selection_start)) : null;
        $this->course_selection_end = $this->course_selection_end ? htmlspecialchars(strip_tags($this->course_selection_end)) : null;
        $this->is_course_selection_active = $this->is_course_selection_active ? 1 : 0;
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":course_selection_start", $this->course_selection_start);
        $stmt->bindParam(":course_selection_end", $this->course_selection_end);
        $stmt->bindParam(":is_course_selection_active", $this->is_course_selection_active);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read one term
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->name = $row['name'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->status = $row['status'];
            $this->is_current = $row['is_current'];
            $this->course_selection_start = $row['course_selection_start'];
            $this->course_selection_end = $row['course_selection_end'];
            $this->is_course_selection_active = $row['is_course_selection_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update term
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name,
                      start_date = :start_date,
                      end_date = :end_date,
                      status = :status,
                      course_selection_start = :course_selection_start,
                      course_selection_end = :course_selection_end,
                      is_course_selection_active = :is_course_selection_active,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->course_selection_start = $this->course_selection_start ? htmlspecialchars(strip_tags($this->course_selection_start)) : null;
        $this->course_selection_end = $this->course_selection_end ? htmlspecialchars(strip_tags($this->course_selection_end)) : null;
        $this->is_course_selection_active = $this->is_course_selection_active ? 1 : 0;
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":end_date", $this->end_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":course_selection_start", $this->course_selection_start);
        $stmt->bindParam(":course_selection_end", $this->course_selection_end);
        $stmt->bindParam(":is_course_selection_active", $this->is_course_selection_active);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete term
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

    // Get current term
    public function getCurrentTerm() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE NOW() BETWEEN start_date AND end_date 
                  AND is_current = TRUE
                  AND status = 'active'
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->is_current = $row['is_current'];
            $this->status = $row['status'];
            $this->course_selection_start = $row['course_selection_start'];
            $this->course_selection_end = $row['course_selection_end'];
            $this->is_course_selection_active = $row['is_course_selection_active'];
            
            return true;
        }
        
        // If no current term is found, try to get the most recent term
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'active'
                  ORDER BY start_date DESC
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->is_current = $row['is_current'];
            $this->status = $row['status'];
            $this->course_selection_start = $row['course_selection_start'];
            $this->course_selection_end = $row['course_selection_end'];
            $this->is_course_selection_active = $row['is_course_selection_active'];
            
            return true;
        }
        
        return false;
    }
    
    // Check if course selection is active for this term
    public function isCourseSelectionActive() {
        if ($this->is_course_selection_active) {
            if ($this->course_selection_start && $this->course_selection_end) {
                $now = date('Y-m-d H:i:s');
                
                // Add timestamps for proper datetime comparison
                $start_time = strtotime($this->course_selection_start);
                $end_time = strtotime($this->course_selection_end);
                $current_time = strtotime($now);
                
                // Check if current time is between start and end times
                if ($current_time >= $start_time && $current_time <= $end_time) {
                    return true;
                }
            }
        }
        return false;
    }
}
?>