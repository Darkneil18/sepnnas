<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/role_check.php';
require_once '../config/database.php';
require_once '../classes/EventCategory.php';

// Check if user can manage events
if (!canManageEvents()) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$category = new EventCategory($db);

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $category->name = $_POST['name'];
                $category->description = $_POST['description'];
                $category->color = $_POST['color'];
                
                if ($category->create()) {
                    $message = 'Category created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating category.';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $category->id = $_POST['category_id'];
                $category->name = $_POST['name'];
                $category->description = $_POST['description'];
                $category->color = $_POST['color'];
                
                if ($category->update()) {
                    $message = 'Category updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating category.';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $category->id = $_POST['category_id'];
                
                if ($category->delete()) {
                    $message = 'Category deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting category.';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all categories
$categories = $category->getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - SEPNAS Event Management</title>
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
        .category-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .category-card:hover {
            transform: translateY(-2px);
        }
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
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
                        <a class="nav-link active" href="categories.php">
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
                        <h2><i class="fas fa-tags me-2"></i>Manage Categories</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Categories Grid -->
                    <div class="row">
                        <?php if (empty($categories)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No categories found</h5>
                                <p class="text-muted">Create your first category to get started.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card category-card h-100" style="border-left-color: <?php echo $cat['color']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">
                                            <span class="color-preview" style="background-color: <?php echo $cat['color']; ?>"></span>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['description']); ?>', '<?php echo $cat['color']; ?>')">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($cat['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $cat['event_count']; ?> events
                                        </small>
                                        <span class="badge" style="background-color: <?php echo $cat['color']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
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

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="categoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="categoryAction" value="create">
                        <input type="hidden" name="category_id" id="categoryId">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="color" class="form-label">Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff">
                                <input type="text" class="form-control" id="colorText" placeholder="#007bff">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="categorySubmit">Add Category</button>
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
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sync color picker and text input
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('colorText').value = this.value;
        });
        
        document.getElementById('colorText').addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                document.getElementById('color').value = this.value;
            }
        });

        function editCategory(id, name, description, color) {
            document.getElementById('categoryModalTitle').textContent = 'Edit Category';
            document.getElementById('categoryAction').value = 'update';
            document.getElementById('categoryId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('description').value = description;
            document.getElementById('color').value = color;
            document.getElementById('colorText').value = color;
            document.getElementById('categorySubmit').textContent = 'Update Category';
            
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        }

        function deleteCategory(id, name) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reset form when modal is hidden
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModalTitle').textContent = 'Add Category';
            document.getElementById('categoryAction').value = 'create';
            document.getElementById('categorySubmit').textContent = 'Add Category';
        });
    </script>
</body>
</html>
