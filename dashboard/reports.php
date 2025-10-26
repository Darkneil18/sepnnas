<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/User.php';
require_once '../classes/Attendance.php';
require_once '../classes/Feedback.php';

// Check if user can view reports
checkRole(['admin', 'teacher']);

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$user = new User($db);
$attendance = new Attendance($db);
$feedback = new Feedback($db);

// Get date range parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get statistics
$event_stats = $event->getEventStats();
$user_stats = $user->getUserStats();
$attendance_summary = $attendance->getAttendanceSummary($start_date, $end_date);
$feedback_summary = $feedback->getFeedbackSummary($start_date, $end_date);
$overall_feedback_stats = $feedback->getOverallFeedbackStats();

// Get recent events
$recent_events = $event->getAllEvents('published', 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <style>
        .sidebar {min-height: 100vh;background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);}
        .sidebar .nav-link {color: rgba(255,255,255,0.8);padding: 12px 20px;border-radius: 8px;margin: 2px 0;transition: all 0.3s;}
        .sidebar .nav-link:hover, .sidebar .nav-link.active {color: white;background: rgba(255,255,255,0.1);transform: translateX(5px);}
        .main-content {background: #f8f9fa;min-height: 100vh;}
        .stats-card {background: white;border-radius: 15px;padding: 1.5rem;box-shadow: 0 5px 15px rgba(0,0,0,0.08);border-left: 4px solid;transition: transform 0.3s;}
        .stats-card:hover {transform: translateY(-5px);}
        .stats-card.events {border-left-color: #007bff;}
        .stats-card.users {border-left-color: #28a745;}
        .stats-card.attendance {border-left-color: #ffc107;}
        .stats-card.feedback {border-left-color: #dc3545;}
        .chart-container, .filter-card, .table-card {background: white;border-radius: 15px;padding: 1.5rem;box-shadow: 0 5px 15px rgba(0,0,0,0.08);margin-bottom: 2rem;}
        .table-card {overflow: hidden;}
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-0"><i class="fas fa-graduation-cap me-2"></i>SEPNAS</h4>
                    <small class="text-white-50">Event Management</small>
                </div>
                <nav class="nav flex-column p-3">
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a>
                    <a class="nav-link" href="events.php"><i class="fas fa-calendar-alt me-2"></i>Events</a>
                    <a class="nav-link" href="calendar.php"><i class="fas fa-calendar me-2"></i>Calendar</a>
                    <?php if(canManageEvents()): ?>
                    <a class="nav-link" href="manage-events.php"><i class="fas fa-plus-circle me-2"></i>Manage Events</a>
                    <a class="nav-link" href="categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                    <a class="nav-link" href="venues.php"><i class="fas fa-map-marker-alt me-2"></i>Venues</a>
                    <?php endif; ?>
                    <a class="nav-link" href="attendance.php"><i class="fas fa-user-check me-2"></i>Attendance</a>
                    <a class="nav-link" href="feedback.php"><i class="fas fa-comments me-2"></i>Feedback</a>
                    <a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                    <a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a>
                    <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                    <div>
                        <button class="btn btn-outline-secondary" onclick="exportReport()"><i class="fas fa-download me-2"></i>Export Report</button>
                        <button class="btn btn-outline-primary" onclick="refreshReports()"><i class="fas fa-sync-alt me-2"></i>Refresh</button>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4 d-grid align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Key Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card events">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $event_stats['total_events'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Total Events</p>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card users">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $user_stats['total_users'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Total Users</p>
                                </div>
                                <i class="fas fa-users fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card attendance">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $overall_feedback_stats['total_feedback'] ?? 0; ?></h3>
                                    <p class="text-muted mb-0">Feedback Received</p>
                                </div>
                                <i class="fas fa-comments fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card feedback">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <!-- ✅ Fixed number_format null issue -->
                                    <h3 class="mb-0"><?php echo number_format($overall_feedback_stats['average_rating'] ?? 0, 1); ?></h3>
                                    <p class="text-muted mb-0">Avg Rating</p>
                                </div>
                                <i class="fas fa-star fa-2x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Event Status Distribution</h5>
                            <canvas id="eventStatusChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-users me-2"></i>User Role Distribution</h5>
                            <canvas id="userRoleChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-star me-2"></i>Feedback Rating Distribution</h5>
                            <canvas id="feedbackRatingChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Event Trends</h5>
                            <canvas id="eventTrendsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="table-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Recent Events</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Status</th>
                                        <th>Participants</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_events as $event_item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($event_item['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($event_item['category_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($event_item['event_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($event_item['start_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($event_item['venue_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $event_item['status'] === 'published' ? 'success' : 
                                                        ($event_item['status'] === 'draft' ? 'warning' : 
                                                        ($event_item['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($event_item['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $event_item['registered_count']; ?> / <?php echo $event_item['max_participants'] ?: '∞'; ?></td>
                                            <td>
                                                <?php
                                                $event_feedback_stats = $feedback->getEventFeedbackStats($event_item['id']);
                                                $avg = $event_feedback_stats['average_rating'] ?? null;
                                                echo $avg ? number_format($avg, 1) . '★' : '<span class="text-muted">No rating</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const eventStatusCtx=document.getElementById('eventStatusChart').getContext('2d');
new Chart(eventStatusCtx,{type:'doughnut',data:{labels:['Published','Draft','Completed','Cancelled'],datasets:[{data:[
<?php echo $event_stats['published_events'] ?? 0; ?>,
<?php echo $event_stats['draft_events'] ?? 0; ?>,
<?php echo $event_stats['completed_events'] ?? 0; ?>,
<?php echo ($event_stats['total_events'] ?? 0)-($event_stats['published_events'] ?? 0)-($event_stats['draft_events'] ?? 0)-($event_stats['completed_events'] ?? 0); ?>
],backgroundColor:['#28a745','#ffc107','#6c757d','#dc3545']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});

const userRoleCtx=document.getElementById('userRoleChart').getContext('2d');
new Chart(userRoleCtx,{type:'pie',data:{labels:['Students','Teachers','Admins'],datasets:[{data:[
<?php echo $user_stats['students'] ?? 0; ?>,
<?php echo $user_stats['teachers'] ?? 0; ?>,
<?php echo $user_stats['admins'] ?? 0; ?>
],backgroundColor:['#007bff','#28a745','#dc3545']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});

const feedbackRatingCtx=document.getElementById('feedbackRatingChart').getContext('2d');
new Chart(feedbackRatingCtx,{type:'bar',data:{labels:['1★','2★','3★','4★','5★'],datasets:[{label:'Number of Ratings',data:[
<?php echo $overall_feedback_stats['terrible_count'] ?? 0; ?>,
<?php echo $overall_feedback_stats['poor_count'] ?? 0; ?>,
<?php echo $overall_feedback_stats['average_count'] ?? 0; ?>,
<?php echo $overall_feedback_stats['good_count'] ?? 0; ?>,
<?php echo $overall_feedback_stats['excellent_count'] ?? 0; ?>
],backgroundColor:['#dc3545','#fd7e14','#ffc107','#20c997','#28a745']}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});

const eventTrendsCtx=document.getElementById('eventTrendsChart').getContext('2d');
new Chart(eventTrendsCtx,{type:'line',data:{labels:['Jan','Feb','Mar','Apr','May','Jun'],datasets:[{label:'Events',data:[12,19,3,5,2,3],borderColor:'#007bff',backgroundColor:'rgba(0,123,255,0.1)',tension:0.4}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});

function refreshReports(){location.reload();}
function exportReport(){alert('Export functionality coming soon!');}
setInterval(()=>location.reload(),300000);
</script>
</body>
</html>
