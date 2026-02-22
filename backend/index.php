<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// Default routing
// /api/auth/register -> AuthController->register
// /api/auth/login -> AuthController->login
// /api/exams -> ExamController
// /api/results -> ResultController

// Search for 'api' in the path to determine start of routing
$apiIndex = array_search('api', $uri);

if ($apiIndex !== false) {
    $resource = isset($uri[$apiIndex + 1]) ? $uri[$apiIndex + 1] : null;
    $action = isset($uri[$apiIndex + 2]) ? $uri[$apiIndex + 2] : null;
    $id = isset($uri[$apiIndex + 3]) ? $uri[$apiIndex + 3] : null;
    
    switch ($resource) {
        case 'auth':
            include_once __DIR__ . '/controllers/AuthController.php';
            $auth = new AuthController();
            if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->register();
            } elseif ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->login();
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Auth endpoint not found"]);
            }
            break;

        case 'users':
            include_once __DIR__ . '/controllers/UserController.php';
            $userController = new UserController();
            if ($action === 'profile') {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $userController->getProfile();
                } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                    $userController->updateProfile();
                } else {
                    http_response_code(405);
                    echo json_encode(["message" => "Method not allowed"]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "User endpoint not found"]);
            }
            break;

        case 'exams':
            include_once __DIR__ . '/controllers/ExamController.php';
            $examController = new ExamController();
            if ($action === null || $action === '') {
                 if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                     $examController->getAll();
                 } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                     $examController->create();
                 }
            } elseif (is_numeric($action)) {
                 if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                     $examController->getOne($action);
                 }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Exam endpoint not found"]);
            }
            break;

        case 'results':
            include_once __DIR__ . '/controllers/ResultController.php';
            $resultController = new ResultController();
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $resultController->submit();
            } elseif ($action === 'student' && is_numeric($id) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $resultController->getStudentResults($id);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Result endpoint not found"]);
            }
            break;

        case 'admin':
            include_once __DIR__ . '/controllers/AdminController.php';
            $adminController = new AdminController();
            if ($action === 'exams' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $adminController->getAllExams();
            } elseif ($action === 'results' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $adminController->getAllResults();
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Admin endpoint not found"]);
            }
            break;

        case 'reports':
            include_once __DIR__ . '/controllers/ReportController.php';
            $reportController = new ReportController();
            if ($action === 'exam-performance' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $reportController->getExamPerformance();
            } elseif ($action === 'pass-fail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $reportController->getPassFailStats(); // Using correct method name
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Report endpoint not found"]);
            }
            break;
        
        // Add other cases here as we implement them
        
        default:
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found: $resource"]);
            break;
    }
} else {
     // Basic root response or 404
     // If accessing root without /api, show running message
     echo json_encode(["message" => "Exam System API Running"]);
}
?>
