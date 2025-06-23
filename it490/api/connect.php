<?php
/**
 * MySQL Database Connection Script for Tailscale
 * Connects to remote MySQL server securely via Tailscale private network
 */

// Database configuration - consider moving these to environment variables
$config = [
    'host'      => '100.70.204.26', // Tailscale IP of your MySQL VM
    'port'      => 3306,            // Default MySQL port
    'username'  => 'BARKBUDDYUSER', // Custom database user
    'password'  => 'new_secure_password',   // Strong password
    'database'  => 'BARKBUDDY',     // Database name
    'ssl'       => false,           // Disable SSL to avoid verification issues
    'timeout'   => 5                // Connection timeout in seconds
];

// Error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Initialize MySQLi object
    $conn = mysqli_init();

    // Set connection timeout
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, $config['timeout']);

    // Optional: disable SSL cert verification if SSL was previously enabled
    // If using real_connect with flags, you can skip this section entirely when ssl is false
    if ($config['ssl']) {
        $conn->ssl_set(null, null, null, null, null);
        $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    }

    // Establish the connection
    if (!$conn->real_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $config['port'],
        null,
        $config['ssl'] ? MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0
    )) {
        throw new Exception("MySQL connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");

    // Connection successful
    echo "✅ Connected successfully to BARKBUDDY database on " . $config['host'];

    // Example query - replace with your actual queries
    $result = $conn->query("SELECT 1");
    if ($result) {
        echo "\n🔹 Test query executed successfully";
        $result->free();
    }

} catch (Exception $e) {
    // Log error securely (in production, log to file instead of displaying)
    error_log($e->getMessage());
    die("❌ Database connection error. Please try again later.");
    
} finally {
    // Always close connection when done
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
