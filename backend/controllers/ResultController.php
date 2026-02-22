<?php
include_once __DIR__ . '/../models/Result.php';
include_once __DIR__ . '/../models/Question.php';
include_once __DIR__ . '/../utils/Encryption.php';

class ResultController {

    private function getAuthUser() {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        
        if(!$authHeader) return null;
        $token = str_replace('Bearer ', '', $authHeader);
        if(!$token) return null;
        
        return json_decode(base64_decode($token));
    }

    public function submit() {
        $user = $this->getAuthUser();
        if(!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->exam_id) && !empty($data->answers)) {
            $exam_id = $data->exam_id;
            
            // 1. Fetch correct answers
            $questionModel = new Question();
            $questions_data = $questionModel->getByExamId($exam_id);
            $questionsMap = [];
            $encryption = new Encryption();

            foreach ($questions_data as $row) {
                $decryptedCorrect = $encryption->decrypt($row['correct_option']);
                
                // If it's Enumeration, the decrypted value is a JSON Array string.
                if($row['question_type'] === 'enumeration') {
                    $questionsMap[$row['id']] = [
                        'type' => $row['question_type'],
                        'correct' => json_decode($decryptedCorrect, true) // Array
                    ];
                } else {
                    $questionsMap[$row['id']] = [
                        'type' => $row['question_type'],
                        'correct' => $decryptedCorrect // String
                    ];
                }
            }

            // 2. Calculate Score
            $score = 0;
            $total = count($questionsMap);
            
            foreach($data->answers as $ans) {
                if(isset($questionsMap[$ans->question_id])) {
                    $qData = $questionsMap[$ans->question_id];
                    $type = $qData['type'];
                    $correct = $qData['correct'];
                    $userAnswer = $ans->answer;

                    if ($type === 'identification') {
                        // Case-insensitive comparison
                        if (strcasecmp(trim($correct), trim($userAnswer)) === 0) {
                            $score++;
                        }
                    } elseif ($type === 'enumeration') {
                        // User Answer should be an array. Check if all correct items are present? 
                        // Or if user provided items are IN the correct list?
                        // Let's assume strict set equality or subset?
                        // Simple rule: count how many valid items the user provided up to max points?
                        // OR: 1 point for the whole question if ALL items match?
                        // Let's do simple: 1 point if the arrays contain same elements (order specific? no).
                        
                        if(is_array($userAnswer) && is_array($correct)) {
                             // Normalize for comparison (sort, lower case trimmed)
                             $c_norm = array_map('strtolower', array_map('trim', $correct));
                             $u_norm = array_map('strtolower', array_map('trim', $userAnswer));
                             
                             sort($c_norm);
                             sort($u_norm);
                             
                             if($c_norm == $u_norm) {
                                 $score++;
                             }
                        }
                    } else {
                        // Multiple Choice / True False (Exact Match)
                        if($correct === $userAnswer) {
                            $score++;
                        }
                    }
                }
            }
            
            // 3. Encrypt Score
            $encryptedScore = $encryption->encrypt((string)$score);
            
            // 4. Save Result
            $result = new Result();
            $result->user_id = $user->id;
            $result->exam_id = $exam_id;
            $result->score = $encryptedScore;
            $result->total_questions = $total;
            
            if($result->create()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Exam submitted successfully.",
                    "score_display" => "$score / $total"
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to save result."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data."]);
        }
    }

    public function getStudentResults($student_id) {
        $user = $this->getAuthUser();
        if(!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }
        
        if($user->role !== 'admin' && $user->id != $student_id) {
             http_response_code(403);
             echo json_encode(["message" => "Forbidden"]);
             return;
        }

        $result = new Result();
        $stmt = $result->getByUserId($student_id);
        $results_arr = [];
        $encryption = new Encryption();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $decryptedScore = $encryption->decrypt($row['score']);
            
            $item = [
                "id" => $row['id'],
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
