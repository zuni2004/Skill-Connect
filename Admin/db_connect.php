<?php
// db_connect.php
// This file handles the database connection using PDO.
// It should be included at the beginning of all other PHP scripts that interact with the database.

// Database connection parameters
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403"; // Your database name
$user = "sql12784403"; // Your database username
$pass = "WAuJFq9xaX"; // Your database password

// Data Source Name (DSN) for MySQL
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

// PDO options for error handling and fetch mode
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better performance and security
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Catch any PDO exceptions and re-throw them for clearer error reporting
    error_log("Database connection error: " . $e->getMessage()); // Log error to server error log
    die("Database connection failed. Please try again later."); // Display a generic error message
}
?>
