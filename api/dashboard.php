<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/User.php';
require_once '../classes/Attendance.php';
require_once '../classes/Feedback.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$user = new User($db);
$attendance = new Attendance($db);
$feedback = new Feedback($db);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'stats':
        $event_stats = $event->getEventStats();
        $user_stats = $user->getUserStats();
        $overall_feedback_stats = $feedback->getOverallFeedbackStats();
        
        $stats = [
            'total_events' => $event_stats['total_events'],
            'published_events' => $event_stats['published_events'],
            'upcoming_events' => $event_stats['upcoming_events'],
            'completed_events' => $event_stats['completed_events'],
            'total_users' => $user_stats['total_users'],
            'active_users' => $user_stats['active_users'],
            'total_feedback' => $overall_feedback_stats['total_feedback'],
            'average_rating' => $overall_feedback_stats['average_rating']
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'recent_events':
        $recent_events = $event->getUpcomingEvents(5);
        echo json_encode(['success' => true, 'data' => $recent_events]);
        break;
        
    case 'notifications':
        $notification = new Notification($db);
        $notifications = $notification->getAllNotifications(10);
        echo json_encode(['success' => true, 'data' => $notifications]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
