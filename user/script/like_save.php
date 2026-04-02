<?php
session_start();
require 'db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$exp_id  = isset($_POST['exp_id']) ? (int)$_POST['exp_id'] : 0;
$type    = isset($_POST['type']) ? $_POST['type'] : "";

if ($exp_id <= 0 || ($type !== "like" && $type !== "save")) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

/**
 * Clear FastAPI cache for this user (non-blocking, ignore failure)
 */
function clear_ai_cache($userKey) {
    $cacheUrl = "http://127.0.0.1:8000/cache/clear?user_key=" . urlencode((string)$userKey);
    @file_get_contents($cacheUrl);
}

$responseStatus = "error";

if ($type === "like") {

    $check = $conn->prepare("SELECT 1 FROM user_like WHERE user_id=? AND exp_id=? LIMIT 1");
    $check->bind_param("ii", $user_id, $exp_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $del = $conn->prepare("DELETE FROM user_like WHERE user_id=? AND exp_id=?");
        $del->bind_param("ii", $user_id, $exp_id);
        $del->execute();
        $del->close();

        $responseStatus = "unliked";
    } else {
        $ins = $conn->prepare("INSERT INTO user_like (user_id, exp_id) VALUES (?,?)");
        $ins->bind_param("ii", $user_id, $exp_id);
        $ins->execute();
        $ins->close();

        $responseStatus = "liked";
    }

} else if ($type === "save") {

    $check = $conn->prepare("SELECT 1 FROM user_save WHERE user_id=? AND exp_id=? LIMIT 1");
    $check->bind_param("ii", $user_id, $exp_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $del = $conn->prepare("DELETE FROM user_save WHERE user_id=? AND exp_id=?");
        $del->bind_param("ii", $user_id, $exp_id);
        $del->execute();
        $del->close();

        $responseStatus = "unsaved";
    } else {
        $ins = $conn->prepare("INSERT INTO user_save (user_id, exp_id) VALUES (?,?)");
        $ins->bind_param("ii", $user_id, $exp_id);
        $ins->execute();
        $ins->close();

        $responseStatus = "saved";
    }
}

// ✅ Clear cache AFTER updating DB (for BOTH like/save)
clear_ai_cache($user_id);

// Return response
echo json_encode(["status" => $responseStatus]);
exit;
?>
