<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private $conn;
    private $table_name = "events";

    public $id;
    public $title;
    public $description;
    public $event_date;
    public $start_time;
    public $end_time;
    public $venue_id;
    public $category_id;
    public $organizer_id;
    public $max_participants;
    public $registration_deadline;
    public $status;
    public $is_public;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create event
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET title=:title, description=:description, event_date=:event_date,
                      start_time=:start_time, end_time=:end_time, venue_id=:venue_id,
                      category_id=:category_id, organizer_id=:organizer_id,
                      max_participants=:max_participants, registration_deadline=:registration_deadline,
                      status=:status, is_public=:is_public, created_by=:created_by";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":event_date", $this->event_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":venue_id", $this->venue_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":organizer_id", $this->organizer_id);
        $stmt->bindParam(":max_participants", $this->max_participants);
        $stmt->bindParam(":registration_deadline", $this->registration_deadline);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":is_public", $this->is_public);
        $stmt->bindParam(":created_by", $this->created_by);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Get event by ID
    public function getEventById($id) {
        $query = "SELECT e.*, v.name as venue_name, v.location as venue_location,
                         c.name as category_name, c.color as category_color,
                         u.first_name as organizer_first_name, u.last_name as organizer_last_name
                  FROM " . $this->table_name . " e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  LEFT JOIN users u ON e.organizer_id = u.id
                  WHERE e.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->event_date = $row['event_date'];
            $this->start_time = $row['start_time'];
            $this->end_time = $row['end_time'];
            $this->venue_id = $row['venue_id'];
            $this->category_id = $row['category_id'];
            $this->organizer_id = $row['organizer_id'];
            $this->max_participants = $row['max_participants'];
            $this->registration_deadline = $row['registration_deadline'];
            $this->status = $row['status'];
            $this->is_public = $row['is_public'];
            $this->created_by = $row['created_by'];
            
            return $row;
        }
        return false;
    }

    // Get all events
    public function getAllEvents($status = null, $limit = null, $offset = 0) {
        $query = "SELECT e.*, v.name as venue_name, v.location as venue_location,
                         c.name as category_name, c.color as category_color,
                         u.first_name as organizer_first_name, u.last_name as organizer_last_name,
                         (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registered_count
                  FROM " . $this->table_name . " e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  LEFT JOIN users u ON e.organizer_id = u.id";
        
        if($status) {
            $query .= " WHERE e.status = :status";
        }
        
        $query .= " ORDER BY e.event_date DESC, e.start_time ASC";
        
        if($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get upcoming events
    public function getUpcomingEvents($limit = 10) {
        $query = "SELECT e.*, v.name as venue_name, v.location as venue_location,
                         c.name as category_name, c.color as category_color,
                         u.first_name as organizer_first_name, u.last_name as organizer_last_name,
                         (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registered_count
                  FROM " . $this->table_name . " e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  LEFT JOIN users u ON e.organizer_id = u.id
                  WHERE e.event_date >= CURDATE() AND e.status = 'published'
                  ORDER BY e.event_date ASC, e.start_time ASC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update event
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET title=:title, description=:description, event_date=:event_date,
                      start_time=:start_time, end_time=:end_time, venue_id=:venue_id,
                      category_id=:category_id, organizer_id=:organizer_id,
                      max_participants=:max_participants, registration_deadline=:registration_deadline,
                      status=:status, is_public=:is_public
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":event_date", $this->event_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":venue_id", $this->venue_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":organizer_id", $this->organizer_id);
        $stmt->bindParam(":max_participants", $this->max_participants);
        $stmt->bindParam(":registration_deadline", $this->registration_deadline);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":is_public", $this->is_public);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete event
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get events by date range
    public function getEventsByDateRange($start_date, $end_date) {
        $query = "SELECT e.*, v.name as venue_name, v.location as venue_location,
                         c.name as category_name, c.color as category_color,
                         u.first_name as organizer_first_name, u.last_name as organizer_last_name
                  FROM " . $this->table_name . " e
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  LEFT JOIN users u ON e.organizer_id = u.id
                  WHERE e.event_date BETWEEN :start_date AND :end_date
                  ORDER BY e.event_date ASC, e.start_time ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get event statistics
    public function getEventStats() {
        $query = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_events,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events,
                    SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check for scheduling conflicts
    public function checkSchedulingConflict($venue_id, $event_date, $start_time, $end_time, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE venue_id = :venue_id 
                  AND event_date = :event_date 
                  AND status != 'cancelled'
                  AND (
                      (start_time <= :start_time AND end_time > :start_time) OR
                      (start_time < :end_time AND end_time >= :end_time) OR
                      (start_time >= :start_time AND end_time <= :end_time)
                  )";
        
        if($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venue_id", $venue_id);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
        
        if($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
