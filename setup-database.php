<?php
// Simple database setup script for SEPNAS Event Management System
require_once 'config/database.php';

echo "<h2>SEPNAS Event Management - Database Setup</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if(!$db) {
        throw new Exception("Database connection failed!");
    }
    
    echo "<div style='color: green;'>✅ Database connection successful!</div>";
    
    // Read and execute the schema
    $schema = file_get_contents('database/schema.sql');
    
    if(!$schema) {
        throw new Exception("Could not read schema.sql file!");
    }
    
    echo "<div style='color: blue;'>📄 Schema file loaded successfully</div>";
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $success_count = 0;
    $error_count = 0;
    
    echo "<h3>Executing SQL Statements:</h3>";
    echo "<ul>";
    
    foreach($statements as $statement) {
        if(empty($statement)) continue;
        
        try {
            $db->exec($statement);
            $success_count++;
            echo "<li style='color: green;'>✅ Executed successfully</li>";
        } catch(PDOException $e) {
            $error_count++;
            echo "<li style='color: red;'>❌ Error: " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Summary:</strong><br>";
    echo "✅ Successful: $success_count<br>";
    echo "❌ Errors: $error_count<br>";
    echo "</div>";
    
    if($error_count == 0) {
        echo "<div style='color: green; font-weight: bold;'>🎉 Database setup completed successfully!</div>";
        echo "<p><a href='auth/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    } else {
        echo "<div style='color: orange; font-weight: bold;'>⚠️ Database setup completed with some errors. Please check the errors above.</div>";
    }
    
} catch(Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}

echo "<hr>";
echo "<p><a href='test-connection.php'>Test Connection</a> | <a href='install.php'>Installation Wizard</a></p>";
?>
