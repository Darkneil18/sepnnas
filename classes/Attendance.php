<?php
require_once __DIR__ . '/../config/database.php';

class Attendance {
    private $conn;
    private $table_name = "attendance";

    public $id;
    public $event_id;
    public $user_id;
    public $check_in_time;
    public $check_out_time;
    public $status;
    public $notes;
    public $recorded_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Record attendance
    public function recordAttendance() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET event_id=:event_id, user_id=:user_id, check_in_time=:check_in_time,
                      check_out_time=:check_out_time, status=:status, notes=:notes, recorded_by=:recorded_by";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":check_in_time", $this->check_in_time);
        $stmt->bindParam(":check_out_time", $this->check_out_time);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":recorded_by", $this->recorded_by);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Update attendance
    public function updateAttendance() {
        $query = "UPDATE " . $this->table_name . " 
                  SET check_in_time=:check_in_time, check_out_time=:check_out_time,
                      status=:status, notes=:notes, recorded_by=:recorded_by
                  WHERE event_id=:event_id AND user_id=:user_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":check_in_time", $this->check_in_time);
        $stmt->bindParam(":check_out_time", $this->check_out_time);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":recorded_by", $this->recorded_by);
        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get attendance for an event
    public function getEventAttendance($event_id) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.role, u.department, u.grade_level, u.section,
                         er.registration_date, er.status as registration_status
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN event_registrations er ON a.event_id = er.event_id AND a.user_id = er.user_id
                  WHERE a.event_id = :event_id
                  ORDER BY a.recorded_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get attendance for a user
    public function getUserAttendance($user_id, $limit = null) {
        $query = "SELECT a.*, e.title as event_title, e.event_date, e.start_time, e.end_time,
                         v.name as venue_name, c.name as category_name
                  FROM " . $this->table_name . " a
                  LEFT JOIN events e ON a.event_id = e.id
                  LEFT JOIN venues v ON e.venue_id = v.id
                  LEFT JOIN event_categories c ON e.category_id = c.id
                  WHERE a.user_id = :user_id
                  ORDER BY e.event_date DESC";
        
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

    // Get attendance statistics for an event
    public function getEventAttendanceStats($event_id) {
        $query = "SELECT 
                    COUNT(*) as total_attendance,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    AVG(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100 as attendance_rate
                  FROM " . $this->table_name . " 
                  WHERE event_id = :event_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user attendance statistics
    public function getUserAttendanceStats($user_id) {
        $query = "SELECT 
                    COUNT(*) as total_events_attended,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    AVG(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100 as attendance_rate
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Bulk update attendance
    public function bulkUpdateAttendance($event_id, $attendance_data) {
        $this->conn->beginTransaction();
        
        try {
            foreach($attendance_data as $data) {
                $this->event_id = $event_id;
                $this->user_id = $data['user_id'];
                $this->status = $data['status'];
                $this->notes = isset($data['notes']) ? $data['notes'] : null;
                $this->recorded_by = $_SESSION['user_id'];
                
                // Check if attendance record exists
                $check_query = "SELECT id FROM " . $this->table_name . " 
                               WHERE event_id = :event_id AND user_id = :user_id";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bindParam(":event_id", $this->event_id);
                $check_stmt->bindParam(":user_id", $this->user_id);
                $check_stmt->execute();
                
                if($check_stmt->rowCount() > 0) {
                    // Update existing record
                    $this->updateAttendance();
                } else {
                    // Create new record
                    $this->recordAttendance();
                }
            }
            
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Get attendance summary by date range
    public function getAttendanceSummary($start_date, $end_date) {
        $query = "SELECT 
                    DATE(a.recorded_at) as attendance_date,
                    COUNT(*) as total_attendance,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                  FROM " . $this->table_name . " a
                  LEFT JOIN events e ON a.event_id = e.id
                  WHERE e.event_date BETWEEN :start_date AND :end_date
                  GROUP BY DATE(a.recorded_at)
                  ORDER BY attendance_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check if user is already marked for attendance
    public function isUserMarked($event_id, $user_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE event_id = :event_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Get attendance by status
    public function getAttendanceByStatus($event_id, $status) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.role, u.department
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.user_id = u.id
                  WHERE a.event_id = :event_id AND a.status = :status
                  ORDER BY a.recorded_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":status", $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
