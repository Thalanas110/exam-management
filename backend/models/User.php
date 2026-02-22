<?php
include_once __DIR__ . '/../config/Database.php';

class User {
    private $db;
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;

    public function __construct() {
        $this->db = new Database();
    }

    public function create() {
        // Assume email and password are set and optionally encrypted by controller/service if needed
        // BUT wait, model should arguably handle the data logic. 
        // The prompt says "Encryption before storing sensitive data".
        // Let's pass the already encrypted email to this function or encrypt it here?
        // Let's encrypt in the Controller to keep Model simple "Data Access Object" style?
        // Or encrypt in Model to ensure it's always encrypted? 
        // "Clear separation of encryption logic (e.g., helper or utility class)".
        // Let's encrypt in the Controller using the Encryption utility, to show separation.
        
        $params = [
            $this->username,
            $this->email, // Already encrypted
            $this->password, // Already hashed
            $this->role
        ];
        
        try {
            $stmt = $this->db->callProcedure(DB_USERS, 'sp_create_user', $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getByUsername() {
        $params = [$this->username];
        $stmt = $this->db->callProcedure(DB_USERS, 'sp_get_user_by_username', $params);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }
}
?>
