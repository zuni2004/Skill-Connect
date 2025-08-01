<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";

// MySQLi connection
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve and sanitize form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? ''; // Changed from 'number' to 'phone'
$cnic = $_POST['cnic'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$bio = $_POST['bio'] ?? '';
$fee_type = $_POST['fee_type'] ?? '';
$username = $_POST['username'] ?? '';

// Password confirmation check
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Simple validation
if (empty($name) || empty($email) || empty($phone) || empty($cnic) || empty($password) || empty($fee_type)) {
    die("Please fill all required fields.");
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// INSERT query (use parameterized query to prevent SQL injection)
$sql = "INSERT INTO tutors (name, username, email, phone_number, cnic, password, bio, fee_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $name, $username, $email, $phone, $cnic, $hashed_password, $bio, $fee_type);

if ($stmt->execute()) {
    header("Location: login.html");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
