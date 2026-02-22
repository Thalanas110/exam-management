<?php
include_once __DIR__ . '/../config/Database.php'; // For direct procedure calls if needed or use models
include_once __DIR__ . '/../utils/Encryption.php';

class ReportController {

    private function getAuthUser() {
        // ... (auth logic - repetitive, should be helper)
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

    // Since scores are encrypted, we must fetch all and aggregate in PHP
    private function getAllResultsDecrypted() {
        $db = new Database();
        $stmt = $db->callProcedure(DB_CORE, 'sp_get_all_results');
        $encryption = new Encryption();
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['score'] = (int)$encryption->decrypt($row['score']);
            $data[] = $row;
        }
        return $data;
    }

    public function getExamPerformance() {
        if(!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        $results = $this->getAllResultsDecrypted();
        $stats = []; // exam_id => [total_score, count, average]

        foreach($results as $r) {
            $eid = $r['exam_id'];
            if(!isset($stats[$eid])) {
                $stats[$eid] = ['total' => 0, 'count' => 0];
            }
            $stats[$eid]['total'] += $r['score'];
            $stats[$eid]['count']++;
        }

        $report = [];
        foreach($stats as $eid => $val) {
            $report[] = [
                "exam_id" => $eid,
                "average_score" => $val['total'] / $val['count'],
                "attempts" => $val['count']
            ];
        }

        echo json_encode($report);
    }

    public function getPassFailStats() {
        if(!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        $results = $this->getAllResultsDecrypted();
        $stats = []; // exam_id => [pass, fail]
        $passThreshold = 0.5; // 50%

        foreach($results as $r) {
            $eid = $r['exam_id'];
            if(!isset($stats[$eid])) {
                $stats[$eid] = ['pass' => 0, 'fail' => 0];
            }
            
            $percentage = $r['score'] / $r['total_questions'];
            if($percentage >= $passThreshold) {
                $stats[$eid]['pass']++;
            } else {
                $stats[$eid]['fail']++;
            }
        }

        $report = [];
        foreach($stats as $eid => $val) {
            $report[] = [
                "exam_id" => $eid,
                "pass_count" => $val['pass'],
                "fail_count" => $val['fail']
            ];
        }

         echo json_encode($report);
    }
}
?>
