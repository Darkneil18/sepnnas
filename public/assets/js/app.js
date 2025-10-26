// =============================================
// Main Application Logic
// =============================================

let currentUser = null;
let eventsSubscription = null;
let notificationsSubscription = null;

// =============================================
// Initialize App
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing SEPNAS Event Management App...');
    
    // Initialize authentication
    initializeAuth();
    
    // Load events
    loadEvents();
    
    // Set up real-time subscriptions
    setupRealtimeSubscriptions();
});

// =============================================
// Authentication
// =============================================
async function initializeAuth() {
    try {
        // Get current user
        const { data: { user } } = await getCurrentUser();
        currentUser = user;
        
        if (user) {
            console.log('✅ User authenticated:', user.email);
            updateUIForAuthenticatedUser(user);
        } else {
            console.log('ℹ️ No authenticated user');
            updateUIForGuestUser();
        }
        
        // Listen for auth state changes
        onAuthStateChange((event, session) => {
            console.log('Auth state changed:', event, session?.user?.email);
            currentUser = session?.user || null;
            
            if (currentUser) {
                updateUIForAuthenticatedUser(currentUser);
                setupUserNotifications();
            } else {
                updateUIForGuestUser();
                cleanupUserSubscriptions();
            }
        });
        
    } catch (error) {
        console.error('❌ Error initializing auth:', error);
    }
}

function updateUIForAuthenticatedUser(user) {
    // Update navigation
    const loginLink = document.querySelector('a[href="#login"]');
    if (loginLink) {
        loginLink.innerHTML = `<i class="fas fa-user me-1"></i>${user.email}`;
        loginLink.href = '#profile';
    }
    
    // Show user-specific features
    document.querySelectorAll('.user-only').forEach(el => el.style.display = 'block');
    document.querySelectorAll('.guest-only').forEach(el => el.style.display = 'none');
}

function updateUIForGuestUser() {
    // Update navigation
    const loginLink = document.querySelector('a[href="#profile"]');
    if (loginLink) {
        loginLink.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i>Login';
        loginLink.href = '#login';
    }
    
    // Hide user-specific features
    document.querySelectorAll('.user-only').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.guest-only').forEach(el => el.style.display = 'block');
}

// =============================================
// Events Management
// =============================================
async function loadEvents() {
    try {
        console.log('📅 Loading events...');
        const events = await getEvents();
        displayEvents(events);
    } catch (error) {
        console.error('❌ Error loading events:', error);
        showError('Failed to load events. Please try again.');
    }
}

function displayEvents(events) {
    const container = document.getElementById('events-container');
    
    if (!events || events.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No events scheduled at the moment.
                </div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = events.map(event => `
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">${event.title}</h5>
                    <p class="card-text text-muted">${event.description || 'No description available.'}</p>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            ${formatDate(event.event_date)}
                        </small>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            ${event.venue || 'Location TBD'}
                        </small>
                    </div>
                    ${event.category ? `
                        <span class="badge bg-primary mb-3">${event.category}</span>
                    ` : ''}
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-outline-primary btn-sm" onclick="viewEvent(${event.id})">
                        <i class="fas fa-eye me-1"></i>View Details
                    </button>
                    ${currentUser ? `
                        <button class="btn btn-outline-success btn-sm ms-2" onclick="registerForEvent(${event.id})">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// =============================================
// Real-time Subscriptions
// =============================================
function setupRealtimeSubscriptions() {
    // Subscribe to events changes
    eventsSubscription = subscribeToEvents((payload) => {
        console.log('📅 Event change detected:', payload);
        
        // Reload events when changes occur
        loadEvents();
        
        // Show notification for new events
        if (payload.eventType === 'INSERT') {
            showNotification('New Event Added', `A new event "${payload.new.title}" has been added!`);
        }
    });
}

function setupUserNotifications() {
    if (!currentUser) return;
    
    // Subscribe to user notifications
    notificationsSubscription = subscribeToUserNotifications(currentUser.id, (payload) => {
        console.log('🔔 New notification:', payload);
        
        const notification = payload.new;
        showNotification(notification.title, notification.message);
        
        // Update notification status in UI
        updateNotificationStatus(true);
    });
}

function cleanupUserSubscriptions() {
    if (notificationsSubscription) {
        notificationsSubscription.unsubscribe();
        notificationsSubscription = null;
    }
}

// =============================================
// Event Actions
// =============================================
function viewEvent(eventId) {
    // Implement event details modal or page
    console.log('Viewing event:', eventId);
    // You can implement a modal or redirect to event details page
}

async function registerForEvent(eventId) {
    if (!currentUser) {
        showError('Please login to register for events.');
        return;
    }
    
    try {
        // Implement event registration logic
        console.log('Registering for event:', eventId);
        showSuccess('Successfully registered for the event!');
    } catch (error) {
        console.error('Error registering for event:', error);
        showError('Failed to register for event. Please try again.');
    }
}

// =============================================
// Notification Management
// =============================================
function updateNotificationStatus(isEnabled) {
    const statusElement = document.getElementById('notification-status');
    if (statusElement) {
        statusElement.innerHTML = isEnabled 
            ? '<i class="fas fa-bell me-1"></i>Enabled'
            : '<i class="fas fa-bell-slash me-1"></i>Disabled';
    }
}

function showNotification(title, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'toast align-items-center text-white bg-primary border-0';
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to toast container
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(notification);
    
    // Show toast
    const toast = new bootstrap.Toast(notification);
    toast.show();
    
    // Remove from DOM after hiding
    notification.addEventListener('hidden.bs.toast', () => {
        notification.remove();
    });
}

// =============================================
// UI Helpers
// =============================================
function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'danger');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of page
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// =============================================
// OneSignal Integration
// =============================================
function requestNotificationPermission() {
    if (typeof subscribeToNotifications === 'function') {
        subscribeToNotifications();
    } else {
        showError('OneSignal not initialized. Please refresh the page.');
    }
}

// =============================================
// Export functions for global use
// =============================================
window.viewEvent = viewEvent;
window.registerForEvent = registerForEvent;
window.requestNotificationPermission = requestNotificationPermission;
