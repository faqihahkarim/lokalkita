<?php
session_start();
require 'db.php';  

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email    = trim($_POST['reg_email']);
    $password = trim($_POST['password']);

    // Hash password
    $hashedPw = password_hash($password, PASSWORD_DEFAULT);

    /* -------------------------------------
       CHECK IF EMAIL ALREADY EXISTS
    --------------------------------------- */
    $check = $conn->prepare("SELECT user_id FROM user_acc WHERE reg_email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists. Try another one.'); 
              window.location='../user_signup.html';</script>";
        exit();
    }

    /* -------------------------------------
        INSERT NEW USER
    --------------------------------------- */
    $stmt = $conn->prepare("
        INSERT INTO user_acc (reg_email, username, password, status)
        VALUES (?, ?, ?, 'Active')
    ");
    $stmt->bind_param("sss", $email, $username, $hashedPw);

    if ($stmt->execute()) {
        echo "<script>
                alert('Account created successfully!');
                window.location='../user_login.html';
              </script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
