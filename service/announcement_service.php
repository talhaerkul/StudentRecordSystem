<?php
/**
 * Announcement Service
 * Handles the business logic for announcements
 */
class AnnouncementService {
    private $db;
    private $announcement;
    private $role;
    private $department;
    private $course;

    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->announcement = new Announcement($db);
        $this->role = new Role($db);
        $this->department = new Department($db);
        $this->course = new Course($db);
    }

    /**
     * Get roles for dropdowns
     * 
     * @return PDOStatement
     */
    public function getRoles() {
        return $this->role->readAll();
    }

    /**
     * Get departments for dropdowns
     * 
     * @return PDOStatement
     */
    public function getDepartments() {
        return $this->department->readAll();
    }

    /**
     * Get courses for dropdowns
     * 
     * @return PDOStatement
     */
    public function getCourses() {
        return $this->course->readAll();
    }

    /**
     * Get all active announcements - visible to everyone
     * Filtered by date range - only applicable ones
     * 
     * @return PDOStatement
     */
    public function getAllAnnouncements() {
        return $this->announcement->readAll();
    }
    
    /**
     * Get all announcements for admin - both active and inactive
     * No date filtering
     * 
     * @return PDOStatement
     */
    public function getAllAnnouncementsForAdmin() {
        return $this->announcement->readAllForAdmin();
    }

    /**
     * Get active announcements for a specific role - used only for editing permissions
     * 
     * @param int $roleId User's role ID
     * @param int|null $departmentId User's department ID
     * @return PDOStatement
     */
    public function getActiveAnnouncements($roleId, $departmentId) {
        error_log("Getting active announcements for role_id: " . $roleId . ", department_id: " . ($departmentId ? $departmentId : "NULL"));
        return $this->announcement->readByUserRole($roleId, $departmentId, 'active');
    }

    /**
     * Get inactive announcements (admin only)
     * 
     * @param int $roleId User's role ID
     * @param int|null $departmentId User's department ID
     * @return PDOStatement
     */
    public function getInactiveAnnouncements($roleId, $departmentId) {
        error_log("Getting inactive announcements for role_id: " . $roleId . ", department_id: " . ($departmentId ? $departmentId : "NULL"));
        return $this->announcement->readByUserRole($roleId, $departmentId, 'inactive');
    }

    /**
     * Create a new announcement
     * 
     * @param array $data Announcement data
     * @return bool Success status
     */
    public function createAnnouncement($data) {
        error_log("Creating new announcement");
        
        $this->announcement->title = $data['title'];
        $this->announcement->content = $data['content'];
        $this->announcement->user_id = $data['user_id'];
        $this->announcement->role_id = !empty($data['role_id']) ? $data['role_id'] : null;
        $this->announcement->department_id = !empty($data['department_id']) ? $data['department_id'] : null;
        $this->announcement->course_id = !empty($data['course_id']) ? $data['course_id'] : null;
        $this->announcement->start_date = $data['start_date'];
        $this->announcement->end_date = $data['end_date'];
        $this->announcement->status = 'active';
        
        error_log("Announcement data before creation:");
        error_log("Title: " . $this->announcement->title);
        error_log("Content: " . $this->announcement->content);
        error_log("User ID: " . $this->announcement->user_id);
        error_log("Role ID: " . ($this->announcement->role_id ? $this->announcement->role_id : "NULL"));
        error_log("Department ID: " . ($this->announcement->department_id ? $this->announcement->department_id : "NULL"));
        error_log("Course ID: " . ($this->announcement->course_id ? $this->announcement->course_id : "NULL"));
        error_log("Start Date: " . $this->announcement->start_date);
        error_log("End Date: " . $this->announcement->end_date);
        error_log("Status: " . $this->announcement->status);
        
        return $this->announcement->create();
    }

    /**
     * Update an existing announcement
     * 
     * @param array $data Announcement data
     * @return bool Success status
     */
    public function updateAnnouncement($data) {
        $this->announcement->id = $data['id'];
        $this->announcement->title = $data['title'];
        $this->announcement->content = $data['content'];
        $this->announcement->role_id = !empty($data['role_id']) ? $data['role_id'] : null;
        $this->announcement->department_id = !empty($data['department_id']) ? $data['department_id'] : null;
        $this->announcement->course_id = !empty($data['course_id']) ? $data['course_id'] : null;
        $this->announcement->start_date = $data['start_date'];
        $this->announcement->end_date = $data['end_date'];
        $this->announcement->status = $data['status'];
        
        return $this->announcement->update();
    }

    /**
     * Delete an announcement
     * 
     * @param int $id Announcement ID
     * @return bool Success status
     */
    public function deleteAnnouncement($id) {
        $this->announcement->id = $id;
        return $this->announcement->delete();
    }

    /**
     * Get a single announcement by ID
     * 
     * @param int $id Announcement ID
     * @return object|bool Announcement object or false
     */
    public function getAnnouncementById($id) {
        $this->announcement->id = $id;
        if ($this->announcement->readOne()) {
            return $this->announcement;
        }
        return false;
    }

    /**
     * Get announcements created by a user
     * 
     * @param int $userId User ID of creator
     * @param string $status Announcement status (active/inactive)
     * @return PDOStatement
     */
    public function getAnnouncementsByCreator($userId, $status = 'active') {
        return $this->announcement->readByCreatorId($userId, $status);
    }
}
?>