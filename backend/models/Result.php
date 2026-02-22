<?php
include_once __DIR__ . '/../config/Database.php';

class Result {
    private $db;
    public $id;
    public $user_id;
    public $exam_id;
    public $score; // Encrypted
    public $total_questions;
    public $submitted_at;

    public function __construct() {
        $this->db = new Database();
    }

    public function create() {
        $params = [
            $this->user_id,
            $this->exam_id,
            $this->score,
            $this->total_questions
        ];
        
        try {
            $this->db->callProcedure(DB_CORE, 'sp_create_result', $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getByUserId($user_id) {
        $params = [$user_id];
        $stmt = $this->db->callProcedure(DB_CORE, 'sp_get_results_by_user', $params);
        return $stmt;
    }
}
?>
