<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../classes/Feedback.php';
require_once '../classes/Event.php';

// ✅ Add role helper functions (prevents fatal error)
function canManageEvents() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'organizer']);
}
function canViewReports() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

$database = new Database();
$db = $database->getConnection();
$feedback = new Feedback($db);
$event = new Event($db);

// Get parameters
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : null;

// Get events for dropdown
$events = $event->getAllEvents('completed');

// Get feedback data
$feedback_data = [];
$feedback_stats = null;
$event_details = null;

if ($event_id) {
    $event_details = $event->getEventById($event_id);
    $feedback_data = $feedback->getEventFeedback($event_id);
    $feedback_stats = $feedback->getEventFeedbackStats($event_id);
} else {
    $feedback_data = $feedback->getAllFeedback(null, $rating_filter, 20);
}

// Get overall stats
$overall_stats = $feedback->getOverallFeedbackStats() ?? [
    'total_feedback' => 0,
    'average_rating' => 0,
    'positive_feedback' => 0,
    'negative_feedback' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - SEPNAS Event Management</title>
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
        .stats-card.total { border-left-color: #007bff; }
        .stats-card.average { border-left-color: #28a745; }
        .stats-card.positive { border-left-color: #ffc107; }
        .stats-card.negative { border-left-color: #dc3545; }
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .rating-stars { color: #ffc107; font-size: 1.2rem; }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .rating-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
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
                        <a class="nav-link" href="attendance.php"><i class="fas fa-user-check me-2"></i>Attendance</a>
                        <a class="nav-link active" href="feedback.php"><i class="fas fa-comments me-2"></i>Feedback</a>
                        <?php if(isAdmin()): ?>
                        <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i>User Management</a>
                        <?php endif; ?>
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
                        <h2><i class="fas fa-comments me-2"></i>Event Feedback</h2>
                        <button class="btn btn-outline-secondary" onclick="refreshFeedback()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>

                    <!-- Overall Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card total">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $overall_stats['total_feedback'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Total Feedback</p>
                                    </div>
                                    <i class="fas fa-comments fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card average">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= number_format($overall_stats['average_rating'] ?? 0, 1) ?></h3>
                                        <p class="text-muted mb-0">Average Rating</p>
                                    </div>
                                    <i class="fas fa-star fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card positive">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $overall_stats['positive_feedback'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Positive (4-5★)</p>
                                    </div>
                                    <i class="fas fa-thumbs-up fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card negative">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?= $overall_stats['negative_feedback'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Negative (1-2★)</p>
                                    </div>
                                    <i class="fas fa-thumbs-down fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="event_id" class="form-label">Event</label>
                                <select class="form-select" id="event_id" name="event_id" onchange="this.form.submit()">
                                    <option value="">All Events</option>
                                    <?php foreach ($events as $event_item): ?>
                                    <option value="<?= $event_item['id'] ?>" <?= $event_id == $event_item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event_item['title'] . ' - ' . date('M d, Y', strtotime($event_item['event_date']))) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating" name="rating" onchange="this.form.submit()">
                                    <option value="">All Ratings</option>
                                    <?php for ($r = 5; $r >= 1; $r--): ?>
                                        <option value="<?= $r ?>" <?= $rating_filter == $r ? 'selected' : '' ?>><?= $r ?> Stars</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="feedback.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Feedback List -->
                    <div class="row">
                        <?php if (empty($feedback_data)): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No feedback found</h4>
                            </div>
                        <?php else: ?>
                            <?php foreach ($feedback_data as $f): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="feedback-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></h6>
                                                <small class="text-muted"><?= ucfirst($f['role']) ?></small>
                                            </div>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?= $i <= $f['rating'] ? '' : '-o' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($f['event_title']): ?>
                                            <p class="text-muted mb-2"><i class="fas fa-calendar me-1"></i><?= htmlspecialchars($f['event_title']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($f['comments']): ?>
                                            <strong>Comments:</strong>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($f['comments']) ?></p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted"><?= date('M d, Y g:i A', strtotime($f['submitted_at'])) ?></small>
                                            <span class="rating-badge badge bg-<?= $f['rating'] >= 4 ? 'success' : ($f['rating'] >= 3 ? 'warning' : 'danger') ?>">
                                                <?= $f['rating'] ?>★
                                            </span>
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
    <script>
        function refreshFeedback() {
            location.reload();
        }
        // Auto-refresh every 2 mins
        setInterval(refreshFeedback, 120000);
    </script>
</body>
</html>
