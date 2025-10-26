<?php
// Role-based access control
// Usage: require_once 'includes/role_check.php'; checkRole(['admin', 'teacher']);

function checkRole($allowed_roles) {
    if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: ../dashboard/');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function canManageEvents() {
    return isAdmin() || isTeacher();
}

function canViewReports() {
    return isAdmin() || isTeacher();
}
?>
