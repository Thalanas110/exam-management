<?php
include_once __DIR__ . '/../models/Exam.php';
include_once __DIR__ . '/../models/Question.php';
include_once __DIR__ . '/../utils/Encryption.php';

class ExamController {

    private function getAuthUser() {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        
        if(!$authHeader) return null;
        $token = str_replace('Bearer ', '', $authHeader);
        if(!$token) return null;
        
        return json_decode(base64_decode($token));
    }

    public function create() {
        // Only Admin
        $user = $this->getAuthUser();
        if(!$user || $user->role !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(
            !empty($data->title) &&
            !empty($data->duration_minutes) &&
            !empty($data->questions) &&
            is_array($data->questions)
        ) {
            $exam = new Exam();
            $exam->title = $data->title;
            $exam->description = isset($data->description) ? $data->description : '';
            $exam->duration_minutes = $data->duration_minutes;
            
            if($exam->create()) {
                $exam_id = $exam->id;
                $encryption = new Encryption();
                
                foreach($data->questions as $q) {
                    $question = new Question();
                    $question->exam_id = $exam_id;
                    $question->question_text = $q->question_text;
                    $question->question_type = isset($q->question_type) ? $q->question_type : 'multiple_choice';
                    
                    // Handle Options
                    // For MC/TF, options should be an array (or object).
                    // For Identification, options is null.
                    $question->options = isset($q->options) ? $q->options : null;
                    
                    // Handle Correct Answer
                    // For Enumeration, correct answer might be an array.
                    // We must convert to string (JSON) before encryption if it's not a simple string.
                    $correctRaw = $q->correct_option;
                    if(is_array($correctRaw) || is_object($correctRaw)) {
                        $correctRaw = json_encode($correctRaw);
                    }
                    
                    // Encrypt correct option
                    $question->correct_option = $encryption->encrypt((string)$correctRaw);
                    
                    $question->create();
                }
                
                http_response_code(201);
                echo json_encode(["message" => "Exam created successfully.", "id" => $exam_id]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to create exam."]);
            }
        } else {
             http_response_code(400);
             echo json_encode(["message" => "Incomplete data."]);
        }
    }

    public function getAll() {
        $exam = new Exam();
        $stmt = $exam->getAll();
        $exams_arr = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($exams_arr, $row);
        }
        
        echo json_encode($exams_arr);
    }

    public function getOne($id) {
        $exam = new Exam();
        $exam->id = $id;
        
        if($exam->getOne()) {
            $exam_arr = [
                "id" => $exam->id,
                "title" => $exam->title,
                "description" => $exam->description,
                "duration_minutes" => $exam->duration_minutes,
                "questions" => []
            ];
            
            $question = new Question();
            $questions_data = $question->getByExamId($id);
            
            foreach ($questions_data as $row) {
                $q_item = [
                    "id" => $row['id'],
                    "question_text" => $row['question_text'],
                    "question_type" => $row['question_type'],
                    "options" => json_decode($row['options'])
                    // Correct option hidden
                ];
                
                array_push($exam_arr['questions'], $q_item);
            }
            
            echo json_encode($exam_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Exam not found."]);
        }
    }
}
?>
