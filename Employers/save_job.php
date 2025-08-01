<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'connect.php';

// Get form data
$title = $_POST['title'];
$description = $_POST['description'];
$location = $_POST['location'];
$salary = $_POST['salary'];
$job_type = $_POST['job_type'];
$employer_id = 1; // Temporary - we'll make this dynamic later

// Prepare SQL query
$sql = "INSERT INTO job_postings (employer_id, title, description, location, salary, job_type) 
        VALUES (?, ?, ?, ?, ?, ?)";

// Create prepared statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error in preparing statement: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("isssss", $employer_id, $title, $description, $location, $salary, $job_type);

// Execute statement
if ($stmt->execute()) {
    echo "Job posted successfully! <a href='post_job.html'>Post another job</a>";
} else {
    echo "Error: " . $stmt->error;
}

// Close statement and connection
$stmt->close();
$conn->close();
?>