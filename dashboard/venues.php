<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/Venue.php';

// Check if user can manage events
if (!canManageEvents()) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$venue = new Venue($db);

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $venue->name = $_POST['name'];
                $venue->location = $_POST['location'];
                $venue->capacity = $_POST['capacity'];
                $venue->facilities = $_POST['facilities'];
                
                if ($venue->create()) {
                    $message = 'Venue created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating venue.';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $venue->id = $_POST['venue_id'];
                $venue->name = $_POST['name'];
                $venue->location = $_POST['location'];
                $venue->capacity = $_POST['capacity'];
                $venue->facilities = $_POST['facilities'];
                
                if ($venue->update()) {
                    $message = 'Venue updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating venue.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $venue->id = $_POST['venue_id'];
                
                if ($venue->delete()) {
                    $message = 'Venue deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting venue.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all venues
$venues = $venue->getAllVenues();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Venues - SEPNAS Event Management</title>
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
        .venue-card {
            border-left: 4px solid #007bff;
            transition: transform 0.3s;
        }
        .venue-card:hover {
            transform: translateY(-2px);
        }
        .facilities-list {
            max-height: 100px;
            overflow-y: auto;
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
                        <a class="nav-link" href="manage-events.php">
                            <i class="fas fa-plus-circle me-2"></i>Manage Events
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link active" href="venues.php">
                            <i class="fas fa-map-marker-alt me-2"></i>Venues
                        </a>
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
                        <h2><i class="fas fa-map-marker-alt me-2"></i>Manage Venues</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#venueModal">
                            <i class="fas fa-plus me-2"></i>Add Venue
                        </button>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Venues Grid -->
                    <div class="row">
                        <?php if (empty($venues)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No venues found</h5>
                                <p class="text-muted">Create your first venue to get started.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($venues as $ven): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card venue-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($ven['name']); ?>
                                        </h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editVenue(<?php echo $ven['id']; ?>, '<?php echo htmlspecialchars($ven['name']); ?>', '<?php echo htmlspecialchars($ven['location']); ?>', <?php echo $ven['capacity']; ?>, '<?php echo htmlspecialchars($ven['facilities']); ?>')">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteVenue(<?php echo $ven['id']; ?>, '<?php echo htmlspecialchars($ven['name']); ?>')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-location-dot me-1"></i>
                                        <?php echo htmlspecialchars($ven['location']); ?>
                                    </p>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>
                                            Capacity: <?php echo number_format($ven['capacity']); ?> people
                                        </small>
                                    </div>
                                    <?php if (!empty($ven['facilities'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Facilities:</small>
                                        <div class="facilities-list">
                                            <small class="text-muted"><?php echo htmlspecialchars($ven['facilities']); ?></small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $ven['event_count']; ?> events
                                        </small>
                                        <span class="badge bg-primary">
                                            <?php echo number_format($ven['capacity']); ?> capacity
                                        </span>
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

    <!-- Venue Modal -->
    <div class="modal fade" id="venueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="venueModalTitle">Add Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="venueForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="venueAction" value="create">
                        <input type="hidden" name="venue_id" id="venueId">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Venue Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="facilities" class="form-label">Facilities</label>
                            <textarea class="form-control" id="facilities" name="facilities" rows="3" placeholder="e.g., Projector, Sound System, WiFi, Air Conditioning"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="venueSubmit">Add Venue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the venue "<span id="deleteVenueName"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="venue_id" id="deleteVenueId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editVenue(id, name, location, capacity, facilities) {
            document.getElementById('venueModalTitle').textContent = 'Edit Venue';
            document.getElementById('venueAction').value = 'update';
            document.getElementById('venueId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('location').value = location;
            document.getElementById('capacity').value = capacity;
            document.getElementById('facilities').value = facilities;
            document.getElementById('venueSubmit').textContent = 'Update Venue';
            
            new bootstrap.Modal(document.getElementById('venueModal')).show();
        }

        function deleteVenue(id, name) {
            document.getElementById('deleteVenueId').value = id;
            document.getElementById('deleteVenueName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reset form when modal is hidden
        document.getElementById('venueModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('venueForm').reset();
            document.getElementById('venueModalTitle').textContent = 'Add Venue';
            document.getElementById('venueAction').value = 'create';
            document.getElementById('venueSubmit').textContent = 'Add Venue';
        });
    </script>
</body>
</html>
