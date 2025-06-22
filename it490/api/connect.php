<?php
$host = '100.70.204.26'; // Your VM's Tailscale IP
$port = 3306;
$username = 'BARKBUDDYUSER';
$password = 'Linklinkm1!';
$dbname = 'BARKBUDDY';

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully to remote MySQL database!";
?>
