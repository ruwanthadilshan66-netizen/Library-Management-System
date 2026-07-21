<?php
$host = 'localhost';
$dbname = 'library_management_system';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);  
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}?>