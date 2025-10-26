// AJAX functionality for real-time updates
(function() {
    'use strict';

    // Base AJAX configuration
    const AJAX_CONFIG = {
        timeout: 30000,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    // Utility function for making AJAX requests
    function makeRequest(url, options = {}) {
        const config = {
            ...AJAX_CONFIG,
            ...options
        };

        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                showErrorMessage('Network error occurred. Please try again.');
                throw error;
            });
    }

    // Real-time data refresh functions
    window.AjaxUtils = {
        // Refresh dashboard statistics
        refreshDashboardStats: function() {
            return makeRequest('api/dashboard.php?action=stats')
                .then(data => {
                    if (data.success) {
                        updateDashboardStats(data.data);
                    }
                });
        },

        // Refresh events list
        refreshEvents: function() {
            return makeRequest('api/events.php?action=list')
                .then(data => {
                    if (data.success) {
                        updateEventsList(data.data);
                    }
                });
        },

        // Refresh calendar events
        refreshCalendar: function() {
            return makeRequest('api/calendar.php?action=events')
                .then(data => {
                    if (data.success && window.calendar) {
                        window.calendar.removeAllEvents();
                        window.calendar.addEventSource(data.data);
                    }
                });
        },

        // Refresh attendance data
        refreshAttendance: function(eventId) {
            return makeRequest(`api/attendance.php?action=list&event_id=${eventId}`)
                .then(data => {
                    if (data.success) {
                        updateAttendanceTable(data.data);
                    }
                });
        },

        // Refresh feedback data
        refreshFeedback: function() {
            return makeRequest('api/feedback.php?action=list')
                .then(data => {
                    if (data.success) {
                        updateFeedbackList(data.data);
                    }
                });
        },

        // Send notification
        sendNotification: function(title, message, targetAudience = 'all', targetUsers = null) {
            return makeRequest('api/notifications.php?action=send', {
                method: 'POST',
                body: JSON.stringify({
                    title: title,
                    message: message,
                    target_audience: targetAudience,
                    target_users: targetUsers
                })
            });
        },

        // Update event status
        updateEventStatus: function(eventId, status) {
            return makeRequest('api/events.php?action=update_status', {
                method: 'POST',
                body: JSON.stringify({
                    event_id: eventId,
                    status: status
                })
            });
        },

        // Register for event
        registerForEvent: function(eventId) {
            return makeRequest('api/events.php?action=register', {
                method: 'POST',
                body: JSON.stringify({
                    event_id: eventId
                })
            });
        },

        // Submit feedback
        submitFeedback: function(eventId, rating, comments, suggestions) {
            return makeRequest('api/feedback.php?action=submit', {
                method: 'POST',
                body: JSON.stringify({
                    event_id: eventId,
                    rating: rating,
                    comments: comments,
                    suggestions: suggestions
                })
            });
        },

        // Update attendance
        updateAttendance: function(eventId, userId, status, notes) {
            return makeRequest('api/attendance.php?action=update', {
                method: 'POST',
                body: JSON.stringify({
                    event_id: eventId,
                    user_id: userId,
                    status: status,
                    notes: notes
                })
            });
        }
    };

    // Update functions for DOM manipulation
    function updateDashboardStats(stats) {
        // Update statistics cards
        const elements = {
            totalEvents: document.querySelector('[data-stat="total_events"]'),
            totalUsers: document.querySelector('[data-stat="total_users"]'),
            upcomingEvents: document.querySelector('[data-stat="upcoming_events"]'),
            completedEvents: document.querySelector('[data-stat="completed_events"]')
        };

        Object.keys(elements).forEach(key => {
            if (elements[key] && stats[key]) {
                elements[key].textContent = stats[key];
            }
        });
    }

    function updateEventsList(events) {
        const container = document.getElementById('events-container');
        if (!container) return;

        if (events.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No events found</h4>
                    </div>
                </div>
            `;
            return;
        }

        const eventsHTML = events.map(event => `
            <div class="col-md-6 col-lg-4">
                <div class="event-card" style="border-left-color: ${event.category_color}">
                    <div class="event-header">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0">${event.title}</h5>
                            <span class="status-badge badge bg-${getStatusColor(event.status)}">
                                ${event.status}
                            </span>
                        </div>
                        <span class="badge" style="background-color: ${event.category_color}">
                            ${event.category_name}
                        </span>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted mb-2">
                            <i class="fas fa-calendar me-2"></i>
                            ${formatDate(event.event_date)}
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-clock me-2"></i>
                            ${formatTime(event.start_time)} - ${formatTime(event.end_time)}
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            ${event.venue_name}
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-users me-2"></i>
                            ${event.registered_count} / ${event.max_participants || '∞'} registered
                        </p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Organizer: ${event.organizer_first_name} ${event.organizer_last_name}
                        </small>
                        <div>
                            <a href="event-details.php?id=${event.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = eventsHTML;
    }

    function updateAttendanceTable(attendance) {
        const tbody = document.querySelector('#attendance-table tbody');
        if (!tbody) return;

        if (attendance.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-user-times fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No attendance records found.</p>
                    </td>
                </tr>
            `;
            return;
        }

        const rowsHTML = attendance.map(record => `
            <tr>
                <td>
                    <div>
                        <strong>${record.first_name} ${record.last_name}</strong>
                        <br>
                        <small class="text-muted">${record.email}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${getRoleColor(record.role)}">
                        ${record.role}
                    </span>
                </td>
                <td>${record.department || 'N/A'}</td>
                <td>
                    ${record.registration_date ? formatDate(record.registration_date) : '<span class="text-muted">Not registered</span>'}
                </td>
                <td>
                    ${record.status ? `<span class="status-badge badge bg-${getStatusColor(record.status)}">${record.status}</span>` : '<span class="text-muted">Not marked</span>'}
                </td>
                <td>
                    ${record.check_in_time ? formatDateTime(record.check_in_time) : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    ${record.notes ? `<span class="text-truncate d-inline-block" style="max-width: 150px;" title="${record.notes}">${record.notes}</span>` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editAttendance(${record.id}, '${record.status}', '${record.notes}')">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        tbody.innerHTML = rowsHTML;
    }

    function updateFeedbackList(feedback) {
        const container = document.getElementById('feedback-container');
        if (!container) return;

        if (feedback.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No feedback found</h4>
                    </div>
                </div>
            `;
            return;
        }

        const feedbackHTML = feedback.map(item => `
            <div class="col-md-6 col-lg-4">
                <div class="feedback-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1">${item.first_name} ${item.last_name}</h6>
                            <small class="text-muted">
                                ${item.role}
                                ${item.department ? `• ${item.department}` : ''}
                            </small>
                        </div>
                        <div class="rating-stars">
                            ${generateStars(item.rating)}
                        </div>
                    </div>
                    ${item.event_title ? `
                        <p class="text-muted mb-2">
                            <i class="fas fa-calendar me-1"></i>
                            ${item.event_title}
                            <br>
                            <small>${formatDate(item.event_date)}</small>
                        </p>
                    ` : ''}
                    ${item.comments ? `
                        <div class="mb-3">
                            <strong>Comments:</strong>
                            <p class="text-muted small mb-0">${item.comments}</p>
                        </div>
                    ` : ''}
                    ${item.suggestions ? `
                        <div class="mb-3">
                            <strong>Suggestions:</strong>
                            <p class="text-muted small mb-0">${item.suggestions}</p>
                        </div>
                    ` : ''}
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            ${formatDateTime(item.submitted_at)}
                        </small>
                        <span class="rating-badge badge bg-${getRatingColor(item.rating)}">
                            ${item.rating}★
                        </span>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = feedbackHTML;
    }

    // Helper functions
    function getStatusColor(status) {
        const colors = {
            'published': 'success',
            'draft': 'warning',
            'cancelled': 'danger',
            'completed': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    function getRoleColor(role) {
        const colors = {
            'admin': 'danger',
            'teacher': 'primary',
            'student': 'success'
        };
        return colors[role] || 'secondary';
    }

    function getRatingColor(rating) {
        if (rating >= 4) return 'success';
        if (rating >= 3) return 'warning';
        return 'danger';
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function formatTime(timeString) {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function formatDateTime(dateTimeString) {
        return new Date(dateTimeString).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star${i <= rating ? '' : '-o'}"></i>`;
        }
        return stars;
    }

    function showErrorMessage(message) {
        // Create or update error message display
        let errorDiv = document.getElementById('ajax-error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'ajax-error-message';
            errorDiv.className = 'alert alert-danger position-fixed';
            errorDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            document.body.appendChild(errorDiv);
        }
        
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }

    function showSuccessMessage(message) {
        // Create or update success message display
        let successDiv = document.getElementById('ajax-success-message');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.id = 'ajax-success-message';
            successDiv.className = 'alert alert-success position-fixed';
            successDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            document.body.appendChild(successDiv);
        }
        
        successDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentElement) {
                successDiv.remove();
            }
        }, 3000);
    }

    // Auto-refresh functionality
    function startAutoRefresh() {
        // Refresh dashboard stats every 30 seconds
        setInterval(() => {
            if (window.location.pathname.includes('index.php')) {
                AjaxUtils.refreshDashboardStats();
            }
        }, 30000);

        // Refresh events every 2 minutes
        setInterval(() => {
            if (window.location.pathname.includes('events.php')) {
                AjaxUtils.refreshEvents();
            }
        }, 120000);

        // Refresh calendar every 5 minutes
        setInterval(() => {
            if (window.location.pathname.includes('calendar.php')) {
                AjaxUtils.refreshCalendar();
            }
        }, 300000);

        // Refresh feedback every 3 minutes
        setInterval(() => {
            if (window.location.pathname.includes('feedback.php')) {
                AjaxUtils.refreshFeedback();
            }
        }, 180000);
    }

    // Initialize auto-refresh when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        startAutoRefresh();
    });

    // Make utility functions globally available
    window.AjaxUtils = AjaxUtils;
    window.showErrorMessage = showErrorMessage;
    window.showSuccessMessage = showSuccessMessage;

})();
