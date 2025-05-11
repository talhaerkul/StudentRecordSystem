<?php
// Include necessary files
require_once '../config/config.php';
require_once '../config/database.php';

// Set output headers
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Adding updated_at Column to student_courses Table</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // First check if the column already exists
    $checkQuery = "SHOW COLUMNS FROM student_courses LIKE 'updated_at'";
    $checkStmt = $db->query($checkQuery);
    
    if ($checkStmt->rowCount() > 0) {
        echo "<p>The 'updated_at' column already exists in the student_courses table.</p>";
    } else {
        // Add the updated_at column
        $alterQuery = "ALTER TABLE student_courses 
                      ADD COLUMN updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";
        
        if ($db->exec($alterQuery) !== false) {
            echo "<p>Successfully added 'updated_at' column to the student_courses table.</p>";
            
            // Update existing records to set updated_at to the same as created_at
            $updateQuery = "UPDATE student_courses SET updated_at = created_at WHERE updated_at IS NULL";
            $updatedRows = $db->exec($updateQuery);
            
            echo "<p>Updated {$updatedRows} existing records to set updated_at equal to created_at.</p>";
        } else {
            echo "<p>Failed to add 'updated_at' column. Error: " . implode(", ", $db->errorInfo()) . "</p>";
        }
    }
    
    // Show the current structure of the table
    echo "<h2>Current Structure of student_courses Table:</h2>";
    $structureQuery = "DESCRIBE student_courses";
    $structureStmt = $db->query($structureQuery);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $structureStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>You can now <a href='../pages/grade/grades.php'>return to the grades page</a> and try saving grades again.</p>";
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}
?>