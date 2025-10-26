<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Notification.php';

// Check if user can manage notifications (admin only)
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$message = '';
$message_type = '';

// Handle test notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_notification'])) {
    $title = "Test Notification - " . date('Y-m-d H:i:s');
    $message_text = "This is a test notification to verify that the notification system is working properly.";
    
    $result = $notification->sendOneSignalNotification(
        $title,
        $message_text,
        'all',
        null,
        null
    );
    
    if ($result) {
        $message = 'Test notification sent successfully! Check your device for the notification.';
        $message_type = 'success';
    } else {
        $message = 'Failed to send test notification. Check your OneSignal configuration.';
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .config-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .config-item:last-child {
            border-bottom: none;
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="test-card">
                    <h2 class="mb-4"><i class="fas fa-bell me-2"></i>Notification System Test</h2>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Configuration Check -->
                    <h5 class="mb-3">Configuration Status</h5>
                    <div class="config-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>OneSignal App ID</span>
                            <span class="<?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>OneSignal REST API Key</span>
                            <span class="<?php echo defined('ONESIGNAL_REST_API_KEY') && ONESIGNAL_REST_API_KEY ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo defined('ONESIGNAL_REST_API_KEY') && ONESIGNAL_REST_API_KEY ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo defined('ONESIGNAL_REST_API_KEY') && ONESIGNAL_REST_API_KEY ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Database Connection</span>
                            <span class="<?php echo $db ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo $db ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo $db ? 'Connected' : 'Failed'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Notification Class</span>
                            <span class="<?php echo class_exists('Notification') ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo class_exists('Notification') ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo class_exists('Notification') ? 'Loaded' : 'Not Found'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Test Notification -->
                    <div class="mt-4">
                        <h5 class="mb-3">Send Test Notification</h5>
                        <p class="text-muted">Click the button below to send a test notification to all users.</p>
                        
                        <form method="POST">
                            <button type="submit" name="test_notification" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Test Notification
                            </button>
                        </form>
                    </div>
                    
                    <!-- Troubleshooting -->
                    <div class="mt-5">
                        <h5 class="mb-3">Troubleshooting</h5>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Common Issues:</h6>
                            <ul class="mb-0">
                                <li><strong>No notifications received:</strong> Check if OneSignal is properly configured in your config.php</li>
                                <li><strong>OneSignal not configured:</strong> Add ONESIGNAL_APP_ID and ONESIGNAL_REST_API_KEY to your config.php</li>
                                <li><strong>Database issues:</strong> Ensure the notifications table exists in your database</li>
                                <li><strong>User not subscribed:</strong> Users need to allow notifications in their browser</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="notifications.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OneSignal SDK v16 -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
    <!-- OneSignal Configuration v16 -->
    <script>
        // Set OneSignal configuration
        window.ONESIGNAL_APP_ID = '<?php echo defined('ONESIGNAL_APP_ID') ? ONESIGNAL_APP_ID : ''; ?>';
        window.ONESIGNAL_SAFARI_WEB_ID = '<?php echo defined('ONESIGNAL_SAFARI_WEB_ID') ? ONESIGNAL_SAFARI_WEB_ID : ''; ?>';
        
        // Initialize OneSignal v16
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
                appId: window.ONESIGNAL_APP_ID,
                safari_web_id: window.ONESIGNAL_SAFARI_WEB_ID || '',
                notifyButton: {
                    enable: false
                },
                allowLocalhostAsSecureOrigin: true,
                autoRegister: false,
                promptOptions: {
                    slidedown: {
                        enabled: false
                    }
                }
            });
            console.log('OneSignal v16 initialized for testing');
        });
    </script>
    
    <!-- OneSignal Initialization -->
    <script src="../assets/js/onesignal-config.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced test functionality -->
    <script>
        // Enhanced test notification with OneSignal integration
        function sendEnhancedTestNotification() {
            if (typeof OneSignal !== 'undefined') {
                // First, ensure user is subscribed
                OneSignal.getUserId().then(function(userId) {
                    if (userId) {
                        // User is subscribed, send test notification
                        fetch('../api/notifications.php?action=send', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                title: 'Test Notification - ' + new Date().toLocaleTimeString(),
                                message: 'This is a test notification to verify OneSignal integration.',
                                type: 'general',
                                target_audience: 'all'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Test notification sent successfully!');
                            } else {
                                alert('Failed to send notification: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error sending test notification');
                        });
                    } else {
                        alert('Please subscribe to notifications first. Click "Enable Notifications" to subscribe.');
                    }
                });
            } else {
                alert('OneSignal SDK not loaded. Please check your configuration.');
            }
        }
        
        // Update the test button to use enhanced functionality
        document.addEventListener('DOMContentLoaded', function() {
            const testForm = document.querySelector('form[method="POST"]');
            if (testForm) {
                testForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    sendEnhancedTestNotification();
                });
            }
        });
    </script>
</body>
</html>
