<?php
ini_set('display_errors', '0');
$servername = "58.180.27.46";
$username = 'ktm';
$password = "@ilovemath7";
$dbname = "ktm";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>