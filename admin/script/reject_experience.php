<?php
session_start(); // <--- IMPORTANT for admin_id
require "db.php";

$exp_id = $_POST['exp_id'];
$comment = $_POST['admin_comment'];

// --------------------------------------
// 1. Get host_id + title
// --------------------------------------
$getHost = $conn->prepare("SELECT host_id, exp_title FROM experience WHERE exp_id=?");
$getHost->bind_param("i", $exp_id);
$getHost->execute();
$res = $getHost->get_result()->fetch_assoc();

$host_id = $res['host_id'];
$exp_title = $res['exp_title'];

// --------------------------------------
// 2. Reject experience
// --------------------------------------
$stmt = $conn->prepare("
    UPDATE experience 
    SET status='Rejected', admin_comment=? 
    WHERE exp_id=?
");
$stmt->bind_param("si", $comment, $exp_id);
$stmt->execute();

// --------------------------------------
// 3. Insert notification to host
// --------------------------------------
$notif_msg = "Your experience '{$exp_title}' was rejected. Feedback: {$comment}";
$type = "error";

$notif = $conn->prepare("
    INSERT INTO host_notifications (host_id, exp_id, type, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$notif->bind_param("iiss", $host_id, $exp_id, $type, $notif_msg);
$notif->execute();

// --------------------------------------
// 4. INSERT ADMIN LOG  (NEW)
// --------------------------------------
$admin_id = $_SESSION['admin_id'];
$action = "Rejected experience ID $exp_id ($exp_title). Reason: $comment";

$log = $conn->prepare("
    INSERT INTO admin_logs (admin_id, action)
    VALUES (?, ?)
");
$log->bind_param("ss", $admin_id, $action);
$log->execute();

// --------------------------------------
echo "OK";
?>
