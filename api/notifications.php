<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Notification.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        switch($action) {
            case 'list':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                $notifications = $notification->getAllNotifications($limit, $offset);
                echo json_encode(['success' => true, 'data' => $notifications]);
                break;
                
            case 'stats':
                $stats = $notification->getNotificationStats();
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'send':
                if(!isset($input['title']) || !isset($input['message'])) {
                    echo json_encode(['success' => false, 'message' => 'Title and message are required']);
                    break;
                }
                
                $notification->title = $input['title'];
                $notification->message = $input['message'];
                $notification->type = isset($input['type']) ? $input['type'] : 'general';
                $notification->target_audience = isset($input['target_audience']) ? $input['target_audience'] : 'all';
                $notification->target_users = isset($input['target_users']) ? json_encode($input['target_users']) : null;
                $notification->event_id = isset($input['event_id']) ? $input['event_id'] : null;
                $notification->scheduled_at = isset($input['scheduled_at']) ? $input['scheduled_at'] : null;
                $notification->created_by = $_SESSION['user_id'];
                
                $notification_id = $notification->create();
                
                if($notification_id) {
                    // Send immediately if not scheduled
                    if(!$notification->scheduled_at) {
                        $result = $notification->sendOneSignalNotification(
                            $notification->title,
                            $notification->message,
                            $notification->target_audience,
                            $notification->target_users,
                            $notification->event_id
                        );
                        
                        if($result) {
                            $notification->markAsSent($notification_id);
                        }
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Notification created successfully', 'id' => $notification_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
                }
                break;
                
            case 'send_reminder':
                if(!isset($input['event_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                    break;
                }
                
                $result = $notification->sendEventReminder($input['event_id']);
                
                if($result) {
                    echo json_encode(['success' => true, 'message' => 'Event reminder sent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send event reminder']);
                }
                break;
                
            case 'send_update':
                if(!isset($input['event_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                    break;
                }
                
                $result = $notification->sendEventUpdate($input['event_id']);
                
                if($result) {
                    echo json_encode(['success' => true, 'message' => 'Event update sent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send event update']);
                }
                break;
                
            case 'process_scheduled':
                $sent_count = $notification->processScheduledNotifications();
                echo json_encode(['success' => true, 'message' => "Processed {$sent_count} scheduled notifications"]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
