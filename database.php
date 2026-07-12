<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "water_database";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("" . $conn->connect_error);
}

$conn->set_charset("utf8mb4"); 
?>