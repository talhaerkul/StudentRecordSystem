<?php
// Database connection and include the announcement model
require_once '../../config/database.php';
require_once '../../models/announcement.php';
require_once '../../includes/auth_check.php';

// Start the session
requireLogin();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/auth/login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: announcements.php');
    exit();
}

// Instantiate database and models
$database = new Database();
$db = $database->getConnection();
$announcement = new Announcement($db);

// Set announcement ID
$announcement->id = $_GET['id'];

// Read the announcement
if (!$announcement->readOne()) {
    // If announcement not found, redirect to announcements page
    header('Location: announcements.php');
    exit();
}

// Set page title
$page_title = $announcement->title;

// İçerik oluştur
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-lg rounded-lg overflow-hidden">
                <div class="card-header bg-gradient-to-r from-purple-700 to-indigo-600 text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 font-semibold">
                        <i class="fas fa-bullhorn mr-2"></i> <?php echo htmlspecialchars($announcement->title); ?>
                    </h5>
                    <a href="announcements.php" class="btn btn-light btn-sm hover:bg-gray-100 transition-colors duration-200 flex items-center shadow-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Announcements
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="space-y-2">
                                <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">Posted by:</span> <?php echo htmlspecialchars($announcement->user_name); ?></p>
                                <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">Date:</span> <?php echo date('F j, Y', strtotime($announcement->created_at)); ?></p>
                                <?php if ($announcement->start_date): ?>
                                    <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">Start Date:</span> <?php echo date('F j, Y', strtotime($announcement->start_date)); ?></p>
                                <?php endif; ?>
                                <?php if ($announcement->end_date): ?>
                                    <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">End Date:</span> <?php echo date('F j, Y', strtotime($announcement->end_date)); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="space-y-2">
                                <?php if ($announcement->role_name): ?>
                                    <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">For Role:</span> <?php echo htmlspecialchars($announcement->role_name); ?></p>
                                <?php endif; ?>
                                <?php if ($announcement->department_name): ?>
                                    <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">For Department:</span> <?php echo htmlspecialchars($announcement->department_name); ?></p>
                                <?php endif; ?>
                                <?php if ($announcement->course_name): ?>
                                    <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">For Course:</span> <?php echo htmlspecialchars($announcement->course_name); ?></p>
                                <?php endif; ?>
                                <p class="flex items-center text-gray-700"><span class="font-semibold mr-2">Status:</span> 
                                    <span class="badge badge-<?php echo $announcement->status == 'active' ? 'success' : 'danger'; ?> ml-1">
                                        <?php echo ucfirst($announcement->status); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="announcement-content bg-indigo-50 p-4 rounded-lg border border-indigo-200 text-gray-800 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($announcement->content)); ?>
                    </div>
                </div>
                <div class="card-footer text-gray-600 bg-gray-50 border-t border-gray-200">
                    <small class="flex items-center"><i class="far fa-clock mr-1"></i> Last updated: <?php echo date('F j, Y, g:i a', strtotime($announcement->updated_at)); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// İçeriği al
$content = ob_get_clean();

// Layout'u dahil et
require_once '../../includes/layout.php';
?> 