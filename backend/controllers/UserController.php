<?php
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../utils/Encryption.php';

class UserController {
    
    private function getAuthUser() {
        // Attempt to get token from Authorization header or fallback to a query param/post body for simplicity if needed
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        
        // Handle different casing of Authorization header
        $authHeader = null;
        if(isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif(isset($headers['authorization'])) {
             $authHeader = $headers['authorization'];
        }

        if(!$authHeader) return null;

        $token = str_replace('Bearer ', '', $authHeader);
        if(!$token) return null;

        $decoded = json_decode(base64_decode($token));
        return $decoded; // {id, role, username, exp}
    }

    public function getProfile() {
        $authUser = $this->getAuthUser();
        if(!$authUser) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        $user = new User();
        $user->username = $authUser->username;
        
        if($user->getByUsername()) {
            $encryption = new Encryption();
            $decryptedEmail = $encryption->decrypt($user->email);

            echo json_encode(array(
                "id" => $user->id,
                "username" => $user->username,
                "email" => $decryptedEmail, 
                "role" => $user->role
            ));
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found"]);
        }
    }

    public function updateProfile() {
        $authUser = $this->getAuthUser();
        if(!$authUser) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        $user = new User();
        $user->username = $authUser->username;
        
        if($user->getByUsername()) {
            if(!empty($data->email)) {
                 $encryption = new Encryption();
                 $encryptedEmail = $encryption->encrypt($data->email);
                 
                 // Update DB Logic would go here
                 // $user->updateEmail($encryptedEmail);
                 
                 // For now, mock success
                 echo json_encode(["message" => "Profile update logic placeholder. Add SP if critical."]);
            } else {
                 echo json_encode(["message" => "No data to update."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found"]);
        }
    }
}
?>
