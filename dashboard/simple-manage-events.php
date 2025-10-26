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

// Get data
$categories = $category->getAllCategories();
$venues = $venue->getAllVenues();
$organizers = $user->getAllUsers(['teacher', 'admin']);

$success_message = '';
$error_message = '';

// Handle form submissions
if($_POST) {
    try {
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
        $event->created_by = $_SESSION['user_id'];
        
        $event_id = $event->create();
        if($event_id) {
            $success_message = "Event created successfully!";
        } else {
            $error_message = "Failed to create event.";
        }
    } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><i class="fas fa-plus-circle me-2"></i>Create Event</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public">
                                    <label class="form-check-label" for="is_public">Public Event</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" class="form-control" id="event_date" name="event_date" required>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
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
                                        <option value="<?php echo $venue_item['id']; ?>">
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
                                        <option value="<?php echo $category_item['id']; ?>">
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
                                        <option value="<?php echo $organizer['id']; ?>">
                                            <?php echo htmlspecialchars($organizer['first_name'] . ' ' . $organizer['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_participants" class="form-label">Max Participants</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                <input type="date" class="form-control" id="registration_deadline" name="registration_deadline">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="events.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" name="create_event" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
