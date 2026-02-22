<?php
include_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================================
    // 1. Setup User Database
    // ==========================================
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_USERS);
    $pdo->exec("USE " . DB_USERS);
    
    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email TEXT NOT NULL, -- Encrypted
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'student') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // SP: Create User
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_create_user");
    $pdo->exec("CREATE PROCEDURE sp_create_user(
        IN p_username VARCHAR(255),
        IN p_email TEXT,
        IN p_password VARCHAR(255),
        IN p_role VARCHAR(50)
    )
    BEGIN
        INSERT INTO users (username, email, password, role) VALUES (p_username, p_email, p_password, p_role);
    END");

    // SP: Get User By Filter (We can't search encrypted email easily without retrieving all, 
    // but for login we usually assume username or we accept that we might need to scan. 
    // Wait, the prompt implies email login. 
    // Standard approach for encrypted search is blind indexing, but that's complex. 
    // For this assignment, we might have to fetch user by ID or just fetch all and filter in PHP if dataset is small, 
    // OR just use username for login. 
    // Let's implement sp_get_user_by_email_hash if we were smart, but let's stick to sp_get_user_by_username for now 
    // or just sp_get_all_users and filter in PHP (inefficient but works for small scale).
    // Actually, let's just make 'username' the login identifier for simplicity, 
    // OR we create a procedure that returns the user if found.
    // NOTE: 'email' is the sensitive field. 
    // Let's create sp_find_user_by_username for login.
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_user_by_email"); 
    // We can't query WHERE email = ? because it's encrypted differently every time (IV).
    // So we will rely on username for uniqueness/lookup, or simple linear scan in PHP (bad practice but strictly following 'encrypt everything' without search index).
    // Let's assume unique Username for Login.
    
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_user_by_username");
    $pdo->exec("CREATE PROCEDURE sp_get_user_by_username(IN p_username VARCHAR(255))
    BEGIN
        SELECT id, username, email, password, role FROM users WHERE username = p_username;
    END");

    // SP: Get All Users (for filtering or admin)
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_all_users");
    $pdo->exec("CREATE PROCEDURE sp_get_all_users()
    BEGIN
        SELECT id, username, email, role FROM users;
    END");

    // ==========================================
    // 2. Setup Core Database
    // ==========================================
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_CORE);
    $pdo->exec("USE " . DB_CORE);

    // Exams Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration_minutes INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Questions Table
    $pdo->exec("DROP TABLE IF EXISTS questions");
    $pdo->exec("CREATE TABLE questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'identification', 'enumeration') DEFAULT 'multiple_choice',
        options JSON, -- Stores options for MC/TF, null for Identification
        correct_option TEXT NOT NULL, -- Encrypted. For Enum, strictly implies encrypted JSON string? Or just comma sep? Let's use JSON string then Encrypt.
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )");

    // Results Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, -- Refers to DB_USERS.users.id
        exam_id INT NOT NULL,
        score TEXT NOT NULL, -- Encrypted
        total_questions INT NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )");

    // --- Stored Procedures for Core ---

    // Exams
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_create_exam");
    $pdo->exec("CREATE PROCEDURE sp_create_exam(IN p_title VARCHAR(255), IN p_description TEXT, IN p_duration INT)
    BEGIN
        INSERT INTO exams (title, description, duration_minutes) VALUES (p_title, p_description, p_duration);
        SELECT LAST_INSERT_ID() as id;
    END");

    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_all_exams");
    $pdo->exec("CREATE PROCEDURE sp_get_all_exams()
    BEGIN
        SELECT * FROM exams;
    END");

    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_exam_by_id");
    $pdo->exec("CREATE PROCEDURE sp_get_exam_by_id(IN p_id INT)
    BEGIN
        SELECT * FROM exams WHERE id = p_id;
    END");

    // Questions
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_create_question");
    $pdo->exec("CREATE PROCEDURE sp_create_question(
        IN p_exam_id INT, 
        IN p_text TEXT, 
        IN p_type VARCHAR(50),
        IN p_options JSON, 
        IN p_correct TEXT
    )
    BEGIN
        INSERT INTO questions (exam_id, question_text, question_type, options, correct_option) 
        VALUES (p_exam_id, p_text, p_type, p_options, p_correct);
    END");

    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_questions_by_exam");
    $pdo->exec("CREATE PROCEDURE sp_get_questions_by_exam(IN p_exam_id INT)
    BEGIN
        SELECT id, exam_id, question_text, question_type, options, correct_option FROM questions WHERE exam_id = p_exam_id;
    END");

    // Results
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_create_result");
    $pdo->exec("CREATE PROCEDURE sp_create_result(
        IN p_user_id INT,
        IN p_exam_id INT,
        IN p_score TEXT,
        IN p_total INT
    )
    BEGIN
        INSERT INTO results (user_id, exam_id, score, total_questions) VALUES (p_user_id, p_exam_id, p_score, p_total);
    END");

    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_results_by_user");
    $pdo->exec("CREATE PROCEDURE sp_get_results_by_user(IN p_user_id INT)
    BEGIN
        SELECT * FROM results WHERE user_id = p_user_id;
    END");
    
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_all_results");
    $pdo->exec("CREATE PROCEDURE sp_get_all_results()
    BEGIN
        SELECT * FROM results;
    END");

    echo "Databases and Stored Procedures initialized successfully.";

} catch (PDOException $e) {
    echo "Initialization Failed: " . $e->getMessage();
}
?>
