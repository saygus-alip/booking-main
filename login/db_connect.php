<?php

$servername = "localhost";
$username = "root";
$password = "alip4523pop";
$dbname = "booking-main";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn -> connect_error) {
    die("Connect failed: " . $conn -> connnect_error);
}

?>