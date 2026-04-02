<?php
session_start();
require 'db.php';

if (!isset($_SESSION['host_id'])) {
    die("Access denied.");
}

$host_id = (int)$_SESSION['host_id'];
$exp_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exp_id <= 0) {
    die("Invalid experience ID.");
}

/* ========================================
   1. Validate ownership
======================================== */
$check = $conn->prepare("
    SELECT exp_id 
    FROM experience 
    WHERE exp_id = ? AND host_id = ?
");
$check->bind_param("ii", $exp_id, $host_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    die("Error: You are not allowed to delete this experience.");
}

/* ========================================
   2. START TRANSACTION
======================================== */
$conn->begin_transaction();

try {

    /* ------------------------------------
       3. Delete HOST notifications
    ------------------------------------ */
    $stmt = $conn->prepare("DELETE FROM host_notifications WHERE exp_id = ?");
    $stmt->bind_param("i", $exp_id);
    $stmt->execute();

    /* ------------------------------------
       4. Delete USER notifications (if any)
    ------------------------------------ */
    if ($conn->query("SHOW TABLES LIKE 'user_notifications'")->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM user_notifications WHERE exp_id = ?");
        $stmt->bind_param("i", $exp_id);
        $stmt->execute();
    }

    /* ------------------------------------
       5. Delete REVIEWS
    ------------------------------------ */
    $stmt = $conn->prepare("DELETE FROM review WHERE exp_id = ?");
    $stmt->bind_param("i", $exp_id);
    $stmt->execute();

    /* ------------------------------------
       6. Delete LIKES / SAVES
    ------------------------------------ */
    $stmt = $conn->prepare("DELETE FROM user_like WHERE exp_id = ?");
    $stmt->bind_param("i", $exp_id);
    $stmt->execute();

    /* ------------------------------------
       7. Delete EXPERIENCE IMAGES (FILES)
    ------------------------------------ */
    $imgQ = $conn->prepare("SELECT img_path FROM experience_images WHERE exp_id = ?");
    $imgQ->bind_param("i", $exp_id);
    $imgQ->execute();
    $imgs = $imgQ->get_result();

    while ($img = $imgs->fetch_assoc()) {
        $filePath = "../uploads/experience/" . $img['img_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Remove experience folder (if exists)
    $folderPath = "../uploads/experience/" . $exp_id;
    if (is_dir($folderPath)) {
        array_map('unlink', glob("$folderPath/*"));
        rmdir($folderPath);
    }

    /* ------------------------------------
       8. Delete IMAGE RECORDS
    ------------------------------------ */
    $stmt = $conn->prepare("DELETE FROM experience_images WHERE exp_id = ?");
    $stmt->bind_param("i", $exp_id);
    $stmt->execute();

    /* ------------------------------------
       9. FINALLY delete EXPERIENCE
    ------------------------------------ */
    $stmt = $conn->prepare("DELETE FROM experience WHERE exp_id = ?");
    $stmt->bind_param("i", $exp_id);
    $stmt->execute();

    /* ------------------------------------
       10. COMMIT
    ------------------------------------ */
    $conn->commit();

    echo "<script>
            alert('Experience has been deleted successfully.');
            window.location.href = '../list.php';
          </script>";

} catch (Exception $e) {
    $conn->rollback();
    die("Delete failed: " . $e->getMessage());
}
?>
