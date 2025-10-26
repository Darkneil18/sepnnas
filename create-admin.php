<?php
// Quick admin user creation script
require_once 'config/database.php';

echo "<h2>SEPNAS Event Management - Create Admin User</h2>";

if($_POST) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if(!$db) {
            throw new Exception("Database connection failed!");
        }
        
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        
        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0) {
            echo "<div style='color: red;'>❌ User with this username or email already exists!</div>";
        } else {
            // Create admin user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
            $result = $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name]);
            
            if($result) {
                echo "<div style='color: green; font-weight: bold;'>✅ Admin user created successfully!</div>";
                echo "<p><strong>Login Details:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Username:</strong> $username</li>";
                echo "<li><strong>Email:</strong> $email</li>";
                echo "<li><strong>Password:</strong> [as entered]</li>";
                echo "</ul>";
                echo "<p><a href='auth/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
            } else {
                echo "<div style='color: red;'>❌ Failed to create admin user!</div>";
            }
        }
        
    } catch(Exception $e) {
        echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
    }
} else {
    ?>
    <form method="POST" style="max-width: 500px; margin: 20px 0;">
        <div style="margin-bottom: 15px;">
            <label for="username" style="display: block; margin-bottom: 5px; font-weight: bold;">Username:</label>
            <input type="text" id="username" name="username" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
            <input type="email" id="email" name="email" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Password:</label>
            <input type="password" id="password" name="password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="first_name" style="display: block; margin-bottom: 5px; font-weight: bold;">First Name:</label>
            <input type="text" id="first_name" name="first_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="last_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Create Admin User</button>
    </form>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>Note:</strong> This script creates an admin user with full system access. Make sure to delete this file after creating your admin user for security.
    </div>
    <?php
}

echo "<hr>";
echo "<p><a href='test-connection.php'>Test Connection</a> | <a href='setup-database.php'>Setup Database</a> | <a href='auth/login.php'>Login</a></p>";
?>
