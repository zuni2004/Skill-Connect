<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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

// Collect data from POST
$username = $_POST['username'] ?? '';
$full_name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$user_type = $_POST['user_type'] ?? '';
$linked_user_id = !empty($_POST['linked_user_id']) ? intval($_POST['linked_user_id']) : null;
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Check required fields
if (!$username || !$full_name || !$email || !$user_type || !$password || !$confirm_password) {
    die("Please fill all required fields.");
}

// Check password match
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Validate linked_user_id based on user_type
if ($linked_user_id !== null) {
    $table_to_check = '';
    $id_column = '';

    if ($user_type === 'student') {
        $table_to_check = 'students';
        $id_column = 'student_id';
    } elseif ($user_type === 'tutor') {
        $table_to_check = 'tutors';
        $id_column = 'id';
    } else {
        die("Invalid user type provided.");
    }

    $sql_check = "SELECT $id_column FROM $table_to_check WHERE $id_column = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        die("Prepare failed for linked_user_id check: " . $conn->error);
    }

    $stmt_check->bind_param("i", $linked_user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        die("Linked user ID not found in the $table_to_check table.");
    }

    $stmt_check->close();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Handle resume upload
$resume_filepath = null;
if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = basename($_FILES['resume']['name']);
    $target_file = $upload_dir . time() . "_" . $filename;

    if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_file)) {
        $resume_filepath = $target_file;
    } else {
        die("Resume upload failed.");
    }
}

// Prepare SQL with corrected column names
$sql = "INSERT INTO job_seekers (
            username, full_name, email, phone, resume_filepath, user_type, linked_user_id, password
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param(
    "ssssssis",
    $username,
    $full_name,
    $email,
    $phone,
    $resume_filepath,
    $user_type,
    $linked_user_id,
    $hashed_password
);

// Execute and redirect or show error
if ($stmt->execute()) {
    header("Location: login.html");
    exit;
} else {
    echo "Registration failed: " . $stmt->error;
}

// Cleanup
$stmt->close();
$conn->close();
?>