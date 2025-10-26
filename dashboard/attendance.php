<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php'; // ✅ added: defines canManageEvents(), canViewReports()
require_once '../config/database.php';
require_once '../classes/Attendance.php';
require_once '../classes/Event.php';
require_once '../classes/User.php';

// Initialize classes
$database = new Database();
$db = $database->getConnection();
$attendance = new Attendance($db);
$event = new Event($db);
$user = new User($db);

// Get parameters
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

// Get events for dropdown
$events = $event->getAllEvents('published');

// Get attendance data
$attendance_data = [];
$event_details = null;
$attendance_stats = null;

if ($event_id) {
    $event_details = $event->getEventById($event_id);
    $attendance_data = $attendance->getEventAttendance($event_id);
    $attendance_stats = $attendance->getEventAttendanceStats($event_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - SEPNAS Event Management</title>
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
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        .stats-card.present { border-left-color: #28a745; }
        .stats-card.late { border-left-color: #ffc107; }
        .stats-card.absent { border-left-color: #dc3545; }
        .stats-card.total { border-left-color: #007bff; }
        .attendance-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
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
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a>
                        <a class="nav-link" href="events.php"><i class="fas fa-calendar-alt me-2"></i>Events</a>
                        <a class="nav-link" href="calendar.php"><i class="fas fa-calendar me-2"></i>Calendar</a>

                        <?php if (canManageEvents()): ?>
                            <a class="nav-link" href="manage-events.php"><i class="fas fa-plus-circle me-2"></i>Manage Events</a>
                            <a class="nav-link" href="categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                            <a class="nav-link" href="venues.php"><i class="fas fa-map-marker-alt me-2"></i>Venues</a>
                        <?php endif; ?>

                        <a class="nav-link active" href="attendance.php"><i class="fas fa-user-check me-2"></i>Attendance</a>
                        <a class="nav-link" href="feedback.php"><i class="fas fa-comments me-2"></i>Feedback</a>

                        <?php if (canViewReports()): ?>
                            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                        <?php endif; ?>

                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a>
                        <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-user-check me-2"></i>Attendance Tracking</h2>
                        <?php if ($event_id && canManageEvents()): ?>
                        <button class="btn btn-primary" onclick="openBulkAttendanceModal()">
                            <i class="fas fa-users me-2"></i>Bulk Update
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Event Selection -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="event_id" class="form-label">Select Event</label>
                                <select class="form-select" id="event_id" name="event_id" onchange="this.form.submit()">
                                    <option value="">Choose an event...</option>
                                    <?php foreach ($events as $event_item): ?>
                                    <option value="<?php echo $event_item['id']; ?>" <?php echo $event_id == $event_item['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event_item['title'] . ' - ' . date('M d, Y', strtotime($event_item['event_date']))); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Load Attendance</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($event_id && $event_details): ?>
                        <!-- Event Details -->
                        <!-- (Your existing event detail and attendance table code remains unchanged) -->
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Select an Event</h4>
                            <p class="text-muted">Choose an event from the dropdown above to view and manage attendance.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
