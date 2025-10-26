// OneSignal initialization and notification handling
(function() {
    'use strict';

    // Initialize OneSignal
    function initOneSignal() {
        if (typeof OneSignal !== 'undefined') {
            OneSignal.init({
                appId: ONESIGNAL_APP_ID,
                safari_web_id: 'web.onesignal.auto.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Replace with your Safari Web ID
                notifyButton: {
                    enable: true,
                },
                allowLocalhostAsSecureOrigin: true,
            });

            // Set external user ID for current user
            if (window.currentUserId) {
                OneSignal.setExternalUserId(window.currentUserId);
            }

            // Handle notification permission
            OneSignal.on('subscriptionChange', function(isSubscribed) {
                console.log('OneSignal subscription changed:', isSubscribed);
            });

            // Handle notification click
            OneSignal.on('notificationClick', function(event) {
                console.log('Notification clicked:', event);
                
                // Handle different notification types
                if (event.data && event.data.type) {
                    switch(event.data.type) {
                        case 'event_notification':
                            if (event.data.event_id) {
                                window.location.href = 'event-details.php?id=' + event.data.event_id;
                            }
                            break;
                        case 'general':
                            // Handle general notifications
                            break;
                    }
                }
            });

            // Handle notification received
            OneSignal.on('notificationDisplay', function(event) {
                console.log('Notification received:', event);
                
                // Show custom notification toast
                showNotificationToast(event.title, event.body);
            });
        }
    }

    // Show custom notification toast
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

        // Add animation keyframes
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

    // Request notification permission
    function requestNotificationPermission() {
        if (typeof OneSignal !== 'undefined') {
            OneSignal.showNativePrompt().then(() => {
                console.log('Notification permission granted');
            }).catch(() => {
                console.log('Notification permission denied');
            });
        }
    }

    // Send notification to specific users
    function sendNotificationToUsers(userIds, title, message, eventId = null) {
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

    // Show success message
    function showSuccessMessage(message) {
        // You can implement a toast notification system here
        console.log('Success:', message);
    }

    // Show error message
    function showErrorMessage(message) {
        // You can implement a toast notification system here
        console.error('Error:', message);
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initOneSignal();
    });

    // Make functions globally available
    window.OneSignalUtils = {
        requestPermission: requestNotificationPermission,
        sendToUsers: sendNotificationToUsers,
        sendEventReminder: sendEventReminder,
        sendEventUpdate: sendEventUpdate
    };

})();
