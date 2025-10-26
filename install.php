<?php
// SEPNAS Event Management System - Installation Script
// This script will help you set up the system on InfinityFree

session_start();

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error = '';
$success = '';

// Check if already installed
if(file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

// Handle form submissions
if($_POST) {
    switch($step) {
        case 2:
            // Database connection test
            $host = $_POST['db_host'];
            $dbname = $_POST['db_name'];
            $username = $_POST['db_user'];
            $password = $_POST['db_pass'];
            
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Store database config
                $_SESSION['db_config'] = compact('host', 'dbname', 'username', 'password');
                header('Location: install.php?step=3');
                exit();
            } catch(PDOException $e) {
                $error = "Database connection failed: " . $e->getMessage();
            }
            break;
            
        case 3:
            // Create database tables
            $config = $_SESSION['db_config'];
            try {
                $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read and execute schema
                $schema = file_get_contents('database/schema.sql');
                $statements = explode(';', $schema);
                
                foreach($statements as $statement) {
                    $statement = trim($statement);
                    if(!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                header('Location: install.php?step=4');
                exit();
            } catch(PDOException $e) {
                $error = "Failed to create tables: " . $e->getMessage();
            }
            break;
            
        case 4:
            // Create admin user
            $config = $_SESSION['db_config'];
            $admin_username = $_POST['admin_username'];
            $admin_email = $_POST['admin_email'];
            $admin_password = $_POST['admin_password'];
            $admin_first_name = $_POST['admin_first_name'];
            $admin_last_name = $_POST['admin_last_name'];
            
            try {
                $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
                $stmt->execute([$admin_username, $admin_email, $hashed_password, $admin_first_name, $admin_last_name]);
                
                // Update config files
                updateConfigFiles($config);
                
                // Create installed lock file
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                header('Location: install.php?step=5');
                exit();
            } catch(PDOException $e) {
                $error = "Failed to create admin user: " . $e->getMessage();
            }
            break;
    }
}

function updateConfigFiles($config) {
    // Update database.php
    $db_content = "<?php
// Database configuration for InfinityFree hosting
class Database {
    private \$host = '{$config['host']}';
    private \$db_name = '{$config['dbname']}';
    private \$username = '{$config['username']}';
    private \$password = '{$config['password']}';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$exception) {
            echo \"Connection error: \" . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}
?>";
    
    file_put_contents('config/database.php', $db_content);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEPNAS Event Management - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="install-card">
                    <div class="install-header">
                        <h3><i class="fas fa-graduation-cap me-2"></i>SEPNAS Event Management</h3>
                        <p class="mb-0">System Installation</p>
                    </div>
                    <div class="install-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'inactive'; ?>">1</div>
                            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'inactive'; ?>">2</div>
                            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'inactive'; ?>">3</div>
                            <div class="step <?php echo $step >= 4 ? ($step > 4 ? 'completed' : 'active') : 'inactive'; ?>">4</div>
                            <div class="step <?php echo $step >= 5 ? 'active' : 'inactive'; ?>">5</div>
                        </div>

                        <?php if($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php switch($step): case 1: ?>
                            <h5>Welcome to SEPNAS Event Management System</h5>
                            <p>This installation wizard will help you set up the system on InfinityFree hosting.</p>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Prerequisites</h6>
                                <ul class="mb-0">
                                    <li>PHP 7.4 or higher</li>
                                    <li>MySQL database on InfinityFree</li>
                                    <li>OneSignal account (optional, for notifications)</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <a href="install.php?step=2" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Start Installation
                                </a>
                            </div>
                        <?php break; case 2: ?>
                            <h5>Database Configuration</h5>
                            <p>Enter your InfinityFree MySQL database credentials.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" 
                                           value="sql201.infinityfree.com" required>
                                    <div class="form-text">Usually sql201.infinityfree.com for InfinityFree</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" required>
                                    <div class="form-text">Your database name from InfinityFree control panel</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-database me-2"></i>Test Connection
                                    </button>
                                </div>
                            </form>
                        <?php break; case 3: ?>
                            <h5>Database Setup</h5>
                            <p>Creating database tables and initial data...</p>
                            
                            <div class="d-grid">
                                <a href="install.php?step=4" class="btn btn-primary">
                                    <i class="fas fa-table me-2"></i>Create Tables
                                </a>
                            </div>
                        <?php break; case 4: ?>
                            <h5>Admin Account Setup</h5>
                            <p>Create your administrator account.</p>
                            
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="admin_first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="admin_first_name" name="admin_first_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="admin_last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="admin_last_name" name="admin_last_name" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-shield me-2"></i>Create Admin Account
                                    </button>
                                </div>
                            </form>
                        <?php break; case 5: ?>
                            <h5>Installation Complete!</h5>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                SEPNAS Event Management System has been successfully installed!
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Next Steps</h6>
                                <ul class="mb-0">
                                    <li>Configure OneSignal for notifications (optional)</li>
                                    <li>Set up event categories and venues</li>
                                    <li>Create user accounts for teachers and students</li>
                                    <li>Test the system functionality</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <a href="auth/login.php" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to System
                                </a>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-trash me-1"></i>
                                    Remember to delete this install.php file for security
                                </small>
                            </div>
                        <?php break; endswitch; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
