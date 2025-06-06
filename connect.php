<?php
$servername = "localhost";
$username = "root";
$password = "";  // or your password
$dbname = "users_db";  // make sure this DB exists

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>  
