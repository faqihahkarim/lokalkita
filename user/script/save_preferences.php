<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$prefs = $data['preferences'];

// Remove old data
$conn->query("DELETE FROM user_interest WHERE user_id = $user_id");

// Insert new interests
$stmt = $conn->prepare("INSERT INTO user_interest (user_id, interest_id) VALUES (?, ?)");

foreach ($prefs as $id) {
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
}

// Mark onboarding as complete
$conn->query("UPDATE user_acc SET has_preferences = 1 WHERE user_id = $user_id");

echo "OK";
?>
