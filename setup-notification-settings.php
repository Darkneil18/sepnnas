<?php
require_once 'config/config.php';
require_once 'config/database.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_column'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Add the notification_settings column
        $query = "ALTER TABLE users ADD COLUMN notification_settings TEXT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        // Add index for better performance
        $query = "CREATE INDEX idx_users_notification_settings ON users(notification_settings(255))";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        // Update existing users with default settings
        $default_settings = json_encode([
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
        ]);
        
        $query = "UPDATE users SET notification_settings = :settings WHERE notification_settings IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":settings", $default_settings);
        $stmt->execute();
        
        $message = 'Notification settings column added successfully!';
        $message_type = 'success';
        
    } catch (PDOException $e) {
        $message = 'Error adding column: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Check if column exists
$column_exists = false;
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SHOW COLUMNS FROM users LIKE 'notification_settings'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $column_exists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // Database error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Notification Settings - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="setup-card">
                    <h2 class="mb-4 text-center">
                        <i class="fas fa-bell me-2"></i>Setup Notification Settings
                    </h2>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Database Status -->
                    <div class="mb-4">
                        <h5>Database Status</h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Notification Settings Column</span>
                            <span class="<?php echo $column_exists ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo $column_exists ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo $column_exists ? 'Exists' : 'Missing'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!$column_exists): ?>
                    <!-- Setup Instructions -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Setup Required</h6>
                        <p class="mb-0">The notification settings column is missing from your database. Click the button below to add it automatically.</p>
                    </div>
                    
                    <form method="POST" class="text-center">
                        <button type="submit" name="add_column" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Add Notification Settings Column
                        </button>
                    </form>
                    <?php else: ?>
                    <!-- Success Message -->
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle me-2"></i>Setup Complete</h6>
                        <p class="mb-0">The notification settings column has been added successfully. You can now use the notification settings feature.</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="dashboard/notification-settings.php" class="btn btn-success btn-lg">
                            <i class="fas fa-cog me-2"></i>Go to Notification Settings
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Manual Setup Instructions -->
                    <div class="mt-4">
                        <h6>Manual Setup (if automatic setup fails)</h6>
                        <p class="text-muted">Run this SQL command in your database:</p>
                        <div class="bg-light p-3 rounded">
                            <code>
                                ALTER TABLE users ADD COLUMN notification_settings TEXT NULL;
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
