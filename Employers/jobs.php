<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB connection file
include 'connect.php'; // ✅ Use your working connection

// Get form values
$title = $_POST['jobTitle'] ?? '';
$job_type = $_POST['jobType'] ?? '';
$work_mode = $_POST['locationType'] ?? '';
$location = $_POST['location'] ?? '';
$salary_min = $_POST['salaryMin'] ?? '';
$salary_max = $_POST['salaryMax'] ?? '';
$description = $_POST['jobDescription'] ?? '';
$requirements = $_POST['skillsRequired'] ?? '';
$category = $_POST['jobCategory'] ?? '';
$deadline = $_POST['applicationDeadline'] ?? '';

// Defaults
$employer_id = 1;
$status = 'active';

// Insert query
$sql = "INSERT INTO job_postings (
    employer_id, title, description, requirements, category, 
    job_type, work_mode, location, salary_min, salary_max, 
    deadline, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("isssssssssss", 
    $employer_id, $title, $description, $requirements, $category,
    $job_type, $work_mode, $location, $salary_min, $salary_max, $deadline, $status
);

// Execute
if ($stmt->execute()) {
    echo "<script>alert('Job posted successfully!'); window.location.href='job_listings.php';</script>";
} else {
    echo "Error posting job: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
