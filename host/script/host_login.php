<?php
session_start();
require 'db.php';

$reg_email = $_POST['reg_email'];
$password  = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM host_acc WHERE reg_email = ?");
$stmt->bind_param("s", $reg_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {

    $row = $result->fetch_assoc();
    $stored = $row['password'];
    $valid  = false;

    // Password hashed
    if (password_verify($password, $stored)) {
        $valid = true;

    // Plaintext old password
    } elseif ($password === $stored) {
        $valid = true;

        // Upgrade plaintext to hash
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE host_acc SET password=? WHERE host_id=?");
        $up->bind_param("si", $newHash, $row['host_id']);
        $up->execute();
    }

    if ($valid) {

        // Save session
        $_SESSION['host_id'] = $row['host_id'];
        $_SESSION['host_name'] = $row['username'];

        // ⭐ UPDATE LAST LOGIN HERE
        $update = $conn->prepare("UPDATE host_acc SET last_login = NOW() WHERE host_id = ?");
        $update->bind_param("i", $row['host_id']);
        $update->execute();

        // Check onboarding completeness
        if (
            empty($row['biz_name']) || 
            empty($row['biz_email']) || 
            empty($row['phone_num']) ||
            empty($row['focus_area']) ||
            empty($row['about']) ||
            empty($row['state'])
        ) {
            header("Location: ../profileonboard.php");
            exit();
        }

        // Completed onboarding → go dashboard
        header("Location: ../host_dashboard.php");
        exit();

    } else {
        echo "<script>alert('Wrong password!'); window.location='../host_login.html';</script>";
        exit();
    }

} else {
    echo "<script>alert('Email not found!'); window.location='../host_login.html';</script>";
    exit();
}
?>
