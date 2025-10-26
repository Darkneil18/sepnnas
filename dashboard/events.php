<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/EventCategory.php';
require_once '../classes/Venue.php';

// ✅ Add helper functions if not already defined
if (!function_exists('canManageEvents')) {
    function canManageEvents() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'organizer']);
    }
}

if (!function_exists('canViewReports')) {
    function canViewReports() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin']);
    }
}

// ✅ Database connections
$database = new Database();
$db = $database->getConnection();

$event = new Event($db);
$category = new EventCategory($db);
$venue = new Venue($db);

// ✅ Get filters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// ✅ Get data
$events = $event->getAllEvents($status_filter);
$categories = $category->getAllCategories();

// Apply additional filters
if ($category_filter) {
    $events = array_filter($events, function($event_item) use ($category_filter) {
        return $event_item['category_id'] == $category_filter;
    });
}

if ($search) {
    $events = array_filter($events, function($event_item) use ($search) {
        return stripos($event_item['title'], $search) !== false ||
               stripos($event_item['description'], $search) !== false ||
               stripos($event_item['venue_name'], $search) !== false ||
               stripos($event_item['organizer_first_name'] . ' ' . $event_item['organizer_last_name'], $search) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - SEPNAS Event Management</title>
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
        .event-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .event-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="events.php">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </a>
                        <a class="nav-link" href="calendar.php">
                            <i class="fas fa-calendar me-2"></i>Calendar
                        </a>
                        <?php if (canManageEvents()): ?>
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
                        <?php if (canViewReports()): ?>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-calendar-alt me-2"></i>Events</h2>
                        <?php if (canManageEvents()): ?>
                        <a href="manage-events.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id']; ?>" <?= $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                       value="<?= htmlspecialchars($search); ?>" placeholder="Search events...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Events List -->
                    <div class="row">
                        <?php if (empty($events)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No events found</h4>
                                    <p class="text-muted">Try adjusting your filters or create a new event.</p>
                                    <?php if (canManageEvents()): ?>
                                    <a href="manage-events.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Event
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($events as $event_item): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="event-card">
                                        <div class="event-header">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="mb-0"><?= htmlspecialchars($event_item['title']); ?></h5>
                                                <span class="status-badge badge bg-<?= 
                                                    $event_item['status'] === 'published' ? 'success' : 
                                                    ($event_item['status'] === 'draft' ? 'warning' : 
                                                    ($event_item['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                                                ?>">
                                                    <?= ucfirst($event_item['status']); ?>
                                                </span>
                                            </div>
                                            <span class="badge" style="background-color: <?= $event_item['category_color']; ?>">
                                                <?= htmlspecialchars($event_item['category_name']); ?>
                                            </span>
                                        </div>

                                        <div class="mb-3">
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?= date('M d, Y', strtotime($event_item['event_date'])); ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                <?= date('g:i A', strtotime($event_item['start_time'])); ?> -
                                                <?= date('g:i A', strtotime($event_item['end_time'])); ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?= htmlspecialchars($event_item['venue_name']); ?>
                                            </p>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-users me-2"></i>
                                                <?= $event_item['registered_count']; ?> / <?= $event_item['max_participants'] ?: '∞'; ?> registered
                                            </p>
                                        </div>

                                        <?php if ($event_item['description']): ?>
                                        <p class="text-muted small mb-3">
                                            <?= htmlspecialchars(substr($event_item['description'], 0, 100)); ?>
                                            <?php if (strlen($event_item['description']) > 100): ?>...<?php endif; ?>
                                        </p>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Organizer: <?= htmlspecialchars($event_item['organizer_first_name'] . ' ' . $event_item['organizer_last_name']); ?>
                                            </small>
                                            <div>
                                                <a href="event-details.php?id=<?= $event_item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <?php if (canManageEvents()): ?>
                                                <a href="manage-events.php?id=<?= $event_item['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
