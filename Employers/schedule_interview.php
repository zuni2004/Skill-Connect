<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if required data is set
    if (isset($_POST['applicant_id']) && isset($_POST['interview_date']) && isset($_POST['interview_time']) && isset($_POST['job_id'])) {
        $applicant_id = $conn->real_escape_string($_POST['applicant_id']);
        $interview_date = $conn->real_escape_string($_POST['interview_date']);
        $interview_time = $conn->real_escape_string($_POST['interview_time']);
        $job_id = $conn->real_escape_string($_POST['job_id']); // Used for redirecting back

        // If date or time are empty, set them to NULL in DB
        $interview_date = !empty($interview_date) ? $interview_date : NULL;
        $interview_time = !empty($interview_time) ? $interview_time : NULL;

        // Prepare UPDATE statement for job_applications table
        $sql_update = "UPDATE job_applications SET
                        interview_date = ?,
                        interview_time = ?
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            // 's' for date (string), 's' for time (string), 'i' for applicant_id (integer)
            $stmt_update->bind_param("ssi", $interview_date, $interview_time, $applicant_id);

            if ($stmt_update->execute()) {
                // Success: Redirect back to the view_applicants page for the same job
                echo "<script>alert('Interview scheduled successfully!'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            } else {
                // Error during execution
                echo "<script>alert('Error scheduling interview: " . $stmt_update->error . "'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            }
            $stmt_update->close();
        } else {
            // Error preparing statement
            echo "<script>alert('Error preparing interview scheduling statement: " . $conn->error . "'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
        }
    } else {
        // Missing required POST data
        echo "<script>alert('Missing required data for interview scheduling.'); window.location.href='job_listings.php';</script>";
    }
} else {
    // If accessed directly without POST method
    echo "<script>alert('Invalid request method.'); window.location.href='job_listings.php';</script>";
}

$conn->close(); // Close database connection
?>
