<?php
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../utils/Encryption.php';

$user = new User();
$encryption = new Encryption();

$user->username = "admin";
if ($user->getByUsername()) {
    echo "Admin already exists.\n";
} else {
    $user->username = "admin";
    $user->email = $encryption->encrypt("admin@example.com");
    $user->password = password_hash("admin123", PASSWORD_BCRYPT);
    $user->role = "admin";

    if($user->create()) {
        echo "Admin created successfully.\n";
    } else {
        echo "Failed to create admin.\n";
    }
}
?>
