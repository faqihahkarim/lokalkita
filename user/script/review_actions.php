<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

if ($action === 'update') {
    $review_id = (int)($_POST['review_id'] ?? 0);
    $rating    = (int)($_POST['rating'] ?? 0);
    $review    = trim($_POST['review'] ?? '');

    if ($review_id <= 0 || $rating < 1 || $rating > 5 || $review === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE review 
        SET review = ?, rating = ?
        WHERE review_id = ? AND user_id = ?
    ");
    $stmt->bind_param("siii", $review, $rating, $review_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nothing updated']);
    }
    $stmt->close();
    exit;
}

if ($action === 'delete') {
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($review_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review']);
        exit;
    }

    $stmt = $conn->prepare("
        DELETE FROM review 
        WHERE review_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot delete this review']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
