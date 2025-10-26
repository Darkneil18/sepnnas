<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Check if user can manage users (admin only)
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                $user->username = $_POST['username'];
                $user->email = $_POST['email'];
                $user->password = $_POST['password'];
                $user->first_name = $_POST['first_name'];
                $user->last_name = $_POST['last_name'];
                $user->role = $_POST['role'];
                $user->phone = $_POST['phone'];
                $user->department = $_POST['department'];
                $user->grade_level = $_POST['grade_level'];
                $user->section = $_POST['section'];
                $user->is_active = 1;
                
                // Check if user already exists
                if ($user->userExists($user->username, $user->email)) {
                    $message = 'Username or email already exists.';
                    $message_type = 'danger';
                } else {
                    if ($user->create()) {
                        $message = 'User created successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error creating user.';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'update_user':
                $user->id = $_POST['user_id'];
                $user->first_name = $_POST['first_name'];
                $user->last_name = $_POST['last_name'];
                $user->email = $_POST['email'];
                $user->phone = $_POST['phone'];
                $user->department = $_POST['department'];
                $user->grade_level = $_POST['grade_level'];
                $user->section = $_POST['section'];
                $user->is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if ($user->update()) {
                    $message = 'User updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating user.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_user':
                $user->id = $_POST['user_id'];
                
                if ($user->delete()) {
                    $message = 'User deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting user.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Get users based on filters
$users = $user->getAllUsers();
if ($role_filter) {
    $users = array_filter($users, function($u) use ($role_filter) {
        return $u['role'] === $role_filter;
    });
}
if ($status_filter !== '') {
    $users = array_filter($users, function($u) use ($status_filter) {
        return $u['is_active'] == $status_filter;
    });
}
if ($search) {
    $users = array_filter($users, function($u) use ($search) {
        return stripos($u['first_name'] . ' ' . $u['last_name'], $search) !== false ||
               stripos($u['email'], $search) !== false ||
               stripos($u['username'], $search) !== false;
    });
}

// Get user statistics
$user_stats = $user->getUserStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SEPNAS Event Management</title>
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
        .stats-card.admins { border-left-color: #dc3545; }
        .stats-card.teachers { border-left-color: #28a745; }
        .stats-card.students { border-left-color: #ffc107; }
        .user-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .user-card:hover {
            transform: translateY(-2px);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
                        <a class="nav-link active" href="users.php">
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
                        <h2><i class="fas fa-users me-2"></i>User Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-2"></i>Add User
                        </button>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card total">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $user_stats['total_users']; ?></h3>
                                        <p class="text-muted mb-0">Total Users</p>
                                    </div>
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card admins">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $user_stats['admins']; ?></h3>
                                        <p class="text-muted mb-0">Administrators</p>
                                    </div>
                                    <i class="fas fa-user-shield fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card teachers">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $user_stats['teachers']; ?></h3>
                                        <p class="text-muted mb-0">Teachers</p>
                                    </div>
                                    <i class="fas fa-chalkboard-teacher fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card students">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-0"><?php echo $user_stats['students']; ?></h3>
                                        <p class="text-muted mb-0">Students</p>
                                    </div>
                                    <i class="fas fa-user-graduate fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, or username">
                            </div>
                            <div class="col-md-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <a href="users.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Users List -->
                    <div class="row">
                        <?php if (empty($users)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No users found</h4>
                            <p class="text-muted">Try adjusting your filters or add a new user.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="user-card">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="user-avatar me-3">
                                        <?php echo strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></h6>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($u['email']); ?></p>
                                        <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'teacher' ? 'success' : 'warning'); ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'secondary'; ?> ms-1">
                                            <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name']); ?>', '<?php echo htmlspecialchars($u['last_name']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo htmlspecialchars($u['phone']); ?>', '<?php echo htmlspecialchars($u['department']); ?>', '<?php echo htmlspecialchars($u['grade_level']); ?>', '<?php echo htmlspecialchars($u['section']); ?>', <?php echo $u['is_active']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>')">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Username</small>
                                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Phone</small>
                                        <strong><?php echo $u['phone'] ? htmlspecialchars($u['phone']) : 'N/A'; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Department</small>
                                        <strong><?php echo $u['department'] ? htmlspecialchars($u['department']) : 'N/A'; ?></strong>
                                    </div>
                                </div>
                                
                                <?php if ($u['grade_level'] || $u['section']): ?>
                                <div class="row text-center mt-2">
                                    <?php if ($u['grade_level']): ?>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Grade Level</small>
                                        <strong><?php echo htmlspecialchars($u['grade_level']); ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($u['section']): ?>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Section</small>
                                        <strong><?php echo htmlspecialchars($u['section']); ?></strong>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="userForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="userAction" value="create_user">
                        <input type="hidden" name="user_id" id="userId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="passwordField">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6">
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="student">Student</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="grade_level" class="form-label">Grade Level</label>
                                <input type="text" class="form-control" id="grade_level" name="grade_level">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="section" name="section">
                            </div>
                        </div>
                        
                        <div class="mb-3" id="activeField" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active User
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="userSubmit">Add User</button>
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
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="deleteUserName"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(id, firstName, lastName, email, phone, department, gradeLevel, section, isActive) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userAction').value = 'update_user';
            document.getElementById('userId').value = id;
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
            document.getElementById('department').value = department;
            document.getElementById('grade_level').value = gradeLevel;
            document.getElementById('section').value = section;
            document.getElementById('is_active').checked = isActive == 1;
            document.getElementById('userSubmit').textContent = 'Update User';
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('activeField').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }

        function deleteUser(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reset form when modal is hidden
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('userForm').reset();
            document.getElementById('userModalTitle').textContent = 'Add User';
            document.getElementById('userAction').value = 'create_user';
            document.getElementById('userSubmit').textContent = 'Add User';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('activeField').style.display = 'none';
        });
    </script>
</body>
</html>
