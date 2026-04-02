<?php
session_start();
require '../script/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// SAFELY GET INPUTS
$name   = $_POST['name'];
$phone  = $_POST['phone'];
$state  = $_POST['state'];
$username = $_POST['username'];
$budget = $_POST['budget'];
$password = $_POST['password']; // optional

// split budget "200-700"
$min_budget = null;
$max_budget = null;
if (!empty($budget)) {
    list($min_budget, $max_budget) = explode('-', $budget);
}

// ---------- HANDLE PROFILE IMAGE UPLOAD ----------
$profile_filename = null;

// Check if user uploaded a new picture
if (!empty($_FILES['profile_picture']['name'])) {

    $uploadDir = "../user/uploads/profile/";
    
    // create directory if not exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $profile_filename = time() . "_" . basename($_FILES["profile_picture"]["name"]);
    $targetFile = $uploadDir . $profile_filename;

    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
        // save new filename into DB
    } else {
        die("Error uploading file.");
    }
}

// ---------- UPDATE USER DATA ----------
if (!empty($password)) {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE user_acc 
            SET username=?, phone_num=?, state=?, 
                min_budget=?, max_budget=?, password=?"
            . (!empty($profile_filename) ? ", profile_pic=?" : "") .
           " WHERE user_id=?";

} else {
    $sql = "UPDATE user_acc 
            SET username=?, phone_num=?, state=?, 
                min_budget=?, max_budget=?"
            . (!empty($profile_filename) ? ", profile_pic=?" : "") .
           " WHERE user_id=?";
}

$stmt = $conn->prepare($sql);

// bind params dynamically
if (!empty($profile_filename) && !empty($password)) {
    $stmt->bind_param("sssisssi", 
        $username, $phone, $state, $min_budget, $max_budget, 
        $passwordHash, $profile_filename, $user_id
    );
} elseif (!empty($profile_filename)) {
    $stmt->bind_param("sssissi", 
        $username, $phone, $state, $min_budget, $max_budget, 
        $profile_filename, $user_id
    );
} elseif (!empty($password)) {
    $stmt->bind_param("sssissi", 
        $username, $phone, $state, $min_budget, $max_budget, 
        $passwordHash, $user_id
    );
} else {
    $stmt->bind_param("sssisi", 
        $username, $phone, $state, $min_budget, $max_budget, 
        $user_id
    );
}

$stmt->execute();


// ---------- UPDATE INTERESTS ----------
$conn->query("DELETE FROM user_interest WHERE user_id=$user_id");

if (!empty($_POST['interests'])) {
    $insert = $conn->prepare("INSERT INTO user_interest (user_id, interest_id) VALUES (?, ?)");
    foreach ($_POST['interests'] as $iid) {
        $insert->bind_param("ii", $user_id, $iid);
        $insert->execute();
    }
}

// redirect back
header("Location: ../user_dashboard.php?updated=1");
exit();
?>
