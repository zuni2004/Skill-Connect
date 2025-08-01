<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if required data is set
    if (isset($_POST['applicant_id']) && isset($_POST['new_status']) && isset($_POST['job_id'])) {
        $applicant_id = $conn->real_escape_string($_POST['applicant_id']);
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $job_id = $conn->real_escape_string($_POST['job_id']); // Used for redirecting back

        // Prepare UPDATE statement for job_applications table
        // Ensure 'status' is the correct column name from your job_applications table
        $sql_update = "UPDATE job_applications SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_status, $applicant_id); // 's' for status (string), 'i' for applicant_id (integer)

            if ($stmt_update->execute()) {
                // Success: Redirect back to the view_applicants page for the same job
                echo "<script>alert('Applicant status updated successfully!'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            } else {
                // Error during execution
                echo "<script>alert('Error updating status: " . $stmt_update->error . "'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            }
            $stmt_update->close();
        } else {
            // Error preparing statement
            echo "<script>alert('Error preparing update statement: " . $conn->error . "'); window.location.href='view_applicants.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
        }
    } else {
        // Missing required POST data
        echo "<script>alert('Missing required data for status update.'); window.location.href='job_listings.php';</script>";
    }
} else {
    // If accessed directly without POST method
    echo "<script>alert('Invalid request method.'); window.location.href='job_listings.php';</script>";
}

$conn->close(); // Close database connection
?>
