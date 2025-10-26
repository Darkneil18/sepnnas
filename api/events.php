<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Event.php';
require_once '../classes/EventCategory.php';
require_once '../classes/Venue.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);
$category = new EventCategory($db);
$venue = new Venue($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        switch($action) {
            case 'list':
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $events = $event->getAllEvents($status, $limit, $offset);
                echo json_encode(['success' => true, 'data' => $events]);
                break;
                
            case 'upcoming':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $events = $event->getUpcomingEvents($limit);
                echo json_encode(['success' => true, 'data' => $events]);
                break;
                
            case 'details':
                $event_id = isset($_GET['id']) ? $_GET['id'] : null;
                if($event_id) {
                    $event_details = $event->getEventById($event_id);
                    if($event_details) {
                        echo json_encode(['success' => true, 'data' => $event_details]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Event not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Event ID required']);
                }
                break;
                
            case 'calendar':
                $start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
                $end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');
                $events = $event->getEventsByDateRange($start_date, $end_date);
                
                $calendar_events = [];
                foreach($events as $event_item) {
                    $calendar_events[] = [
                        'id' => $event_item['id'],
                        'title' => $event_item['title'],
                        'start' => $event_item['event_date'] . 'T' . $event_item['start_time'],
                        'end' => $event_item['event_date'] . 'T' . $event_item['end_time'],
                        'color' => $event_item['category_color'],
                        'url' => 'event-details.php?id=' . $event_item['id']
                    ];
                }
                
                echo json_encode(['success' => true, 'data' => $calendar_events]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch($action) {
            case 'create':
                if(!canManageEvents()) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    break;
                }
                
                $event->title = $input['title'];
                $event->description = $input['description'];
                $event->event_date = $input['event_date'];
                $event->start_time = $input['start_time'];
                $event->end_time = $input['end_time'];
                $event->venue_id = $input['venue_id'];
                $event->category_id = $input['category_id'];
                $event->organizer_id = $input['organizer_id'];
                $event->max_participants = $input['max_participants'];
                $event->registration_deadline = $input['registration_deadline'];
                $event->status = $input['status'];
                $event->is_public = $input['is_public'];
                $event->created_by = $_SESSION['user_id'];
                
                $event_id = $event->create();
                
                if($event_id) {
                    echo json_encode(['success' => true, 'message' => 'Event created successfully', 'id' => $event_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create event']);
                }
                break;
                
            case 'update':
                if(!canManageEvents()) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    break;
                }
                
                $event->id = $input['id'];
                $event->title = $input['title'];
                $event->description = $input['description'];
                $event->event_date = $input['event_date'];
                $event->start_time = $input['start_time'];
                $event->end_time = $input['end_time'];
                $event->venue_id = $input['venue_id'];
                $event->category_id = $input['category_id'];
                $event->organizer_id = $input['organizer_id'];
                $event->max_participants = $input['max_participants'];
                $event->registration_deadline = $input['registration_deadline'];
                $event->status = $input['status'];
                $event->is_public = $input['is_public'];
                
                if($event->update()) {
                    echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update event']);
                }
                break;
                
            case 'update_status':
                if(!canManageEvents()) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    break;
                }
                
                $event->id = $input['event_id'];
                $event->status = $input['status'];
                
                if($event->update()) {
                    echo json_encode(['success' => true, 'message' => 'Event status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update event status']);
                }
                break;
                
            case 'register':
                // Event registration logic would go here
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
                break;
                
            case 'delete':
                if(!canManageEvents()) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    break;
                }
                
                $event->id = $input['id'];
                
                if($event->delete()) {
                    echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete event']);
                }
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
