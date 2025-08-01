<?php
// filepath: d:\University\DBMS\DBMS Project\DBMS-Group-5\employer_register.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// DB credentials
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

// Collecting data from POST
$username = $_POST['username'];
$organization_name = $_POST['organization_name'];
$contact_person = $_POST['contact_person'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$confirm_password = $_POST['confirm_password'];
$address = $_POST['address'];
$website = $_POST['website'];

// Check if passwords match
if ($_POST['password'] !== $_POST['confirm_password']) {
    die("Passwords do not match.");
}

// Build INSERT query
$sql = "INSERT INTO employers (
    username, organization_name, contact_person, email, phone, password, address, website
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssss",
    $username, $organization_name, $contact_person, $email, $phone, $password, $address, $website
);

// Execute and check result
if ($stmt->execute()) {
    header("Location: login.html");
    exit;
} else {
    echo "Registration failed: " . $stmt->error;
}

// Close connection
$stmt->close();
$conn->close();