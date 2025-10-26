<?php
// Minimal test to identify the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing SEPNAS System</h1>";

echo "<p>1. Testing basic PHP...</p>";
echo "<p>✅ PHP is working</p>";

echo "<p>2. Testing session...</p>";
session_start();
echo "<p>✅ Session started</p>";

echo "<p>3. Testing config...</p>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p>✅ Config loaded</p>";
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Config error: " . $e->getMessage() . "</p>";
}

echo "<p>4. Testing database...</p>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if($db) {
        echo "<p>✅ Database connected</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<p>5. Testing classes...</p>";
try {
    require_once __DIR__ . '/classes/User.php';
    require_once __DIR__ . '/classes/Event.php';
    require_once __DIR__ . '/classes/EventCategory.php';
    require_once __DIR__ . '/classes/Venue.php';
    echo "<p>✅ Classes loaded</p>";
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Class error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard/simple-manage-events.php'>Test Simple Manage Events</a></p>";
echo "<p><a href='debug-manage-events.php'>Debug Manage Events</a></p>";
?>
