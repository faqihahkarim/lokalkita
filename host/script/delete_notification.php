<?php
session_start();
require "../script/db.php";

$notif_id = $_POST['notif_id'];

// Ensure host only deletes their own notifications (security)
$host_id = $_SESSION['host_id'];

$stmt = $conn->prepare("
    DELETE FROM host_notifications 
    WHERE notif_id = ? AND host_id = ?
");
$stmt->bind_param("ii", $notif_id, $host_id);
$stmt->execute();

echo "OK";
?>
