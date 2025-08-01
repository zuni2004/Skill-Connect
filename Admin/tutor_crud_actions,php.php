<?php
// tutor_crud_actions.php
// This script handles various tutor management actions:
// delete, ban, unban, update, and approve.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Get the action type from the POST request
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'delete':
            // Delete a tutor from the 'tutors' table
            $tutor_id = $_POST['tutor_id'] ?? null;
            if ($tutor_id) {
                // Before deleting tutor, consider actions for related sessions (e.g., set tutor_id to NULL, cascade delete, or mark sessions as cancelled)
                // For simplicity here, we'll just delete the tutor. If FOREIGN KEY is set to CASCADE, sessions will be deleted too.
                $stmt = $pdo->prepare("DELETE FROM tutors WHERE id = ?");
                $stmt->execute([$tutor_id]);
                echo json_encode(['success' => true, 'message' => 'Tutor deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tutor ID not provided for deletion.']);
            }
            break;

        case 'ban':
            // Move a tutor from 'tutors' to 'banned_tutors'
            $tutor_id = $_POST['tutor_id'] ?? null;
            $reason = $_POST['reason'] ?? 'No reason provided';
            if ($tutor_id) {
                $pdo->beginTransaction();
                // Get tutor details before deleting
                $stmt_select = $pdo->prepare("SELECT name, email FROM tutors WHERE id = ?");
                $stmt_select->execute([$tutor_id]);
                $tutor_details = $stmt_select->fetch();

                if ($tutor_details) {
                    // Insert into banned_tutors table
                    $stmt_insert = $pdo->prepare("INSERT INTO banned_tutors (original_tutor_id, name, email, reason_for_ban) VALUES (?, ?, ?, ?)");
                    $stmt_insert->execute([$tutor_id, $tutor_details['name'], $tutor_details['email'], $reason]);

                    // Delete from tutors table
                    $stmt_delete = $pdo->prepare("DELETE FROM tutors WHERE id = ?");
                    $stmt_delete->execute([$tutor_id]);

                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'Tutor banned successfully.']);
                } else {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Tutor not found for banning.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Tutor ID not provided for banning.']);
            }
            break;

        case 'unban':
            // Remove a user from the 'banned_tutors' table
            // Note: This does NOT re-insert them into the 'tutors' table.
            // They would need to re-register to become an active tutor again.
            $banned_id = $_POST['banned_id'] ?? null;
            if ($banned_id) {
                $stmt = $pdo->prepare("DELETE FROM banned_tutors WHERE banned_id = ?");
                $stmt->execute([$banned_id]);
                echo json_encode(['success' => true, 'message' => 'Tutor unbanned successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Banned ID not provided for unbanning.']);
            }
            break;

        case 'approve':
            // Approve a tutor (set is_approved to TRUE and set approved_at)
            $tutor_id = $_POST['tutor_id'] ?? null;
            if ($tutor_id) {
                $stmt = $pdo->prepare("UPDATE tutors SET is_approved = TRUE, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$tutor_id]);
                echo json_encode(['success' => true, 'message' => 'Tutor approved successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tutor ID not provided for approval.']);
            }
            break;

        case 'update':
            // Update an existing tutor's information
            $tutor_id = $_POST['id'] ?? null; // ID from the form
            $name = $_POST['name'] ?? null;
            $username = $_POST['username'] ?? null;
            $email = $_POST['email'] ?? null;
            $phone_number = $_POST['phone_number'] ?? null;
            $cnic = $_POST['cnic'] ?? null;
            $bio = $_POST['bio'] ?? null;
            $fee_type = $_POST['fee_type'] ?? null;
            $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0; // Cast to int (1 or 0)
            
            if ($tutor_id && $name && $username && $email && $phone_number && $cnic && $fee_type) {
                $sql = "UPDATE tutors SET 
                            name = :name,
                            username = :username,
                            email = :email,
                            phone_number = :phone_number,
                            cnic = :cnic,
                            bio = :bio,
                            fee_type = :fee_type,
                            is_approved = :is_approved";
                
                // Only update approved_at if status changes to approved and it's not already set
                if ($is_approved == 1) {
                    $sql .= ", approved_at = COALESCE(approved_at, NOW())"; // Set if null, otherwise keep existing
                } else {
                    $sql .= ", approved_at = NULL"; // Clear if unapproved
                }

                $sql .= " WHERE id = :tutor_id"; // Use 'id' column for WHERE clause

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':cnic', $cnic);
                $stmt->bindParam(':bio', $bio);
                $stmt->bindParam(':fee_type', $fee_type);
                $stmt->bindParam(':is_approved', $is_approved, PDO::PARAM_INT);
                $stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT); // Bind tutor_id as integer

                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Tutor information updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No changes made or tutor not found.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required tutor data for update.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error in tutor_crud_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in tutor_crud_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
?>
