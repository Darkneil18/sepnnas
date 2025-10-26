<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/EventCategory.php';
require_once '../classes/Venue.php';
require_once '../classes/User.php';

// Check if user can manage events
checkRole(['admin', 'teacher']);

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$category = new EventCategory($db);
$venue = new Venue($db);
$user = new User($db);

$event_id = isset($_GET['id']) ? $_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get data
$categories = $category->getAllCategories();
$venues = $venue->getAllVenues();
$organizers = $user->getAllUsers(['teacher', 'admin']);

$event_details = null;
if($event_id) {
    $event_details = $event->getEventById($event_id);
}

$success_message = '';
$error_message = '';

// Handle form submissions
if($_POST) {
    if(isset($_POST['create_event']) || isset($_POST['update_event'])) {
        $event->title = $_POST['title'];
        $event->description = $_POST['description'];
        $event->event_date = $_POST['event_date'];
        $event->start_time = $_POST['start_time'];
        $event->end_time = $_POST['end_time'];
        $event->venue_id = $_POST['venue_id'];
        $event->category_id = $_POST['category_id'];
        $event->organizer_id = $_POST['organizer_id'];
        $event->max_participants = $_POST['max_participants'];
        $event->registration_deadline = $_POST['registration_deadline'];
        $event->status = $_POST['status'];
        $event->is_public = isset($_POST['is_public']) ? 1 : 0;
        
        if(isset($_POST['create_event'])) {
            $event->created_by = $_SESSION['user_id'];
            $event_id = $event->create();
            if($event_id) {
                $success_message = "Event created successfully!";
                header('Location: manage-events.php?id=' . $event_id);
                exit();
            } else {
                $error_message = "Failed to create event.";
            }
        } else {
            $event->id = $event_id;
            if($event->update()) {
                $success_message = "Event updated successfully!";
                $event_details = $event->getEventById($event_id);
            } else {
                $error_message = "Failed to update event.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 100%;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
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
                        <a class="nav-link" href="calendar.php">
                            <i class="fas fa-calendar me-2"></i>Calendar
                        </a>
                        <a class="nav-link active" href="manage-events.php">
                            <i class="fas fa-plus-circle me-2"></i>Manage Events
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="venues.php">
                            <i class="fas fa-map-marker-alt me-2"></i>Venues
                        </a>
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-user-check me-2"></i>Attendance
                        </a>
                        <a class="nav-link" href="feedback.php">
                            <i class="fas fa-comments me-2"></i>Feedback
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
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
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="fas fa-<?php echo $event_id ? 'edit' : 'plus-circle'; ?> me-2"></i>
                            <?php echo $event_id ? 'Edit Event' : 'Create Event'; ?>
                        </h2>
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Events
                        </a>
                    </div>

                    <?php if($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-card">
                        <form method="POST" id="eventForm">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Event Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo $event_details ? htmlspecialchars($event_details['title']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $event_details ? htmlspecialchars($event_details['description']) : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="draft" <?php echo ($event_details && $event_details['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo ($event_details && $event_details['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                            <option value="cancelled" <?php echo ($event_details && $event_details['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" 
                                                   <?php echo ($event_details && $event_details['is_public']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_public">
                                                Public Event
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Event Date *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" 
                                               value="<?php echo $event_details ? $event_details['event_date'] : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Start Time *</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" 
                                               value="<?php echo $event_details ? $event_details['start_time'] : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label">End Time *</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" 
                                               value="<?php echo $event_details ? $event_details['end_time'] : ''; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="venue_id" class="form-label">Venue *</label>
                                        <select class="form-select" id="venue_id" name="venue_id" required>
                                            <option value="">Select Venue</option>
                                            <?php foreach($venues as $venue_item): ?>
                                                <option value="<?php echo $venue_item['id']; ?>" 
                                                        <?php echo ($event_details && $event_details['venue_id'] == $venue_item['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($venue_item['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach($categories as $category_item): ?>
                                                <option value="<?php echo $category_item['id']; ?>" 
                                                        <?php echo ($event_details && $event_details['category_id'] == $category_item['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category_item['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="organizer_id" class="form-label">Organizer *</label>
                                        <select class="form-select" id="organizer_id" name="organizer_id" required>
                                            <option value="">Select Organizer</option>
                                            <?php foreach($organizers as $organizer): ?>
                                                <option value="<?php echo $organizer['id']; ?>" 
                                                        <?php echo ($event_details && $event_details['organizer_id'] == $organizer['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($organizer['first_name'] . ' ' . $organizer['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label">Max Participants</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" 
                                               value="<?php echo $event_details ? $event_details['max_participants'] : ''; ?>" 
                                               min="1" placeholder="Leave empty for unlimited">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                        <input type="date" class="form-control" id="registration_deadline" name="registration_deadline" 
                                               value="<?php echo $event_details ? $event_details['registration_deadline'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="events.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="<?php echo $event_id ? 'update_event' : 'create_event'; ?>" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $event_id ? 'save' : 'plus'; ?> me-2"></i>
                                    <?php echo $event_id ? 'Update Event' : 'Create Event'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const eventDate = new Date(document.getElementById('event_date').value);
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const registrationDeadline = document.getElementById('registration_deadline').value;
            
            // Check if event date is in the past
            if (eventDate < new Date()) {
                e.preventDefault();
                alert('Event date cannot be in the past.');
                return;
            }
            
            // Check if start time is before end time
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                alert('Start time must be before end time.');
                return;
            }
            
            // Check if registration deadline is before event date
            if (registrationDeadline && eventDate && new Date(registrationDeadline) >= eventDate) {
                e.preventDefault();
                alert('Registration deadline must be before event date.');
                return;
            }
        });

        // Auto-fill registration deadline
        document.getElementById('event_date').addEventListener('change', function() {
            const eventDate = new Date(this.value);
            const registrationDeadline = document.getElementById('registration_deadline');
            
            if (eventDate && !registrationDeadline.value) {
                // Set deadline to 1 day before event
                const deadline = new Date(eventDate);
                deadline.setDate(deadline.getDate() - 1);
                registrationDeadline.value = deadline.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
