<?php
// Database configuration for InfinityFree hosting
class Database {
    private $host = 'sql102.infinityfree.com'; // InfinityFree MySQL host
    private $db_name = 'if0_40241636_sepnas'; // Replace with your actual database name
    private $username = 'if0_40241636'; // Replace with your actual username
    private $password = 'gRFQ6Xft8DF'; // Replace with your actual password
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
