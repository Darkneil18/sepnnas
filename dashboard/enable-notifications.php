<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';

$message = '';
$message_type = '';

// Handle notification permission request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_notifications'])) {
    // This will be handled by JavaScript, but we can log the attempt
    $message = 'Please allow notifications in the browser prompt that appears.';
    $message_type = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enable Notifications - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
        }
        .notification-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .benefits-list {
            text-align: left;
            margin: 2rem 0;
        }
        .benefit-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .benefit-item:last-child {
            border-bottom: none;
        }
        .status-indicator {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="notification-card">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    
                    <h2 class="mb-3">Enable Notifications</h2>
                    <p class="text-muted mb-4">Stay updated with the latest events and important announcements from SEPNAS.</p>
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Notification Status -->
                    <div class="mb-4">
                        <div id="notification-status" class="status-indicator status-disabled">
                            <i class="fas fa-times-circle me-1"></i>Notifications Disabled
                        </div>
                    </div>
                    
                    <!-- Benefits -->
                    <div class="benefits-list">
                        <h5 class="mb-3">Why enable notifications?</h5>
                        <div class="benefit-item">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            <strong>Event Reminders:</strong> Get reminded about upcoming events
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-bell text-warning me-2"></i>
                            <strong>Event Updates:</strong> Stay informed about event changes
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-comments text-success me-2"></i>
                            <strong>Feedback Requests:</strong> Reminders to submit event feedback
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-bullhorn text-info me-2"></i>
                            <strong>Announcements:</strong> Important system updates and news
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button id="enable-btn" class="btn btn-primary btn-lg" onclick="subscribeToNotifications()">
                            <i class="fas fa-bell me-2"></i>Enable Notifications
                        </button>
                        <button id="disable-btn" class="btn btn-outline-danger btn-lg" onclick="unsubscribeFromNotifications()" style="display: none;">
                            <i class="fas fa-bell-slash me-2"></i>Disable Notifications
                        </button>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="mt-4">
                        <h6>How to enable notifications:</h6>
                        <ol class="text-start">
                            <li>Click the "Enable Notifications" button above</li>
                            <li>Allow notifications when your browser asks for permission</li>
                            <li>You'll receive a confirmation message</li>
                            <li>You can customize your notification preferences in Settings</li>
                        </ol>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="mt-4">
                        <a href="notification-settings.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-cog me-1"></i>Notification Settings
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i>Back to Dashboard
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
                    enable: true,
                    size: 'large',
                    position: 'bottom-right',
                    showCredit: false
                },
                allowLocalhostAsSecureOrigin: true,
                autoRegister: false,
                promptOptions: {
                    slidedown: {
                        enabled: true,
                        autoPrompt: true,
                        timeDelay: 10,
                        pageViews: 1,
                        actionMessage: "We'd like to show you notifications for the latest updates.",
                        acceptButtonText: "Allow",
                        cancelButtonText: "No Thanks"
                    }
                }
            });
            console.log('OneSignal v16 initialized for notifications');
        });
    </script>
    
    <!-- OneSignal Initialization -->
    <script src="../assets/js/onesignal-config.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom notification handling -->
    <script>
        // Update UI based on notification status
        function updateNotificationUI(isEnabled) {
            const statusElement = document.getElementById('notification-status');
            const enableBtn = document.getElementById('enable-btn');
            const disableBtn = document.getElementById('disable-btn');
            
            if (isEnabled) {
                statusElement.innerHTML = '<i class="fas fa-check-circle me-1"></i>Notifications Enabled';
                statusElement.className = 'status-indicator status-enabled';
                enableBtn.style.display = 'none';
                disableBtn.style.display = 'inline-block';
            } else {
                statusElement.innerHTML = '<i class="fas fa-times-circle me-1"></i>Notifications Disabled';
                statusElement.className = 'status-indicator status-disabled';
                enableBtn.style.display = 'inline-block';
                disableBtn.style.display = 'none';
            }
        }
        
        // Override the global function to update UI
        const originalUpdateStatus = window.updateNotificationStatus;
        window.updateNotificationStatus = function(isSubscribed) {
            if (originalUpdateStatus) {
                originalUpdateStatus(isSubscribed);
            }
            updateNotificationUI(isSubscribed);
        };
        
        // Check initial status
        setTimeout(function() {
            if (typeof OneSignal !== 'undefined') {
                OneSignal.getUserId().then(function(userId) {
                    updateNotificationUI(!!userId);
                });
            }
        }, 2000);
    </script>
</body>
</html>
