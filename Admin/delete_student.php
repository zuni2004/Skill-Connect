

<?php
// delete_student.php
// This script handles the deletion of a student record from the 'students' table.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Check if 'student_id' is provided in the POST request
if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];

    try {
        // Start a database transaction to ensure atomicity
        $pdo->beginTransaction();

        // Optional: You could fetch student details here before deleting,
        // for logging purposes or if you needed to move them to an archive.
        // For a simple delete, this select is not strictly necessary.
        // $stmt_select = $pdo->prepare("SELECT first_name, last_name, email FROM students WHERE student_id = ?");
        // $stmt_select->execute([$student_id]);
        // $student_details = $stmt_select->fetch();

        // Prepare and execute the SQL DELETE statement.
        // Using a prepared statement helps prevent SQL injection.
        $stmt_delete = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt_delete->execute([$student_id]);

        // Commit the transaction if the deletion was successful
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);

    } catch (PDOException $e) {
        // If an error occurs, roll back the transaction
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error deleting student: ' . $e->getMessage()]);
    }
} else {
    // If student_id is not provided, return an error message
    echo json_encode(['success' => false, 'message' => 'Invalid request: student_id not provided.']);
}
?>
