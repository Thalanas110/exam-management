<?php
include_once __DIR__ . '/../config/Database.php';

class Question {
    private $db;
    public $id;
    public $exam_id;
    public $question_text;
    public $question_type;
    public $options; // JSON string or Array
    public $correct_option; // Encrypted

    public function __construct() {
        $this->db = new Database();
    }

    public function create() {
        // Ensure options is JSON string
        $options_json = (is_array($this->options) || is_object($this->options)) ? json_encode($this->options) : $this->options;
        if(empty($options_json)) $options_json = null;

        $params = [
            $this->exam_id,
            $this->question_text,
            $this->question_type,
            $options_json,
            $this->correct_option // Expecting encrypted value here
        ];
        
        try {
            $this->db->callProcedure(DB_CORE, 'sp_create_question', $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getByExamId($exam_id) {
        $params = [$exam_id];
        $stmt = $this->db->callProcedure(DB_CORE, 'sp_get_questions_by_exam', $params);
        $results = [];

        try {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Log error if needed
        }
        
        return $results;
    }
}
?>
