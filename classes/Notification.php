<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $title;
    public $message;
    public $type;
    public $target_audience;
    public $target_users;
    public $event_id;
    public $is_sent;
    public $scheduled_at;
    public $sent_at;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET title=:title, message=:message, type=:type, target_audience=:target_audience,
                      target_users=:target_users, event_id=:event_id, scheduled_at=:scheduled_at, created_by=:created_by";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":target_audience", $this->target_audience);
        $stmt->bindParam(":target_users", $this->target_users);
        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":scheduled_at", $this->scheduled_at);
        $stmt->bindParam(":created_by", $this->created_by);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Send notification via OneSignal
    public function sendOneSignalNotification($title, $message, $target_audience = 'all', $target_users = null, $event_id = null) {
        $app_id = ONESIGNAL_APP_ID;
        $rest_api_key = ONESIGNAL_REST_API_KEY;
        
        $url = "https://onesignal.com/api/v1/notifications";
        
        $fields = [
            'app_id' => $app_id,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'included_segments' => ['All'],
            'data' => [
                'type' => 'event_notification',
                'event_id' => $event_id
            ]
        ];

        // Customize target audience
        if($target_audience === 'specific' && $target_users) {
            $user_ids = json_decode($target_users, true);
            if(is_array($user_ids) && !empty($user_ids)) {
                $fields['include_external_user_ids'] = $user_ids;
                unset($fields['included_segments']);
            }
        }

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $rest_api_key
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($http_code === 200) {
            $response_data = json_decode($response, true);
            return $response_data;
        }
        
        return false;
    }

    // Send event reminder
    public function sendEventReminder($event_id) {
        $query = "SELECT e.*, v.name as venue_name, c.name as category_name
                  FROM events e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  WHERE e.id = :event_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $title = "Event Reminder: " . $event['title'];
            $message = "Don't forget! " . $event['title'] . " is tomorrow at " . 
                      date('g:i A', strtotime($event['start_time'])) . " in " . $event['venue_name'];
            
            return $this->sendOneSignalNotification($title, $message, 'all', null, $event_id);
        }
        
        return false;
    }

    // Send event update notification
    public function sendEventUpdate($event_id, $update_type = 'general') {
        $query = "SELECT e.*, v.name as venue_name, c.name as category_name
                  FROM events e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  WHERE e.id = :event_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $title = "Event Update: " . $event['title'];
            $message = "There has been an update to " . $event['title'] . ". Please check the event details for more information.";
            
            return $this->sendOneSignalNotification($title, $message, 'all', null, $event_id);
        }
        
        return false;
    }

    // Get all notifications
    public function getAllNotifications($limit = null, $offset = 0) {
        $query = "SELECT n.*, e.title as event_title, u.first_name as created_by_first_name, u.last_name as created_by_last_name
                  FROM " . $this->table_name . " n
                  LEFT JOIN events e ON n.event_id = e.id
                  LEFT JOIN users u ON n.created_by = u.id
                  ORDER BY n.created_at DESC";
        
        if($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mark notification as sent
    public function markAsSent($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_sent = 1, sent_at = NOW() 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Process scheduled notifications
    public function processScheduledNotifications() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_sent = 0 AND scheduled_at <= NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sent_count = 0;
        foreach($notifications as $notification) {
            $result = $this->sendOneSignalNotification(
                $notification['title'],
                $notification['message'],
                $notification['target_audience'],
                $notification['target_users'],
                $notification['event_id']
            );

            if($result) {
                $this->markAsSent($notification['id']);
                $sent_count++;
            }
        }

        return $sent_count;
    }

    // Get notification statistics
    public function getNotificationStats() {
        $query = "SELECT 
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent_notifications,
                    SUM(CASE WHEN is_sent = 0 THEN 1 ELSE 0 END) as pending_notifications,
                    SUM(CASE WHEN type = 'event_reminder' THEN 1 ELSE 0 END) as reminder_notifications,
                    SUM(CASE WHEN type = 'event_update' THEN 1 ELSE 0 END) as update_notifications
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create automated event reminder
    public function createEventReminder($event_id, $reminder_hours = 24) {
        $query = "SELECT * FROM events WHERE id = :event_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reminder_time = date('Y-m-d H:i:s', strtotime($event['event_date'] . ' ' . $event['start_time']) - ($reminder_hours * 3600));
            
            $this->title = "Event Reminder: " . $event['title'];
            $this->message = "Don't forget! " . $event['title'] . " is coming up. Check the details and make sure you're prepared.";
            $this->type = 'event_reminder';
            $this->target_audience = 'all';
            $this->target_users = null;
            $this->event_id = $event_id;
            $this->scheduled_at = $reminder_time;
            $this->created_by = $_SESSION['user_id'];
            
            return $this->create();
        }
        
        return false;
    }
}
?>
