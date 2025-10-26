<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$user = new User($db);

// Get dashboard statistics
$event_stats = $event->getEventStats();
$user_stats = $user->getUserStats();
$upcoming_events = $event->getUpcomingEvents(5);

// Get recent events
$recent_events = $event->getAllEvents('published', 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SEPNAS Event Management</title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.events { border-left-color: #007bff; }
        .stat-card.users { border-left-color: #28a745; }
        .stat-card.upcoming { border-left-color: #ffc107; }
        .stat-card.completed { border-left-color: #6c757d; }
        .event-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        .welcome-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </a>
                        <a class="nav-link" href="calendar.php">
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
                        <a class="nav-link" href="notification-settings.php">
                            <i class="fas fa-cog me-2"></i>Notification Settings
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
                    <!-- Welcome Header -->
                    <div class="welcome-header">
                        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
                        <p class="mb-0">Here's what's happening at SEPNAS today.</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card events">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $event_stats['total_events']; ?></h3>
                                        <p class="text-muted mb-0">Total Events</p>
                                    </div>
                                    <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card users">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $user_stats['total_users']; ?></h3>
                                        <p class="text-muted mb-0">Total Users</p>
                                    </div>
                                    <i class="fas fa-users fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card upcoming">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $event_stats['upcoming_events']; ?></h3>
                                        <p class="text-muted mb-0">Upcoming Events</p>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card completed">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $event_stats['completed_events']; ?></h3>
                                        <p class="text-muted mb-0">Completed Events</p>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Upcoming Events -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Events</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($upcoming_events)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No upcoming events scheduled.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach($upcoming_events as $event_item): ?>
                                            <div class="event-card" style="border-left-color: <?php echo $event_item['category_color']; ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($event_item['title']); ?></h6>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?php echo date('M d, Y', strtotime($event_item['event_date'])); ?>
                                                            <i class="fas fa-clock ms-3 me-1"></i>
                                                            <?php echo date('g:i A', strtotime($event_item['start_time'])); ?>
                                                        </p>
                                                        <p class="text-muted mb-0">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($event_item['venue_name']); ?>
                                                        </p>
                                                    </div>
                                                    <span class="badge" style="background-color: <?php echo $event_item['category_color']; ?>">
                                                        <?php echo htmlspecialchars($event_item['category_name']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(canManageEvents()): ?>
                                    <a href="manage-events.php" class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-plus me-2"></i>Create Event
                                    </a>
                                    <a href="categories.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-tags me-2"></i>Manage Categories
                                    </a>
                                    <a href="venues.php" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>Manage Venues
                                    </a>
                                    <?php endif; ?>
                                    <a href="events.php" class="btn btn-outline-success w-100 mb-2">
                                        <i class="fas fa-calendar-alt me-2"></i>View All Events
                                    </a>
                                    <a href="calendar.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-calendar me-2"></i>Calendar View
                                    </a>
                                </div>
                            </div>

                            <!-- User Info -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Your Profile</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                                    <p class="mb-1"><strong>Role:</strong> <span class="badge bg-primary"><?php echo ucfirst($_SESSION['role']); ?></span></p>
                                    <?php if(isset($_SESSION['department']) && $_SESSION['department']): ?>
                                    <p class="mb-0"><strong>Department:</strong> <?php echo htmlspecialchars($_SESSION['department']); ?></p>
                                    <?php endif; ?>
                                    <a href="profile.php" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-edit me-1"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
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
            console.log('OneSignal v16 initialized for dashboard');
        });
    </script>
    
    <!-- OneSignal Initialization -->
    <script src="../assets/js/onesignal-config.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
