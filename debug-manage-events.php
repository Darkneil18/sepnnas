<?php
// Debug version of manage-events.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Manage Events</h2>";

try {
    echo "<p>1. Loading config...</p>";
    require_once __DIR__ . '/config/config.php';
    echo "<p>✅ Config loaded</p>";
    
    echo "<p>2. Loading auth_check...</p>";
    require_once __DIR__ . '/includes/auth_check.php';
    echo "<p>✅ Auth check loaded</p>";
    
    echo "<p>3. Loading role_check...</p>";
    require_once __DIR__ . '/includes/role_check.php';
    echo "<p>✅ Role check loaded</p>";
    
    echo "<p>4. Loading database...</p>";
    require_once __DIR__ . '/config/database.php';
    echo "<p>✅ Database loaded</p>";
    
    echo "<p>5. Loading classes...</p>";
    require_once __DIR__ . '/classes/Event.php';
    require_once __DIR__ . '/classes/EventCategory.php';
    require_once __DIR__ . '/classes/Venue.php';
    require_once __DIR__ . '/classes/User.php';
    echo "<p>✅ Classes loaded</p>";
    
    echo "<p>6. Checking role...</p>";
    checkRole(['admin', 'teacher']);
    echo "<p>✅ Role check passed</p>";
    
    echo "<p>7. Creating database connection...</p>";
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>✅ Database connection created</p>";
    
    echo "<p>8. Creating class instances...</p>";
    $event = new Event($db);
    $category = new EventCategory($db);
    $venue = new Venue($db);
    $user = new User($db);
    echo "<p>✅ Class instances created</p>";
    
    echo "<p>9. Getting data...</p>";
    $categories = $category->getAllCategories();
    echo "<p>✅ Categories: " . count($categories) . " found</p>";
    
    $venues = $venue->getAllVenues();
    echo "<p>✅ Venues: " . count($venues) . " found</p>";
    
    $organizers = $user->getAllUsers(['teacher', 'admin']);
    echo "<p>✅ Organizers: " . count($organizers) . " found</p>";
    
    echo "<p>10. All checks passed! The issue might be in the HTML rendering.</p>";
    
    echo "<h3>Session Data:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h3>Categories Data:</h3>";
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='manage-events.php'>Try Original Page</a> | <a href='index.php'>Dashboard</a></p>";
?>
