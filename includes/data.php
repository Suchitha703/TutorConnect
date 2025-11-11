<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'localtutorconnect';
    private $username = 'root';
    private $password = 'root';
    public $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname}", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Generic query execution method
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }

    // Get single record
    public function getSingle($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch();
    }

    // Get multiple records
    public function getMultiple($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }

    // Insert record and return ID
    public function insert($sql, $params = []) {
        $this->executeQuery($sql, $params);
        return $this->conn->lastInsertId();
    }

    // Update records
    public function update($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
}
?>