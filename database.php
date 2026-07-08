<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "water_database";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Xiriirkii database-ka waa uu guuldareystay: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4"); 
?>