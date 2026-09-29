<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "dbcredit";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>