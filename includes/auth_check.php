<?php
// Always start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Define app URL (update with your exact domain if not already defined)
if (!defined('APP_URL')) {
    define('APP_URL', 'https://sepnaseventmanagementsystem.free.nf');
}

// ✅ Prevent redirect loop
$currentFile = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $currentFile !== 'login.php') {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit();
}

// ✅ Only load user if logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/User.php';

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    if (!$user->getUserById($_SESSION['user_id'])) {
        session_destroy();
        header('Location: ' . APP_URL . '/auth/login.php');
        exit();
    }

    // Store user info in session
    $_SESSION['user_info'] = [
        'id' => $user->id,
        'username' => $user->username,
        'email' => $user->email,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'role' => $user->role,
        'phone' => $user->phone,
        'department' => $user->department,
        'grade_level' => $user->grade_level,
        'section' => $user->section
    ];

    // Compatibility variables
    $_SESSION['username'] = $user->username;
    $_SESSION['email'] = $user->email;
    $_SESSION['first_name'] = $user->first_name;
    $_SESSION['last_name'] = $user->last_name;
    $_SESSION['role'] = $user->role;
    $_SESSION['phone'] = $user->phone;
    $_SESSION['department'] = $user->department;
    $_SESSION['grade_level'] = $user->grade_level;
    $_SESSION['section'] = $user->section;
}
?>
