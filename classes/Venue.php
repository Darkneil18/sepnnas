<?php
require_once __DIR__ . '/../config/database.php';

class Venue {
    private $conn;
    private $table_name = "venues";

    public $id;
    public $name;
    public $location;
    public $capacity;
    public $facilities;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create venue
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, location=:location, capacity=:capacity, facilities=:facilities";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":facilities", $this->facilities);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Get all venues
    public function getAllVenues() {
        $query = "SELECT v.*, COUNT(e.id) as event_count
                  FROM " . $this->table_name . " v
                  LEFT JOIN events e ON v.id = e.venue_id
                  GROUP BY v.id
                  ORDER BY v.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get venue by ID
    public function getVenueById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->location = $row['location'];
            $this->capacity = $row['capacity'];
            $this->facilities = $row['facilities'];
            return $row;
        }
        return false;
    }

    // Update venue
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, location=:location, capacity=:capacity, facilities=:facilities
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":facilities", $this->facilities);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete venue
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get available venues for a specific date and time
    public function getAvailableVenues($event_date, $start_time, $end_time, $exclude_event_id = null) {
        $query = "SELECT v.* FROM " . $this->table_name . " v
                  WHERE v.id NOT IN (
                      SELECT venue_id FROM events 
                      WHERE event_date = :event_date 
                      AND status != 'cancelled'
                      AND (
                          (start_time <= :start_time AND end_time > :start_time) OR
                          (start_time < :end_time AND end_time >= :end_time) OR
                          (start_time >= :start_time AND end_time <= :end_time)
                      )";
        
        if($exclude_event_id) {
            $query .= " AND id != :exclude_event_id";
        }
        
        $query .= ") ORDER BY v.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
        
        if($exclude_event_id) {
            $stmt->bindParam(":exclude_event_id", $exclude_event_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
