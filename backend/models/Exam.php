<?php
include_once __DIR__ . '/../config/Database.php';

class Exam {
    private $db;
    public $id;
    public $title;
    public $description;
    public $duration_minutes;
    public $created_at;

    public function __construct() {
        $this->db = new Database();
    }

    public function create() {
        $params = [
            $this->title,
            $this->description,
            $this->duration_minutes
        ];
        
        try {
            $stmt = $this->db->callProcedure(DB_CORE, 'sp_create_exam', $params);
            
            // Loop through rowsets (standard PDO pattern for SPs returning results)
            do {
                try {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && isset($row['id'])) {
                        $this->id = $row['id'];
                        $stmt->closeCursor();
                        return true;
                    }
                } catch (Exception $e) {
                    // Ignore fetch errors on non-result rowsets
                }
            } while ($stmt->nextRowset());
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAll() {
        $stmt = $this->db->callProcedure(DB_CORE, 'sp_get_all_exams');
        return $stmt;
    }

    public function getOne() {
        $params = [$this->id];
        $stmt = $this->db->callProcedure(DB_CORE, 'sp_get_exam_by_id', $params);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->duration_minutes = $row['duration_minutes'];
            return true;
        }
        return false;
    }
}
?>
