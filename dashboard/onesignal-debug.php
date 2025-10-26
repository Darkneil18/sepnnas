<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneSignal Debug - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .debug-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .config-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .config-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="debug-card">
                    <h2 class="mb-4 text-center">
                        <i class="fas fa-bug me-2"></i>OneSignal Debug Information
                    </h2>
                    
                    <!-- PHP Configuration -->
                    <h5 class="mb-3">PHP Configuration</h5>
                    <div class="config-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>OneSignal App ID</span>
                            <span class="<?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'status-ok' : 'status-error'; ?>">
                                <i class="fas fa-<?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                        <?php if (defined('ONESIGNAL_APP_ID') && ONESIGNAL_APP_ID): ?>
                        <small class="text-muted">Value: <?php echo ONESIGNAL_APP_ID; ?></small>
                        <?php endif; ?>
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
                            <span>OneSignal Safari Web ID</span>
                            <span class="<?php echo defined('ONESIGNAL_SAFARI_WEB_ID') && ONESIGNAL_SAFARI_WEB_ID ? 'status-ok' : 'status-warning'; ?>">
                                <i class="fas fa-<?php echo defined('ONESIGNAL_SAFARI_WEB_ID') && ONESIGNAL_SAFARI_WEB_ID ? 'check' : 'exclamation-triangle'; ?> me-1"></i>
                                <?php echo defined('ONESIGNAL_SAFARI_WEB_ID') && ONESIGNAL_SAFARI_WEB_ID ? 'Configured' : 'Optional'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- JavaScript Configuration -->
                    <h5 class="mb-3 mt-4">JavaScript Configuration</h5>
                    <div id="js-config">
                        <div class="config-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>OneSignal SDK Loaded</span>
                                <span id="sdk-status" class="status-warning">
                                    <i class="fas fa-spinner fa-spin me-1"></i>Checking...
                                </span>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>App ID in JavaScript</span>
                                <span id="app-id-status" class="status-warning">
                                    <i class="fas fa-spinner fa-spin me-1"></i>Checking...
                                </span>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>User Subscription</span>
                                <span id="subscription-status" class="status-warning">
                                    <i class="fas fa-spinner fa-spin me-1"></i>Checking...
                                </span>
                            </div>
                        </div>
                        
                        <div class="config-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Notification Permission</span>
                                <span id="permission-status" class="status-warning">
                                    <i class="fas fa-spinner fa-spin me-1"></i>Checking...
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Actions -->
                    <div class="mt-4">
                        <h5>Test Actions</h5>
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-primary" onclick="testOneSignalConnection()">
                                <i class="fas fa-plug me-2"></i>Test Connection
                            </button>
                            <button class="btn btn-success" onclick="requestNotificationPermission()">
                                <i class="fas fa-bell me-2"></i>Request Permission
                            </button>
                            <button class="btn btn-info" onclick="checkSubscriptionStatus()">
                                <i class="fas fa-user-check me-2"></i>Check Subscription
                            </button>
                            <button class="btn btn-warning" onclick="refreshStatus()">
                                <i class="fas fa-sync me-2"></i>Refresh Status
                            </button>
                        </div>
                    </div>
                    
                    <!-- Console Output -->
                    <div class="mt-4">
                        <h5>Console Output</h5>
                        <div id="console-output" class="bg-dark text-light p-3 rounded" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.9rem;">
                            <div>Console output will appear here...</div>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="text-center mt-4">
                        <a href="test-notifications.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-bell me-1"></i>Test Notifications
                        </a>
                        <a href="enable-notifications.php" class="btn btn-outline-success me-2">
                            <i class="fas fa-cog me-1"></i>Enable Notifications
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i>Dashboard
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
        
        // Initialize OneSignal v16 (only if not already initialized)
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        if (!window.oneSignalConfigLoaded) {
            OneSignalDeferred.push(async function(OneSignal) {
                try {
                    await OneSignal.init({
                        appId: window.ONESIGNAL_APP_ID,
                        safari_web_id: window.ONESIGNAL_SAFARI_WEB_ID || '',
                        notifyButton: {
                            enable: false
                        },
                        allowLocalhostAsSecureOrigin: true,
                        autoRegister: false,
                        serviceWorkerParam: {
                            scope: '/'
                        },
                        serviceWorkerPath: '/sw.js',
                        promptOptions: {
                            slidedown: {
                                enabled: false
                            }
                        }
                    });
                    console.log('OneSignal v16 initialized in debug page');
                } catch (error) {
                    console.error('OneSignal initialization failed in debug page:', error);
                    
                    // Try fallback initialization without service worker
                    if (error.message.includes('ServiceWorker') || error.message.includes('redirect')) {
                        console.log('Trying fallback initialization without service worker...');
                        try {
                            await OneSignal.init({
                                appId: window.ONESIGNAL_APP_ID,
                                safari_web_id: window.ONESIGNAL_SAFARI_WEB_ID || '',
                                notifyButton: {
                                    enable: false
                                },
                                allowLocalhostAsSecureOrigin: true,
                                autoRegister: false,
                                serviceWorkerParam: null,
                                serviceWorkerPath: null,
                                promptOptions: {
                                    slidedown: {
                                        enabled: false
                                    }
                                }
                            });
                            console.log('OneSignal v16 initialized in debug page (fallback mode)');
                        } catch (fallbackError) {
                            console.error('Fallback initialization also failed:', fallbackError);
                        }
                    }
                }
            });
        }
    </script>
    
    <!-- OneSignal Initialization -->
    <script src="../assets/js/onesignal-config.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Debug Scripts -->
    <script>
        const consoleOutput = document.getElementById('console-output');
        
        function logToConsole(message) {
            const timestamp = new Date().toLocaleTimeString();
            consoleOutput.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        // Override console.log to capture output
        const originalLog = console.log;
        console.log = function(...args) {
            originalLog.apply(console, args);
            logToConsole(args.join(' '));
        };
        
        // Check OneSignal status (v16 approach)
        function checkOneSignalStatus() {
            // Check SDK
            const sdkStatus = document.getElementById('sdk-status');
            if (typeof window.OneSignalDeferred !== 'undefined') {
                sdkStatus.innerHTML = '<i class="fas fa-check me-1"></i>v16 Loaded';
                sdkStatus.className = 'status-ok';
                logToConsole('OneSignal v16 SDK loaded successfully');
                
                // Use OneSignalDeferred to access OneSignal
                window.OneSignalDeferred.push(async function(OneSignal) {
                    try {
                        // Check subscription
                        const userId = await OneSignal.User.PushSubscription.id;
                        const optedIn = OneSignal.User.PushSubscription.optedIn;
                        const subscriptionStatus = document.getElementById('subscription-status');
                        
                        if (userId && optedIn) {
                            subscriptionStatus.innerHTML = '<i class="fas fa-check me-1"></i>Subscribed';
                            subscriptionStatus.className = 'status-ok';
                            logToConsole('User subscribed with ID: ' + userId);
                        } else {
                            subscriptionStatus.innerHTML = '<i class="fas fa-times me-1"></i>Not Subscribed';
                            subscriptionStatus.className = 'status-error';
                            logToConsole('User not subscribed');
                        }
                        
                        // Check permission
                        const permission = await OneSignal.Notifications.permission;
                        const permissionStatus = document.getElementById('permission-status');
                        
                        if (permission === 'granted') {
                            permissionStatus.innerHTML = '<i class="fas fa-check me-1"></i>Granted';
                            permissionStatus.className = 'status-ok';
                        } else if (permission === 'denied') {
                            permissionStatus.innerHTML = '<i class="fas fa-times me-1"></i>Denied';
                            permissionStatus.className = 'status-error';
                        } else {
                            permissionStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Default';
                            permissionStatus.className = 'status-warning';
                        }
                        logToConsole('Notification permission: ' + permission);
                        
                    } catch (error) {
                        const subscriptionStatus = document.getElementById('subscription-status');
                        subscriptionStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error';
                        subscriptionStatus.className = 'status-warning';
                        
                        const permissionStatus = document.getElementById('permission-status');
                        permissionStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Error';
                        permissionStatus.className = 'status-warning';
                        
                        logToConsole('Error checking OneSignal status: ' + error);
                    }
                });
                
            } else {
                sdkStatus.innerHTML = '<i class="fas fa-times me-1"></i>v16 Not Loaded';
                sdkStatus.className = 'status-error';
                logToConsole('OneSignal v16 SDK not loaded');
                
                // Set error status for subscription and permission
                const subscriptionStatus = document.getElementById('subscription-status');
                subscriptionStatus.innerHTML = '<i class="fas fa-times me-1"></i>SDK Not Loaded';
                subscriptionStatus.className = 'status-error';
                
                const permissionStatus = document.getElementById('permission-status');
                permissionStatus.innerHTML = '<i class="fas fa-times me-1"></i>SDK Not Loaded';
                permissionStatus.className = 'status-error';
            }
            
            // Check App ID
            const appIdStatus = document.getElementById('app-id-status');
            if (window.ONESIGNAL_APP_ID) {
                appIdStatus.innerHTML = '<i class="fas fa-check me-1"></i>Configured';
                appIdStatus.className = 'status-ok';
                logToConsole('App ID configured: ' + window.ONESIGNAL_APP_ID);
            } else {
                appIdStatus.innerHTML = '<i class="fas fa-times me-1"></i>Not Configured';
                appIdStatus.className = 'status-error';
                logToConsole('App ID not configured');
            }
        }
        
        // Test functions
        function testOneSignalConnection() {
            logToConsole('Testing OneSignal connection...');
            if (typeof window.OneSignalDeferred !== 'undefined') {
                window.OneSignalDeferred.push(async function(OneSignal) {
                    try {
                        logToConsole('OneSignal SDK is available');
                        const userId = await OneSignal.User.PushSubscription.id;
                        if (userId) {
                            logToConsole('Connection successful. User ID: ' + userId);
                        } else {
                            logToConsole('Connection successful but user not subscribed');
                        }
                    } catch (error) {
                        logToConsole('Error testing connection: ' + error);
                    }
                });
            } else {
                logToConsole('OneSignal SDK not available');
            }
        }
        
        function requestNotificationPermission() {
            logToConsole('Requesting notification permission...');
            if (typeof window.OneSignalDeferred !== 'undefined') {
                window.OneSignalDeferred.push(async function(OneSignal) {
                    try {
                        await OneSignal.Slidedown.promptPush();
                        logToConsole('Permission prompt shown');
                    } catch (error) {
                        logToConsole('Error showing permission prompt: ' + error);
                    }
                });
            } else {
                logToConsole('OneSignal SDK not available');
            }
        }
        
        function checkSubscriptionStatus() {
            logToConsole('Checking subscription status...');
            if (typeof window.OneSignalDeferred !== 'undefined') {
                window.OneSignalDeferred.push(async function(OneSignal) {
                    try {
                        const userId = await OneSignal.User.PushSubscription.id;
                        const optedIn = OneSignal.User.PushSubscription.optedIn;
                        if (userId && optedIn) {
                            logToConsole('User is subscribed with ID: ' + userId);
                        } else {
                            logToConsole('User is not subscribed');
                        }
                    } catch (error) {
                        logToConsole('Error checking subscription: ' + error);
                    }
                });
            } else {
                logToConsole('OneSignal SDK not available');
            }
        }
        
        function refreshStatus() {
            logToConsole('Refreshing status...');
            // Reset status indicators
            document.getElementById('subscription-status').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';
            document.getElementById('permission-status').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';
            
            // Re-check status
            setTimeout(checkOneSignalStatus, 500);
        }
        
        // Check status when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initial check after 1 second
            setTimeout(checkOneSignalStatus, 1000);
            
            // Fallback check after 3 seconds if still checking
            setTimeout(function() {
                const subscriptionStatus = document.getElementById('subscription-status');
                const permissionStatus = document.getElementById('permission-status');
                
                if (subscriptionStatus.innerHTML.includes('Checking')) {
                    subscriptionStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Timeout';
                    subscriptionStatus.className = 'status-warning';
                    logToConsole('Subscription check timed out');
                }
                
                if (permissionStatus.innerHTML.includes('Checking')) {
                    permissionStatus.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Timeout';
                    permissionStatus.className = 'status-warning';
                    logToConsole('Permission check timed out');
                }
            }, 3000);
        });
    </script>
</body>
</html>
