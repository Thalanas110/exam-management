<?php
include_once __DIR__ . '/config.php';

class Database {
    private $connections = [];

    public function getConnection($dbName) {
        if (!isset($this->connections[$dbName])) {
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . $dbName, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connections[$dbName] = $conn;
            } catch(PDOException $e) {
                // If DB doesn't exist yet (e.g. during init), try connecting without dbname
                try {
                    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $conn;
                } catch(PDOException $ex) {
                    echo "Connection error: " . $ex->getMessage();
                    exit;
                }
            }
        }
        return $this->connections[$dbName];
    }

    // Helper to call Stored Procedures
    public function callProcedure($dbName, $procName, $params = []) {
        $conn = $this->getConnection($dbName);
        
        // Build query: CALL procName(?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($params), '?'));
        $sql = "CALL $procName($placeholders)";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => &$val) { // Note: some drivers require reference for bindParam, but bindValue is safer for literals
             $stmt->bindValue($key + 1, $val);
        }
        
        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            // Log error or rethrow
            file_put_contents("php://stderr", "DB Error: " . $e->getMessage() . "\n");
            throw $e;
        }
    }
}
?>
