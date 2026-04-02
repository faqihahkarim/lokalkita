<?php
require 'db.php';

$username = $_POST['username'];
$reg_email = $_POST['reg_email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check for existing email
$check = $conn->prepare("SELECT * FROM host_acc WHERE reg_email = ?");
$check->bind_param("s", $reg_email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email already exists!'); window.location='../host_signup.html';</script>";
    exit();
}

// Insert new host
$stmt = $conn->prepare("INSERT INTO host_acc (username, reg_email, password, status) VALUES (?, ?, ?, 'Active')");
$stmt->bind_param("sss", $username, $reg_email, $password);

if ($stmt->execute()) {
    echo "<script>alert('Registration successful!'); window.location='../host_login.html';</script>";
} else {
    echo "Error: " . $stmt->error;
}
?>
<?php