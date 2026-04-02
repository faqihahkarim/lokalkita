<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit;
}

$user_id   = $_SESSION['user_id'];
$bucket_id = $_POST['bucket_id'] ?? null;
$title     = $_POST['title'] ?? null;
$date      = $_POST['date'] ?? null;
$emotion   = $_POST['emotion'] ?? null;
$thoughts  = $_POST['thought'] ?? null;

if (!$bucket_id) {
    die("Invalid request.");
}

/* =========================
   CHECK IF JOURNAL EXISTS
========================= */
$check = $conn->prepare("
    SELECT journal_id
    FROM journal
    WHERE bucket_id = ? AND user_id = ?
");
$check->bind_param("ii", $bucket_id, $user_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    /* =========================
       UPDATE JOURNAL
    ========================= */
    $journal_id = $existing['journal_id'];

    $update = $conn->prepare("
        UPDATE journal
        SET title = ?, date_travel = ?, emotion = ?, thoughts = ?
        WHERE journal_id = ?
    ");
    $update->bind_param(
        "ssisi",
        $title,
        $date,
        $emotion,
        $thoughts,
        $journal_id
    );
    $update->execute();

    /* Remove old entries (simplest & safest) */
    $conn->query("DELETE FROM journal_entry WHERE journal_id = $journal_id");

} else {
    /* =========================
       INSERT JOURNAL
    ========================= */
    $insert = $conn->prepare("
        INSERT INTO journal (user_id, bucket_id, title, date_travel, emotion, thoughts)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "iissis",
        $user_id,
        $bucket_id,
        $title,
        $date,
        $emotion,
        $thoughts
    );
    $insert->execute();
    $journal_id = $conn->insert_id;
}

/* =========================
   HANDLE IMAGE UPLOADS
========================= */
$uploadDir = "uploads/journal/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES['images']['name'])) {
    foreach ($_FILES['images']['tmp_name'] as $index => $tmp) {

        if (!$tmp) continue;

        $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
        $fileName = uniqid("journal_") . "." . $ext;
        $filePath = $uploadDir . $fileName;

        move_uploaded_file($tmp, $filePath);

        $caption = $_POST['captions'][$index] ?? '';
        $seq = $index + 1;

        $entry = $conn->prepare("
            INSERT INTO journal_entry (journal_id, caption, img_path, seq_num)
            VALUES (?, ?, ?, ?)
        ");
        $entry->bind_param(
            "issi",
            $journal_id,
            $caption,
            $filePath,
            $seq
        );
        $entry->execute();
    }
}

/* =========================
   REDIRECT BACK
========================= */
header("Location: ../memory-lane.php?bucket_id=$bucket_id&saved=1");
exit;
