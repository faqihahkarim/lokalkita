<?php
session_start();
require 'db.php';

if (!isset($_SESSION['host_id'])) {
    die("Access denied.");
}

$host_id = $_SESSION['host_id'];

// Collect form data
$username    = $_POST['username'];
$biz_name    = $_POST['biz_name'];
$biz_email   = $_POST['biz_email'];
$phone       = $_POST['phone_num'];
$focus_area  = $_POST['category'];
$about       = $_POST['about'];
$state       = $_POST['state'];

// Handle profile picture upload
$profile_pic = null;

if (!empty($_FILES['profile_img']['name'])) {
    $target_dir = "../uploads/profile/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $image_name = time() . "_" . basename($_FILES["profile_img"]["name"]);
    $target_file = $target_dir . $image_name;

    if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file)) {
        $profile_pic = $image_name;
    }
}

// Build SQL query
if ($profile_pic) {
    $sql = "UPDATE host_acc 
            SET username=?, biz_name=?, biz_email=?, phone_num=?, focus_area=?, about=?, state=?, profile_pic=?
            WHERE host_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi",
        $username, $biz_name, $biz_email, $phone,
        $focus_area, $about, $state, $profile_pic, $host_id
    );
} else {
    $sql = "UPDATE host_acc 
            SET username=?, biz_name=?, biz_email=?, phone_num=?, focus_area=?, about=?, state=?
            WHERE host_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi",
        $username, $biz_name, $biz_email, $phone,
        $focus_area, $about, $state, $host_id
    );
}

$stmt->execute();

// Success popup + redirect
echo "
<script>
    alert('Your profile has been saved!');
    window.location.href = '../host_dashboard.php';
</script>";
exit();
?>
