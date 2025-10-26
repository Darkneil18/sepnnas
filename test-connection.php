<?php
// Test database connection
require_once 'config/database.php';

echo "<h2>SEPNAS Event Management - Database Connection Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "<div style='color: green; font-weight: bold;'>✅ Database connection successful!</div>";
        
        // Test a simple query
        $stmt = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='color: blue;'>📊 Found " . $result['table_count'] . " tables in the database</div>";
        
        // Check if our tables exist
        $tables = ['users', 'events', 'event_categories', 'venues', 'attendance', 'feedback', 'notifications'];
        echo "<h3>Table Status:</h3>";
        echo "<ul>";
        
        foreach($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if($stmt->rowCount() > 0) {
                echo "<li style='color: green;'>✅ $table - exists</li>";
            } else {
                echo "<li style='color: red;'>❌ $table - missing</li>";
            }
        }
        echo "</ul>";
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Database connection failed!</div>";
    }
    
} catch(Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='install.php'>Run Installation Wizard</a> | <a href='auth/login.php'>Go to Login</a></p>";
?>
