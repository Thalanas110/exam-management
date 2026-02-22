<?php
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../utils/Encryption.php';

class AuthController {
    
    public function register() {
        $data = json_decode(file_get_contents("php://input"));

        if(
            !empty($data->username) &&
            !empty($data->email) &&
            !empty($data->password)
        ) {
            $user = new User();
            $encryption = new Encryption();
            
            $user->username = $data->username;
            
            // Check if user exists (by username)
            if($user->getByUsername()) {
                http_response_code(400);
                echo json_encode(array("message" => "Username already exists."));
                return;
            }

            // Reset user object for creation
            $user->username = $data->username;
            
            // Encrypt Email
            $user->email = $encryption->encrypt($data->email);
            
            // Hash Password
            $user->password = password_hash($data->password, PASSWORD_BCRYPT);
            $user->role = isset($data->role) ? $data->role : 'student';

            if($user->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "User was created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create user."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->username) && !empty($data->password)) { // Changed email to username for login
            $user = new User();
            $encryption = new Encryption();
            
            $user->username = $data->username;
            
            if($user->getByUsername()) {
                if(password_verify($data->password, $user->password)) {
                    
                    // Decrypt email for response (as proof of decryption)
                    $decryptedEmail = $encryption->decrypt($user->email);
                    if ($decryptedEmail === false) {
                        $decryptedEmail = "Error decrypting email";
                    }

                    $token = base64_encode(json_encode([
                        "id" => $user->id,
                        "role" => $user->role,
                        "username" => $user->username,
                        "exp" => time() + (60*60)
                    ]));

                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Login successful.",
                        "token" => $token,
                        "user" => array(
                            "id" => $user->id,
                            "username" => $user->username,
                            "email" => $decryptedEmail, // Return decrypted info
                            "role" => $user->role
                        )
                    ));
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Login failed."));
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Login failed."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
    }
}
?>
