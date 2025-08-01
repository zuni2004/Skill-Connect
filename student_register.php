<?php
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
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$date_of_birth = $_POST['date_of_birth'];
$cnic = $_POST['cnic'];
$age = $_POST['age'];
$phone = $_POST['phone'];
$bio = $_POST['bio'];
$academic_history = $_POST['academic_history'];
$country = $_POST['country'];
$province = $_POST['province'];
$city = $_POST['city'];
$area = $_POST['area'];
$street = $_POST['street'];
$postal_code = $_POST['postal_code'];
$agreed_terms = isset($_POST['agreed_terms']) ? 'true' : 'false';

// Check if passwords match
if ($_POST['password'] !== $_POST['confirm_password']) {
    die("Passwords do not match.");
}

// Optional: photo handling (store as blob)
$photo = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $photo = file_get_contents($_FILES['photo']['tmp_name']);
}

// Build INSERT query
$sql = "INSERT INTO students (
    first_name, last_name, username, email, password, date_of_birth, cnic, age, phone,
    photo, bio, academic_history, country, province, city, area, street, postal_code, agreed_terms
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssisbsssssssss",
    $first_name, $last_name, $username, $email, $password, $date_of_birth, $cnic, $age, $phone,
    $photo, $bio, $academic_history, $country, $province, $city, $area, $street, $postal_code, $agreed_terms
);

// Execute and check result
if ($stmt->execute()) {
    header("Location: login.html");
    exit;
} else {
    echo "Registration failed: " . $stmt->error;
}

echo count([$first_name, $last_name, $username, $email, $password, $date_of_birth, $cnic, $age, $phone,
    $photo, $bio, $academic_history, $country, $province, $city, $area, $street, $postal_code, $agreed_terms]);

// Close connection
$stmt->close();
$conn->close();
