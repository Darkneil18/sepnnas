<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role;
    public $phone;
    public $department;
    public $grade_level;
    public $section;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password, 
                      first_name=:first_name, last_name=:last_name, role=:role,
                      phone=:phone, department=:department, grade_level=:grade_level, 
                      section=:section";

        $stmt = $this->conn->prepare($query);

        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":grade_level", $this->grade_level);
        $stmt->bindParam(":section", $this->section);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login($username, $password) {
        $query = "SELECT id, username, email, password, first_name, last_name, role, 
                         phone, department, grade_level, section, is_active
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->role = $row['role'];
                $this->phone = $row['phone'];
                $this->department = $row['department'];
                $this->grade_level = $row['grade_level'];
                $this->section = $row['section'];
                $this->is_active = $row['is_active'];
                
                return true;
            }
        }
        return false;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT id, username, email, first_name, last_name, role, 
                         phone, department, grade_level, section, is_active, created_at
                  FROM " . $this->table_name . " 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];
            $this->phone = $row['phone'];
            $this->department = $row['department'];
            $this->grade_level = $row['grade_level'];
            $this->section = $row['section'];
            $this->is_active = $row['is_active'];
            
            return true;
        }
        return false;
    }

    // Get all users
    public function getAllUsers($role = null) {
        $query = "SELECT id, username, email, first_name, last_name, role, 
                         phone, department, grade_level, section, is_active, created_at
                  FROM " . $this->table_name;
        
        if($role) {
            if(is_array($role)) {
                // Handle array of roles
                $placeholders = str_repeat('?,', count($role) - 1) . '?';
                $query .= " WHERE role IN ($placeholders)";
            } else {
                // Handle single role
                $query .= " WHERE role = :role";
            }
        }
        
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if($role) {
            if(is_array($role)) {
                // Bind array values
                foreach($role as $index => $roleValue) {
                    $stmt->bindValue($index + 1, $roleValue);
                }
            } else {
                // Bind single role
                $stmt->bindParam(":role", $role);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name=:first_name, last_name=:last_name, email=:email,
                      phone=:phone, department=:department, grade_level=:grade_level, 
                      section=:section, is_active=:is_active
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":grade_level", $this->grade_level);
        $stmt->bindParam(":section", $this->section);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Change password
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password=:password 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if user exists
    public function userExists($username, $email) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Get user statistics
    public function getUserStats() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers,
                    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
