<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Event.php';
require_once __DIR__ . '/../classes/EventCategory.php';
require_once __DIR__ . '/../classes/Venue.php';
require_once __DIR__ . '/../classes/User.php';

checkRole(['admin', 'teacher']);

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$category = new EventCategory($db);
$venue = new Venue($db);
$user = new User($db);

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$categories = $category->getAllCategories();
$venues = $venue->getAllVenues();
$organizers = $user->getAllUsers(['teacher', 'admin']);

$event_details = $event_id ? $event->getEventById($event_id) : null;

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $event->title = trim($_POST['title']);
        $event->description = trim($_POST['description']);
        $event->event_date = $_POST['event_date'];
        $event->start_time = $_POST['start_time'];
        $event->end_time = $_POST['end_time'];
        $event->venue_id = $_POST['venue_id'];
        $event->category_id = $_POST['category_id'];
        $event->organizer_id = $_POST['organizer_id'];
        $event->status = $_POST['status'];
        $event->is_public = isset($_POST['is_public']) ? 1 : 0;
        $event->registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : null;
        $event->max_participants = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;

        // Validate date order
        if (!empty($event->registration_deadline) && strtotime($event->registration_deadline) < strtotime($event->event_date)) {
            throw new Exception('Registration deadline must be after event date.');
        }

        if (isset($_POST['create_event'])) {
            $event->created_by = $_SESSION['user_id'];
            $event_id = $event->create();
            if ($event_id) {
                header("Location: manage-events.php?id={$event_id}&success=1");
                exit();
            } else {
                throw new Exception('Failed to create event.');
            }
        } elseif (isset($_POST['update_event'])) {
            $event->id = $event_id;
            if ($event->update()) {
                header("Location: manage-events.php?id={$event_id}&updated=1");
                exit();
            } else {
                throw new Exception('Failed to update event.');
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

if (isset($_GET['success'])) $success_message = "Event created successfully!";
if (isset($_GET['updated'])) $success_message = "Event updated successfully!";
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
      .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
      .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 0; transition: 0.3s; }
      .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); transform: translateX(5px); }
      .main-content { background: #f8f9fa; min-height: 100vh; }
      .form-card { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 px-0 sidebar">
      <div class="p-3">
        <h4 class="text-white mb-0"><i class="fas fa-graduation-cap me-2"></i>SEPNAS</h4>
        <small class="text-white-50">Event Management</small>
      </div>
      <nav class="nav flex-column p-3">
        <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a>
        <a class="nav-link" href="events.php"><i class="fas fa-calendar-alt me-2"></i>Events</a>
        <a class="nav-link active" href="manage-events.php"><i class="fas fa-plus-circle me-2"></i>Manage Events</a>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10">
      <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2><i class="fas fa-<?php echo $event_id ? 'edit' : 'plus-circle'; ?> me-2"></i><?php echo $event_id ? 'Edit Event' : 'Create Event'; ?></h2>
          <a href="events.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Events</a>
        </div>

        <?php if ($success_message): ?>
          <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-card">
          <form method="POST" id="eventForm">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3">
                  <label class="form-label">Event Title *</label>
                  <input type="text" class="form-control" name="title" required value="<?php echo htmlspecialchars($event_details['title'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($event_details['description'] ?? ''); ?></textarea>
                </div>
              </div>

              <div class="col-md-4">
                <div class="mb-3">
                  <label class="form-label">Status *</label>
                  <select class="form-select" name="status" required>
                    <option value="draft" <?php echo ($event_details['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($event_details['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="cancelled" <?php echo ($event_details['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                  </select>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_public" <?php echo ($event_details['is_public'] ?? 0) ? 'checked' : ''; ?>>
                  <label class="form-check-label">Public Event</label>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <label class="form-label">Event Date *</label>
                <input type="date" class="form-control" name="event_date" required value="<?php echo $event_details['event_date'] ?? ''; ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Start Time *</label>
                <input type="time" class="form-control" name="start_time" required value="<?php echo $event_details['start_time'] ?? ''; ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">End Time *</label>
                <input type="time" class="form-control" name="end_time" required value="<?php echo $event_details['end_time'] ?? ''; ?>">
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label">Venue *</label>
                <select class="form-select" name="venue_id" required>
                  <option value="">Select Venue</option>
                  <?php foreach ($venues as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo ($event_details['venue_id'] ?? '') == $v['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($v['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Category *</label>
                <select class="form-select" name="category_id" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($event_details['category_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label">Organizer *</label>
                <select class="form-select" name="organizer_id" required>
                  <option value="">Select Organizer</option>
                  <?php foreach ($organizers as $o): ?>
                    <option value="<?php echo $o['id']; ?>" <?php echo ($event_details['organizer_id'] ?? '') == $o['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Max Participants</label>
                <input type="number" class="form-control" name="max_participants" min="1"
                       value="<?php echo $event_details['max_participants'] ?? ''; ?>" placeholder="Leave empty for unlimited">
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label">Registration Deadline</label>
                <input type="date" class="form-control" name="registration_deadline" value="<?php echo $event_details['registration_deadline'] ?? ''; ?>">
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <a href="events.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
              <button type="submit" name="<?php echo $event_id ? 'update_event' : 'create_event'; ?>" class="btn btn-primary">
                <i class="fas fa-<?php echo $event_id ? 'save' : 'plus'; ?> me-2"></i><?php echo $event_id ? 'Update Event' : 'Create Event'; ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Form validation
document.getElementById('eventForm').addEventListener('submit', function(e) {
  const eventDate = new Date(document.querySelector('[name="event_date"]').value);
  const start = document.querySelector('[name="start_time"]').value;
  const end = document.querySelector('[name="end_time"]').value;
  const regDeadline = document.querySelector('[name="registration_deadline"]').value;

  if (start && end && start >= end) {
    e.preventDefault();
    alert("Start time must be before end time.");
    return;
  }

  if (regDeadline && new Date(regDeadline) < eventDate) {
  e.preventDefault();
  alert("Registration deadline must be after event date.");
  return;
  }
});
</script>
</body>
</html>
