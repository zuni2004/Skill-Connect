<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize input data
    $job_posting_id = $conn->real_escape_string($_POST['job_posting_id']);
    $applicantName = $conn->real_escape_string(htmlspecialchars($_POST['applicantName']));
    $applicantEmail = $conn->real_escape_string(htmlspecialchars($_POST['applicantEmail']));
    $coverLetter = !empty($_POST['coverLetter']) ? $conn->real_escape_string(htmlspecialchars($_POST['coverLetter'])) : NULL;

    // Hardcode job_seeker_id to 2 for now (from our dummy job_seekers table).
    // In a real system, this would come from the logged-in job seeker's session.
    $job_seeker_id = 2; // Make sure this ID exists in your 'job_seekers' table

    $resume_filepath = NULL; // Initialize to NULL

    // 2. Handle Resume Upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/resumes/"; // Create this directory in your project root!
        // Ensure the directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create recursively and give full permissions
        }

        $file_name = basename($_FILES["resume"]["name"]);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        // Generate a unique file name to prevent overwrites and security issues
        $unique_file_name = uniqid('resume_', true) . '.' . $file_extension;
        $target_file = $target_dir . $unique_file_name;

        $uploadOk = 1;
        $fileType = mime_content_type($_FILES["resume"]["tmp_name"]); // Use mime_content_type for better security

        // Check file size (e.g., 5MB limit)
        if ($_FILES["resume"]["size"] > 5000000) { // 5MB in bytes
            echo "<script>alert('Sorry, your file is too large. Max 5MB.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_posting_id) . "';</script>";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($fileType != "application/pdf" && $fileType != "application/msword" && $fileType != "application/vnd.openxmlformats-officedocument.wordprocessingml.document") {
            echo "<script>alert('Sorry, only PDF, DOC, & DOCX files are allowed.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_posting_id) . "';</script>";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            // Error message already shown by alert
        } else {
            if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
                $resume_filepath = $target_file; // Save path to database
            } else {
                echo "<script>alert('Sorry, there was an error uploading your resume.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_posting_id) . "';</script>";
                $conn->close();
                exit();
            }
        }
    }

    // 3. Insert application data into job_applications table
    $initial_status = 'Pending';

    $sql_insert = "INSERT INTO job_applications (
                        job_posting_id,
                        job_seeker_id,
                        applicant_name,
                        applicant_email,
                        cover_letter,
                        resume_filepath,
                        status
                   ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                   )";
    $stmt_insert = $conn->prepare($sql_insert);

    if ($stmt_insert) {
        $stmt_insert->bind_param(
            "iisssss", // i: job_posting_id, i: job_seeker_id, s: applicantName, s: applicantEmail, s: coverLetter, s: resume_filepath, s: status
            $job_posting_id,
            $job_seeker_id,
            $applicantName,
            $applicantEmail,
            $coverLetter,
            $resume_filepath,
            $initial_status
        );

        if ($stmt_insert->execute()) {
            echo "<script>alert('Your application has been submitted successfully!'); window.location.href='job_listings.php';</script>";
        } else {
            echo "<script>alert('Error submitting application: " . $stmt_insert->error . "'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_posting_id) . "';</script>";
        }
        $stmt_insert->close();
    } else {
        echo "<script>alert('Error preparing application statement: " . $conn->error . "'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_posting_id) . "';</script>";
    }

} else {
    echo "<script>alert('Invalid request method.'); window.location.href='job_listings.php';</script>";
}

$conn->close(); // Close database connection
?>
