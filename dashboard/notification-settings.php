<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$message_type = '';

// Get current user data
$user->getUserById($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_notification_settings') {
        // Get notification settings from form
        $notification_settings = [
            'event_reminders' => isset($_POST['event_reminders']) ? 1 : 0,
            'event_updates' => isset($_POST['event_updates']) ? 1 : 0,
            'event_cancellations' => isset($_POST['event_cancellations']) ? 1 : 0,
            'new_events' => isset($_POST['new_events']) ? 1 : 0,
            'feedback_requests' => isset($_POST['feedback_requests']) ? 1 : 0,
            'system_announcements' => isset($_POST['system_announcements']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'push_notifications' => isset($_POST['push_notifications']) ? 1 : 0,
            'reminder_timing' => $_POST['reminder_timing'] ?? '24',
            'notification_frequency' => $_POST['notification_frequency'] ?? 'immediate'
        ];
        
        // Update user notification settings
        try {
            $query = "UPDATE users SET notification_settings = :settings WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $settings_json = json_encode($notification_settings);
        $stmt->bindParam(":settings", $settings_json);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = 'Notification settings updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating notification settings.';
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            // Column doesn't exist, show message to add it
            $message = 'Notification settings column not found. Please add the notification_settings column to the users table.';
            $message_type = 'warning';
        }
    }
}

// Get current notification settings
$current_settings = [
    'event_reminders' => 1,
    'event_updates' => 1,
    'event_cancellations' => 1,
    'new_events' => 1,
    'feedback_requests' => 1,
    'system_announcements' => 1,
    'email_notifications' => 1,
    'push_notifications' => 1,
    'reminder_timing' => '24',
    'notification_frequency' => 'immediate'
];

// Try to get existing settings from database
try {
    $query = "SELECT notification_settings FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($user_data['notification_settings']) && $user_data['notification_settings']) {
            $current_settings = array_merge($current_settings, json_decode($user_data['notification_settings'], true));
        }
    }
} catch (PDOException $e) {
    // Column doesn't exist, use default settings
    $current_settings = [
        'event_reminders' => 1,
        'event_updates' => 1,
        'event_cancellations' => 1,
        'new_events' => 1,
        'feedback_requests' => 1,
        'system_announcements' => 1,
        'email_notifications' => 1,
        'push_notifications' => 1,
        'reminder_timing' => '24',
        'notification_frequency' => 'immediate'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings - SEPNAS Event Management</title>
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
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .setting-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-description {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .form-switch .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
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
                        <a class="nav-link" href="notifications.php">
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
                        <a class="nav-link active" href="notification-settings.php">
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
                        <h2><i class="fas fa-bell me-2"></i>Notification Settings</h2>
                        <div>
                            <a href="enable-notifications.php" class="btn btn-primary me-2">
                                <i class="fas fa-bell me-2"></i>Enable Notifications
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Profile
                            </a>
                        </div>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_notification_settings">
                        
                        <!-- Event Notifications -->
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Event Notifications</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Event Reminders</h6>
                                        <p class="setting-description mb-0">Get reminded about upcoming events</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="event_reminders" name="event_reminders" 
                                               <?php echo $current_settings['event_reminders'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Event Updates</h6>
                                        <p class="setting-description mb-0">Notifications when events are modified</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="event_updates" name="event_updates" 
                                               <?php echo $current_settings['event_updates'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Event Cancellations</h6>
                                        <p class="setting-description mb-0">Alerts when events are cancelled</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="event_cancellations" name="event_cancellations" 
                                               <?php echo $current_settings['event_cancellations'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">New Events</h6>
                                        <p class="setting-description mb-0">Notifications about newly created events</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="new_events" name="new_events" 
                                               <?php echo $current_settings['new_events'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Notifications -->
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>System Notifications</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Feedback Requests</h6>
                                        <p class="setting-description mb-0">Reminders to submit event feedback</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feedback_requests" name="feedback_requests" 
                                               <?php echo $current_settings['feedback_requests'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">System Announcements</h6>
                                        <p class="setting-description mb-0">Important system updates and announcements</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="system_announcements" name="system_announcements" 
                                               <?php echo $current_settings['system_announcements'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Methods -->
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Delivery Methods</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Email Notifications</h6>
                                        <p class="setting-description mb-0">Receive notifications via email</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                               <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Push Notifications</h6>
                                        <p class="setting-description mb-0">Receive push notifications on your device</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications" 
                                               <?php echo $current_settings['push_notifications'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timing Settings -->
                        <div class="settings-card">
                            <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Timing Settings</h5>
                            
                            <div class="setting-item">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="reminder_timing" class="form-label">Event Reminder Timing</label>
                                        <select class="form-select" id="reminder_timing" name="reminder_timing">
                                            <option value="1" <?php echo $current_settings['reminder_timing'] === '1' ? 'selected' : ''; ?>>1 hour before</option>
                                            <option value="6" <?php echo $current_settings['reminder_timing'] === '6' ? 'selected' : ''; ?>>6 hours before</option>
                                            <option value="12" <?php echo $current_settings['reminder_timing'] === '12' ? 'selected' : ''; ?>>12 hours before</option>
                                            <option value="24" <?php echo $current_settings['reminder_timing'] === '24' ? 'selected' : ''; ?>>1 day before</option>
                                            <option value="48" <?php echo $current_settings['reminder_timing'] === '48' ? 'selected' : ''; ?>>2 days before</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notification_frequency" class="form-label">Notification Frequency</label>
                                        <select class="form-select" id="notification_frequency" name="notification_frequency">
                                            <option value="immediate" <?php echo $current_settings['notification_frequency'] === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                                            <option value="hourly" <?php echo $current_settings['notification_frequency'] === 'hourly' ? 'selected' : ''; ?>>Hourly Digest</option>
                                            <option value="daily" <?php echo $current_settings['notification_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily Digest</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save Notification Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
