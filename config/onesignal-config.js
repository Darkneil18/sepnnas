// OneSignal Configuration for SEPNAS Event Management System
// Replace these values with your actual OneSignal credentials

// OneSignal App ID - Get this from your OneSignal dashboard
const ONESIGNAL_APP_ID = 'c75a0da8-94a2-46cf-b191-bd1eed981bbd';

// OneSignal REST API Key - Get this from your OneSignal dashboard
const ONESIGNAL_REST_API_KEY = 'os_v2_org_y5na3keuujdm7mmrxupo3ga3xwtokjmsqfyemiukloqjk2eyfseyfwafbv5id5j5uctxp3fq53jvhs2j6kkadzfioluex34zp4tgsii';

// Safari Web ID (optional) - For Safari push notifications
const ONESIGNAL_SAFARI_WEB_ID = 'web.onesignal.auto.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';

// Notification settings
const NOTIFICATION_SETTINGS = {
    // Auto-subscribe users to notifications
    autoSubscribe: true,
    
    // Show native prompt for notification permission
    showNativePrompt: true,
    
    // Enable notification bell icon
    showNotificationBell: true,
    
    // Notification categories
    categories: {
        event_reminder: 'Event Reminders',
        event_update: 'Event Updates',
        general: 'General Announcements',
        system: 'System Notifications'
    },
    
    // Default notification settings
    defaultSettings: {
        event_reminder: true,
        event_update: true,
        general: true,
        system: false
    }
};

// Initialize OneSignal when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof OneSignal !== 'undefined' && ONESIGNAL_APP_ID !== 'your-onesignal-app-id-here') {
        OneSignal.init({
            appId: ONESIGNAL_APP_ID,
            safari_web_id: ONESIGNAL_SAFARI_WEB_ID,
            notifyButton: {
                enable: NOTIFICATION_SETTINGS.showNotificationBell,
            },
            allowLocalhostAsSecureOrigin: true,
            autoRegister: NOTIFICATION_SETTINGS.autoSubscribe,
            promptOptions: {
                slidedown: {
                    enabled: NOTIFICATION_SETTINGS.showNativePrompt,
                    autoPrompt: true,
                    timeDelay: 20,
                    pageViews: 1
                }
            }
        });

        // Set external user ID for current user
        if (window.currentUserId) {
            OneSignal.setExternalUserId(window.currentUserId);
        }

        // Handle subscription changes
        OneSignal.on('subscriptionChange', function(isSubscribed) {
            console.log('OneSignal subscription changed:', isSubscribed);
            if (isSubscribed) {
                showSuccessMessage('Notifications enabled successfully!');
            }
        });

        // Handle notification clicks
        OneSignal.on('notificationClick', function(event) {
            console.log('Notification clicked:', event);
            handleNotificationClick(event);
        });

        // Handle notification display
        OneSignal.on('notificationDisplay', function(event) {
            console.log('Notification received:', event);
            showNotificationToast(event.title, event.body);
        });
    } else {
        console.warn('OneSignal not configured. Please update the configuration.');
    }
});

// Handle notification clicks based on type
function handleNotificationClick(event) {
    if (event.data && event.data.type) {
        switch(event.data.type) {
            case 'event_notification':
                if (event.data.event_id) {
                    window.location.href = 'event-details.php?id=' + event.data.event_id;
                }
                break;
            case 'event_reminder':
                if (event.data.event_id) {
                    window.location.href = 'event-details.php?id=' + event.data.event_id;
                }
                break;
            case 'event_update':
                if (event.data.event_id) {
                    window.location.href = 'events.php';
                }
                break;
            case 'general':
                // Handle general notifications
                break;
            case 'system':
                // Handle system notifications
                break;
        }
    }
}

// Show notification toast
function showNotificationToast(title, message) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'notification-toast';
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-bell me-2"></i>
            <strong>${title}</strong>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;

    // Add styles
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        max-width: 350px;
        animation: slideInRight 0.3s ease-out;
    `;

    // Add animation keyframes if not already added
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .notification-toast {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .notification-toast .toast-header {
                padding: 12px 16px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                align-items: center;
                font-size: 14px;
            }
            .notification-toast .toast-body {
                padding: 12px 16px;
                font-size: 13px;
                color: #6c757d;
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Send notification to specific users
function sendNotificationToUsers(userIds, title, message, eventId = null) {
    if (!ONESIGNAL_REST_API_KEY || ONESIGNAL_REST_API_KEY === 'your-onesignal-rest-api-key-here') {
        console.error('OneSignal REST API Key not configured');
        return;
    }

    fetch('api/notifications.php?action=send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            title: title,
            message: message,
            target_audience: 'specific',
            target_users: userIds,
            event_id: eventId,
            type: 'general'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Notification sent successfully');
        } else {
            showErrorMessage('Failed to send notification: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending notification:', error);
        showErrorMessage('Error sending notification');
    });
}

// Send event reminder
function sendEventReminder(eventId) {
    fetch('api/notifications.php?action=send_reminder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Event reminder sent successfully');
        } else {
            showErrorMessage('Failed to send reminder: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending reminder:', error);
        showErrorMessage('Error sending reminder');
    });
}

// Send event update notification
function sendEventUpdate(eventId) {
    fetch('api/notifications.php?action=send_update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Event update sent successfully');
        } else {
            showErrorMessage('Failed to send update: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending update:', error);
        showErrorMessage('Error sending update');
    });
}

// Request notification permission
function requestNotificationPermission() {
    if (typeof OneSignal !== 'undefined') {
        OneSignal.showNativePrompt().then(() => {
            console.log('Notification permission granted');
            showSuccessMessage('Notifications enabled successfully!');
        }).catch(() => {
            console.log('Notification permission denied');
            showErrorMessage('Notification permission denied');
        });
    }
}

// Show success message
function showSuccessMessage(message) {
    console.log('Success:', message);
    // You can implement a toast notification system here
}

// Show error message
function showErrorMessage(message) {
    console.error('Error:', message);
    // You can implement a toast notification system here
}

// Make functions globally available
window.OneSignalConfig = {
    sendToUsers: sendNotificationToUsers,
    sendEventReminder: sendEventReminder,
    sendEventUpdate: sendEventUpdate,
    requestPermission: requestNotificationPermission
};
