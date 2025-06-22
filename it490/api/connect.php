<?php
$host = '10.0.2.15'; // IP of dev-db VM
$user = 'BARKBUDDYUSER';
$pass = 'SECUREPASSWORD';
$dbname = 'BARKBUDDY';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
