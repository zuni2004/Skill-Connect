
<?php
// ban_student.php
// This script handles banning a student: it moves their record from the 'students' table
// to the 'banned_users' table.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Check if 'student_id' is provided in the POST request
if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    // Get the reason for banning, defaulting if not provided
    $reason = isset($_POST['reason']) ? $_POST['reason'] : 'No reason provided';

    try {
        // Start a database transaction to ensure atomicity of operations
        $pdo->beginTransaction();

        // First, fetch the student's details from the 'students' table
        $stmt_select = $pdo->prepare("SELECT first_name, last_name, email FROM students WHERE student_id = ?");
        $stmt_select->execute([$student_id]);
        $student_details = $stmt_select->fetch();

        if ($student_details) {
            // Insert the student's details into the 'banned_users' table
            $stmt_insert = $pdo->prepare("INSERT INTO banned_users (student_id, first_name, last_name, email, reason_for_ban) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->execute([$student_id, $student_details['first_name'], $student_details['last_name'], $student_details['email'], $reason]);

            // Delete the student's record from the 'students' table
            $stmt_delete = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt_delete->execute([$student_id]);

            // Commit the transaction if both operations were successful
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Student banned successfully.']);
        } else {
            // If student was not found in the 'students' table, roll back and report error
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
        }

    } catch (PDOException $e) {
        // If any error occurs, roll back the transaction
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error banning student: ' . $e->getMessage()]);
    }
} else {
    // If student_id is not provided, return an error message
    echo json_encode(['success' => false, 'message' => 'Invalid request: student_id not provided.']);
}
?>