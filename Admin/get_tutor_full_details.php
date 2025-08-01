<?php
// get_tutor_full_details.php
// This script fetches all details for a single tutor by their ID.
// It is used to populate both the 'Edit Tutor' and 'View Tutor Details' modals.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Get the tutor_id from the GET request
$tutor_id = isset($_GET['tutor_id']) ? $_GET['tutor_id'] : null;

if ($tutor_id) {
    try {
        // Prepare the SQL query to select all columns for a specific tutor ID, excluding password
        $stmt = $pdo->prepare("SELECT id, name, username, email, phone_number, cnic, bio, fee_type, is_approved, approved_at, created_at FROM tutors WHERE id = ?");
        $stmt->execute([$tutor_id]);
        
        // Fetch the tutor's details
        $tutor = $stmt->fetch();

        if ($tutor) {
            // Return success with the tutor data
            echo json_encode(['success' => true, 'tutor' => $tutor]);
        } else {
            // Return failure if tutor not found
            echo json_encode(['success' => false, 'message' => 'Tutor not found.']);
        }

    } catch (PDOException $e) {
        // Return JSON error message on database error
        error_log("Error in get_tutor_full_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Return error if tutor_id is not provided
    echo json_encode(['success' => false, 'message' => 'Invalid request: tutor_id not provided.']);
}
?>
