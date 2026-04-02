<?php
$host = "sql200.infinityfree.com";
$user = "if0_41540540";
$pass = "fOm6nX3XOcN";
$dbname = "if0_41540540_lokalkita";


$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
