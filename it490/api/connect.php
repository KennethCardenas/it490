<?php
/**
 * Simple MySQL connection helper used by the MQ worker and API scripts.
 * Returns a mysqli instance connected to the BarkBuddy database.
 */

function getDbConnection(): mysqli
{
    static $conn;
    if ($conn instanceof mysqli && $conn->ping()) {
        return $conn;
    }

    $config = [
        'host'      => '100.70.204.26',
        'port'      => 3306,
        'username'  => 'BARKBUDDYUSER',
        'password'  => 'Linklinkm1!',
        'database'  => 'BARKBUDDY',
        'ssl'       => false,
        'timeout'   => 5
    ];

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $conn = mysqli_init();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, $config['timeout']);
    if ($config['ssl']) {
        $conn->ssl_set(null, null, null, null, null);
        $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    }
    if (!$conn->real_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $config['port'],
        null,
        $config['ssl'] ? MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0
    )) {
        throw new Exception('MySQL connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Provide a default $conn variable for legacy includes
$conn = getDbConnection();
