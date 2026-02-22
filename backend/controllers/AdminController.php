<?php
include_once __DIR__ . '/../models/Exam.php';
include_once __DIR__ . '/../models/Result.php';
include_once __DIR__ . '/../utils/Encryption.php';

class AdminController {

    private function getAuthUser() {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        
        if(!$authHeader) return null;
        $token = str_replace('Bearer ', '', $authHeader);
        if(!$token) return null;
        
        return json_decode(base64_decode($token));
    }

    private function isAdmin() {
        $user = $this->getAuthUser();
        return ($user && $user->role === 'admin');
    }

    public function getAllExams() {
        if(!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        // Reuse Exam model
        $exam = new Exam();
        $stmt = $exam->getAll();
        $exams_arr = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($exams_arr, $row);
        }
        
        echo json_encode($exams_arr);
    }

    public function getAllResults() {
        if(!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        // We need a method in Result model to get ALL results
        // I should add `getAll()` to Result model.
        // For now, I'll use raw DB call via new method in controller or add safely to model?
        // Let's add `getAll()` to Result model first/dynamically.
        // Wait, `sp_get_all_results` exists in `init_dbs.php`.
        
        $db = new Database();
        $stmt = $db->callProcedure(DB_CORE, 'sp_get_all_results');
        
        $results_arr = [];
        $encryption = new Encryption();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $decryptedScore = $encryption->decrypt($row['score']);
            
            $item = [
                "id" => $row['id'],
                "user_id" => $row['user_id'],
                "exam_id" => $row['exam_id'],
                "score" => $decryptedScore,
                "total_questions" => $row['total_questions'],
                "submitted_at" => $row['submitted_at']
            ];
            array_push($results_arr, $item);
        }

        echo json_encode($results_arr);
    }
}
?>
