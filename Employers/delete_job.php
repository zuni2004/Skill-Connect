<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

if (isset($_GET['id'])) {
    $job_id = $conn->real_escape_string($_GET['id']);

    // Prepare SQL DELETE statement
    $sql_delete = "DELETE FROM job_postings WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $job_id); // 'i' for integer type
        if ($stmt_delete->execute()) {
            echo "<script>alert('Job deleted successfully!'); window.location.href='job_listings.php';</script>";
        } else {
            echo "<script>alert('Error deleting job: " . $stmt_delete->error . "'); window.location.href='job_listings.php';</script>";
        }
        $stmt_delete->close();
    } else {
        echo "<script>alert('Error preparing delete statement: " . $conn->error . "'); window.location.href='job_listings.php';</script>";
    }
} else {
    echo "<script>alert('No job ID provided for deletion.'); window.location.href='job_listings.php';</script>";
}

$conn->close(); // Close database connection
?>
