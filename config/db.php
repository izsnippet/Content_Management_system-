
<?php

// Start the session if it has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection details.
$host = "localhost";
$username = "root";
$password = "";
$database = "wiki_cms_v2";

// Enable MySQLi error reporting.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Connect PHP to the MySQL database.
    $conn = new mysqli(
        $host,
        $username,
        $password,
        $database
    );

    // Support text in different languages.
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Log the technical error privately.
    error_log("Database connection failed: " . $e->getMessage());

    // Show a safe message to the visitor.
    http_response_code(500);
    exit("Database connection failed. Please try again later.");
}
?>
