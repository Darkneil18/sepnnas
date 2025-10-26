<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Notification.php';
require_once '../classes/User.php';

// Check if user can manage notifications (admin only)
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);
$user = new User($db);

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_notification':
                $notification->title = $_POST['title'];
                $notification->message = $_POST['message'];
                $notification->type = $_POST['type'];
                $notification->target_audience = $_POST['target_audience'];
                $notification->target_users = $_POST['target_users'] ? json_encode(explode(',', $_POST['target_users'])) : null;
                $notification->event_id = $_POST['event_id'] ?: null;
                $notification->scheduled_at = $_POST['scheduled_at'] ?: null;
                $notification->created_by = $_SESSION['user_id'];
                
                $notification_id = $notification->create();
                
                if ($notification_id) {
                    // Send immediately if not scheduled
                    if (!$notification->scheduled_at) {
                        $result = $notification->sendOneSignalNotification(
                            $notification->title,
                            $notification->message,
                            $notification->target_audience,
                            $notification->target_users,
                            $notification->event_id
                        );
                        
                        if ($result) {
                            $notification->markAsSent($notification_id);
                        }
                    }
                    
                    $message = 'Notification sent successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error sending notification.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get notifications
$notifications = $notification->getAllNotifications(50, 0);
$notification_stats = $notification->getNotificationStats();

// Get users for targeting
$users = $user->getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        .stats-card.total { border-left-color: #007bff; }
        .stats-card.sent { border-left-color: #28a745; }
        .stats-card.pending { border-left-color: #ffc107; }
        .stats-card.reminders { border-left-color: #dc3545; }
        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .notification-type {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>SEPNAS
                        </h4>
                        <small class="text-white-50">Event Management</small>
                    </div>
                    <nav class="nav flex-column p-3">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </a>
                        <a class="nav-link" href="calendar.php">
                            <i class="fas fa-calendar me-2"></i>Calendar
                        </a>
                        <?php if(canManageEvents()): ?>
                        <a class="nav-link" href="manage-events.php">
                            <i class="fas fa-plus-circle me-2"></i>Manage Events
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="venues.php">
                            <i class="fas fa-map-marker-alt me-2"></i>Venues
                        </a>
                        <?php endif; ?>
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-user-check me-2"></i>Attendance
                        </a>
                        <a class="nav-link" href="feedback.php">
                            <i class="fas fa-comments me-2"></i>Feedback
                        </a>
                        <?php if(isAdmin()): ?>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>User Management
                        </a>
                        <a class="nav-link active" href="notifications.php">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </a>
                        <?php endif; ?>
                        <?php if(canViewReports()): ?>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <?php endif; ?>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                        <a class="nav-link" href="notification-settings.php">
                            <i class="fas fa-cog me-2"></i>Notification Settings
                        </a>
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-bell me-2"></i>Notification Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#notificationModal">
                            <i class="fas fa-plus me-2"></i>Send Notification
                        </button>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card total">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $notification_stats['total_notifications']; ?></h3>
                                        <p class="text-muted mb-0">Total Notifications</p>
                                    </div>
                                    <i class="fas fa-bell fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card sent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $notification_stats['sent_notifications']; ?></h3>
                                        <p class="text-muted mb-0">Sent</p>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card pending">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $notification_stats['pending_notifications']; ?></h3>
                                        <p class="text-muted mb-0">Pending</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card reminders">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $notification_stats['reminder_notifications']; ?></h3>
                                        <p class="text-muted mb-0">Reminders</p>
                                    </div>
                                    <i class="fas fa-bell fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div class="row">
                        <?php if (empty($notifications)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No notifications found</h4>
                            <p class="text-muted">Send your first notification to get started.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="notification-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                    <span class="notification-type badge bg-<?php echo $notif['is_sent'] ? 'success' : 'warning'; ?>">
                                        <?php echo $notif['is_sent'] ? 'Sent' : 'Pending'; ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($notif['message']); ?></p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Type</small>
                                        <strong><?php echo ucfirst(str_replace('_', ' ', $notif['type'])); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Target</small>
                                        <strong><?php echo ucfirst($notif['target_audience']); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo date('M d, Y g:i A', strtotime($notif['created_at'])); ?>
                                    </small>
                                    <?php if ($notif['is_sent']): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check me-1"></i>Sent
                                    </small>
                                    <?php else: ?>
                                    <small class="text-warning">
                                        <i class="fas fa-clock me-1"></i>Scheduled
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_notification">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="general">General</option>
                                    <option value="event_reminder">Event Reminder</option>
                                    <option value="event_update">Event Update</option>
                                    <option value="system_announcement">System Announcement</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <select class="form-select" id="target_audience" name="target_audience" required>
                                    <option value="all">All Users</option>
                                    <option value="students">Students Only</option>
                                    <option value="teachers">Teachers Only</option>
                                    <option value="specific">Specific Users</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="specificUsersField" style="display: none;">
                            <label for="target_users" class="form-label">User IDs (comma-separated)</label>
                            <input type="text" class="form-control" id="target_users" name="target_users" placeholder="1,2,3,4">
                        </div>
                        
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label">Schedule (optional)</label>
                            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at">
                            <div class="form-text">Leave empty to send immediately</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Notification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide specific users field based on target audience
        document.getElementById('target_audience').addEventListener('change', function() {
            const specificUsersField = document.getElementById('specificUsersField');
            if (this.value === 'specific') {
                specificUsersField.style.display = 'block';
            } else {
                specificUsersField.style.display = 'none';
            }
        });
    </script>
</body>
</html>
