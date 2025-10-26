<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Event.php';

// ✅ Add helper functions for role-based access
if (!function_exists('canManageEvents')) {
    function canManageEvents() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher']);
    }
}

if (!function_exists('canViewReports')) {
    function canViewReports() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);

// Get events for calendar
$events = $event->getAllEvents();
$calendar_events = [];

foreach ($events as $event_item) {
    $calendar_events[] = [
        'id' => $event_item['id'],
        'title' => $event_item['title'],
        'start' => $event_item['event_date'] . 'T' . $event_item['start_time'],
        'end' => $event_item['event_date'] . 'T' . $event_item['end_time'],
        'color' => $event_item['category_color'],
        'url' => '',
        'extendedProps' => [
            'venue' => $event_item['venue_name'],
            'organizer' => $event_item['organizer_first_name'] . ' ' . $event_item['organizer_last_name'],
            'status' => $event_item['status'],
            'registered_count' => $event_item['registered_count'],
            'max_participants' => $event_item['max_participants']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
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
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .fc-event {
            border-radius: 6px !important;
            border: none !important;
            padding: 2px 6px !important;
            font-size: 0.85rem !important;
        }
        .fc-event-title {
            font-weight: 600 !important;
        }
        .fc-daygrid-event {
            margin: 1px 0 !important;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
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
                        <a class="nav-link active" href="calendar.php">
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
                        <?php endif; ?>
                        <?php if(canViewReports()): ?>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <?php endif; ?>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>Profile
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
                        <h2><i class="fas fa-calendar me-2"></i>Event Calendar</h2>
                        <div>
                            <?php if(canManageEvents()): ?>
                            <a href="manage-events.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Event
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" onclick="refreshCalendar()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #007bff;"></div>
                            <span>Academic</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #28a745;"></div>
                            <span>Sports</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffc107;"></div>
                            <span>Cultural</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #dc3545;"></div>
                            <span>Social</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #6c757d;"></div>
                            <span>Meeting</span>
                        </div>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                events: <?php echo json_encode($calendar_events); ?>,
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                eventMouseEnter: function(info) {
                    info.el.style.transform = 'scale(1.02)';
                    info.el.style.transition = 'transform 0.2s';
                },
                eventMouseLeave: function(info) {
                    info.el.style.transform = 'scale(1)';
                },
                dayMaxEvents: 3,
                moreLinkClick: 'popover',
                eventDisplay: 'block',
                height: 'auto',
                aspectRatio: 1.8,
                nowIndicator: true,
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime: '08:00',
                    endTime: '17:00'
                }
            });
            calendar.render();
            window.calendar = calendar;
        });

        function showEventDetails(event) {
            const title = event.title;
            const start = event.start;
            const end = event.end;
            const extendedProps = event.extendedProps;
            document.getElementById('eventModalTitle').textContent = title;
            document.getElementById('eventModalBody').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-calendar me-2"></i>Date:</strong> ${start.toLocaleDateString()}</p>
                        <p><strong><i class="fas fa-clock me-2"></i>Time:</strong> ${start.toLocaleTimeString()} - ${end.toLocaleTimeString()}</p>
                        <p><strong><i class="fas fa-map-marker-alt me-2"></i>Venue:</strong> ${extendedProps.venue || 'TBA'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-user me-2"></i>Organizer:</strong> ${extendedProps.organizer || 'TBA'}</p>
                        <p><strong><i class="fas fa-users me-2"></i>Participants:</strong> ${extendedProps.registered_count || 0} / ${extendedProps.max_participants || '∞'}</p>
                        <p><strong><i class="fas fa-info-circle me-2"></i>Status:</strong> 
                            <span class="badge bg-${extendedProps.status === 'published' ? 'success' :
                                (extendedProps.status === 'draft' ? 'warning' :
                                (extendedProps.status === 'cancelled' ? 'danger' : 'secondary'))}">
                                ${extendedProps.status || 'Unknown'}
                            </span>
                        </p>
                    </div>
                </div>
            `;
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        }

        function refreshCalendar() {
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
        }

        // Auto-refresh calendar every 5 minutes
        setInterval(function() {
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
        }, 300000);
    </script>
</body>
</html>
