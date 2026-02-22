<?php
include_once __DIR__ . '/../config/Database.php';

$db = new Database();
$conn = $db->getConnection(DB_USERS);

$stmt = $conn->query("SELECT email FROM users WHERE username = 'testuser'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Raw Email in DB: " . $row['email'] . "\n";
?>
