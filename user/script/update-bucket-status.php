<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "NOT_LOGGED_IN";
    exit();
}

$user_id = intval($_SESSION['user_id']);
$bucket_id = intval($_POST['bucket_id']);
$new_status = $_POST['status']; // "Active" or "Done"

// Validate
if (!in_array($new_status, ['Active', 'Done'])) {
    echo "INVALID_STATUS";
    exit();
}

// Ensure bucket belongs to user
$check = $conn->prepare("SELECT bucket_id FROM bucketlist WHERE bucket_id=? AND user_id=?");
$check->bind_param("ii", $bucket_id, $user_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    echo "NOT_ALLOWED";
    exit();
}

$update = $conn->prepare("UPDATE bucketlist SET status=? WHERE bucket_id=?");
$update->bind_param("si", $new_status, $bucket_id);
$update->execute();

echo "OK";
?>
