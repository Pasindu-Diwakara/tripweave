<?php
/**
 * TripWeave — includes/db.php
 * Database connection using MySQLi with prepared-statement support.
 * Credentials match a standard XAMPP/WAMP local setup.
 * If you use a password, set DB_PASS to your password string.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change if your MySQL root has a password
define('DB_NAME', 'tripweave');
define('DB_PORT', 3306);

/* ── Establish connection ──────────────────────────────── */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Terminate early with a helpful message if connection fails
if ($conn->connect_error) {
    // In production you'd log this rather than displaying it
    die(json_encode([
        'error' => true,
        'message' => 'Database connection failed: ' . $conn->connect_error
            . ' — Make sure XAMPP/WAMP is running and the "tripweave" database exists.'
    ]));
}

// Use UTF-8 multibyte for proper emoji / international character support
$conn->set_charset('utf8mb4');
