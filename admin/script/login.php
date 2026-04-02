<?php
session_start();
require 'db.php';

$admin_id = $_POST['admin_id'];
$password  = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM admin_acc WHERE admin_id = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();



if ($result->num_rows === 1) {
    
    $row = $result->fetch_assoc();
    $stored = $row['password'];
    $valid  = false;

    // CASE 1: bcrypt hashed password
    if (password_verify($password, $stored)) {
        $valid = true;

    // CASE 2: plaintext legacy passwords
    } elseif ($password === $stored) {
        $valid = true;

        // auto-upgrade to hashed
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE admin_acc SET password=? WHERE admin_id=?");
        $up->bind_param("ss", $newHash, $row['admin_id']);
        $up->execute();
    }

    if ($valid) {
        $_SESSION['admin_id'] = $row['admin_id'];

        // Redirect properly
        header("Location: ../admin_dashboard.php");
        exit();
    }

    // wrong password
    echo "<script>alert('Wrong password!'); window.location='../admin_login.html';</script>";
    exit();
}

// admin not found
echo "<script>alert('Admin ID not found!'); window.location='../admin_login.html';</script>";
exit();
?>
