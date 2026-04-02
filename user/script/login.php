<?php
session_start();
require 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['reg_email']);
    $password = trim($_POST['password']);

    // Fetch user
    $stmt = $conn->prepare("SELECT user_id, username, password FROM user_acc WHERE reg_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // If no user found
    if ($result->num_rows == 0) {
        echo "<script>alert('Email not found!'); window.location='../user_login.html';</script>";
        exit();
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo "<script>alert('Incorrect password!'); window.location='../user_login.html';</script>";
        exit();
    }

    // Save session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];

    // Update last_login
    $update = $conn->prepare("UPDATE user_acc SET last_login = NOW() WHERE user_id = ?");
    $update->bind_param("i", $user['user_id']);
    $update->execute();

    // Redirect
    // Check if first-time
        $prefCheck = $conn->prepare("SELECT has_preferences FROM user_acc WHERE user_id = ?");
        $prefCheck->bind_param("i", $user['user_id']);
        $prefCheck->execute();
        $prefRes = $prefCheck->get_result()->fetch_assoc();

        if ($prefRes['has_preferences'] == 0) {
            // First time login → go to preferences
            echo "<script>window.location='../preferences.php';</script>";
        } else {
            // Already onboarded → go to homepage/dashboard
            echo "<script>window.location='../main_page.php';</script>";
        }

}
?>
