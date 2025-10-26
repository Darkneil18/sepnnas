<?php
require_once __DIR__ . '/../config/database.php';

class Feedback {
    private $conn;
    private $table_name = "feedback";

    public $id;
    public $event_id;
    public $user_id;
    public $rating;
    public $comments;
    public $suggestions;
    public $submitted_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Submit feedback
    public function submitFeedback() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET event_id=:event_id, user_id=:user_id, rating=:rating,
                      comments=:comments, suggestions=:suggestions";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comments", $this->comments);
        $stmt->bindParam(":suggestions", $this->suggestions);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Get feedback for an event
    public function getEventFeedback($event_id) {
        $query = "SELECT f.*, u.first_name, u.last_name, u.role, u.department,
                         e.title as event_title, e.event_date
                  FROM " . $this->table_name . " f
                  LEFT JOIN users u ON f.user_id = u.id
                  LEFT JOIN events e ON f.event_id = e.id
                  WHERE f.event_id = :event_id
                  ORDER BY f.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get feedback statistics for an event
    public function getEventFeedbackStats($event_id) {
        $query = "SELECT 
                    COUNT(*) as total_feedback,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as excellent_count,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as good_count,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as average_count,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as poor_count,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as terrible_count
                  FROM " . $this->table_name . " 
                  WHERE event_id = :event_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all feedback with filters
    public function getAllFeedback($event_id = null, $rating = null, $limit = null, $offset = 0) {
        $query = "SELECT f.*, u.first_name, u.last_name, u.role, u.department,
                         e.title as event_title, e.event_date, e.start_time
                  FROM " . $this->table_name . " f
                  LEFT JOIN users u ON f.user_id = u.id
                  LEFT JOIN events e ON f.event_id = e.id
                  WHERE 1=1";
        
        $params = [];
        
        if($event_id) {
            $query .= " AND f.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }
        
        if($rating) {
            $query .= " AND f.rating = :rating";
            $params[':rating'] = $rating;
        }
        
        $query .= " ORDER BY f.submitted_at DESC";
        
        if($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get feedback summary by date range
    public function getFeedbackSummary($start_date, $end_date) {
        $query = "SELECT 
                    DATE(f.submitted_at) as feedback_date,
                    COUNT(*) as total_feedback,
                    AVG(f.rating) as average_rating,
                    e.title as event_title
                  FROM " . $this->table_name . " f
                  LEFT JOIN events e ON f.event_id = e.id
                  WHERE f.submitted_at BETWEEN :start_date AND :end_date
                  GROUP BY DATE(f.submitted_at), f.event_id
                  ORDER BY feedback_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get feedback by user
    public function getUserFeedback($user_id, $limit = null) {
        $query = "SELECT f.*, e.title as event_title, e.event_date, e.start_time
                  FROM " . $this->table_name . " f
                  LEFT JOIN events e ON f.event_id = e.id
                  WHERE f.user_id = :user_id
                  ORDER BY f.submitted_at DESC";
        
        if($limit) {
            $query .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if user has already submitted feedback for an event
    public function hasUserSubmittedFeedback($event_id, $user_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE event_id = :event_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Get overall feedback statistics
    public function getOverallFeedbackStats() {
        $query = "SELECT 
                    COUNT(*) as total_feedback,
                    AVG(rating) as average_rating,
                    COUNT(DISTINCT event_id) as events_with_feedback,
                    COUNT(DISTINCT user_id) as users_who_feedback,
                    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
                    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_feedback
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get feedback by rating
    public function getFeedbackByRating($rating) {
        $query = "SELECT f.*, u.first_name, u.last_name, u.role,
                         e.title as event_title, e.event_date
                  FROM " . $this->table_name . " f
                  LEFT JOIN users u ON f.user_id = u.id
                  LEFT JOIN events e ON f.event_id = e.id
                  WHERE f.rating = :rating
                  ORDER BY f.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":rating", $rating);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get recent feedback
    public function getRecentFeedback($limit = 10) {
        $query = "SELECT f.*, u.first_name, u.last_name, u.role,
                         e.title as event_title, e.event_date
                  FROM " . $this->table_name . " f
                  LEFT JOIN users u ON f.user_id = u.id
                  LEFT JOIN events e ON f.event_id = e.id
                  ORDER BY f.submitted_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
