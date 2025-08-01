<?php
session_start();

// Verify user is logged in as tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Database configuration
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db   = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$errors = [];
$uploadedImagePath = null;

// Process form data
$title = sanitizeInput($_POST['title']);
$subject = sanitizeInput($_POST['subject']);
$location = sanitizeInput($_POST['location']);
$mode = sanitizeInput($_POST['mode']);
$fee_type = sanitizeInput($_POST['fee_type']);
$fee_amount = floatval($_POST['fee_amount']);
$tutor_id = intval($_POST['tutor_id']);
$description = sanitizeInput($_POST['description']);
$rating = 0;
$created_at = date('Y-m-d H:i:s');

// Validate required fields
if (empty($title)) $errors[] = "Course title is required";
if (empty($subject)) $errors[] = "Subject is required";
if (empty($description) || strlen($description) < 20) $errors[] = "Description must be at least 20 characters";
if ($fee_amount <= 0) $errors[] = "Fee amount must be positive";

// Handle file upload
if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fileInfo, $_FILES['course_image']['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mime, $allowedTypes)) {
        $errors[] = "Only JPG, PNG, and GIF images are allowed";
    } elseif ($_FILES['course_image']['size'] > $maxSize) {
        $errors[] = "Image size must be less than 2MB";
    } else {
        $uploadDir = 'uploads/courses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('course_') . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['course_image']['tmp_name'], $destination)) {
            $uploadedImagePath = $destination;
        } else {
            $errors[] = "Failed to upload image";
        }
    }
}

// Make sure $tutor_id is set and valid
if (!isset($tutor_id) || !is_numeric($tutor_id)) {
    die("Invalid tutor_id");
}

// If no errors, proceed with database insertion
if (empty($errors)) {
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO courses (
        title, 
        subject, 
        location, 
        mode, 
        fee_type, 
        fee_amount, 
        tutor_id, 
        rating, 
        description, 
        created_at, 
        image_url
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssssdissds", 
        $title, 
        $subject, 
        $location, 
        $mode, 
        $fee_type, 
        $fee_amount, 
        $tutor_id, 
        $rating, 
        $description, 
        $created_at, 
        $uploadedImagePath
    );

    // Debugging line
    var_dump($tutor_id); // or whatever variable you use

    // Execute and respond
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course added successfully!";
    } else {
        $_SESSION['error_message'] = "Database error: " . $stmt->error;
        // Clean up uploaded file if database insert failed
        if ($uploadedImagePath && file_exists($uploadedImagePath)) {
            unlink($uploadedImagePath);
        }
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = implode("<br>", $errors);
    $_SESSION['form_data'] = $_POST; // Preserve form data for repopulation
}

$conn->close();
header("Location: tutor_dashboard.php");
exit;
?>