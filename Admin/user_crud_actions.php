<?php
// user_crud_actions.php
// This script handles various user management actions:
// delete, ban, unban, update, and approve.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Get the action type from the POST request
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    switch ($action) {
        case 'delete':
            // Delete a student from the 'students' table
            $student_id = $_POST['student_id'] ?? null;
            if ($student_id) {
                $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                $stmt->execute([$student_id]);
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student ID not provided for deletion.']);
            }
            break;

        case 'ban':
            // Move a student from 'students' to 'banned_users'
            $student_id = $_POST['student_id'] ?? null;
            $reason = $_POST['reason'] ?? 'No reason provided';
            if ($student_id) {
                $pdo->beginTransaction();
                // Get student details before deleting
                $stmt_select = $pdo->prepare("SELECT first_name, last_name, email FROM students WHERE student_id = ?");
                $stmt_select->execute([$student_id]);
                $student_details = $stmt_select->fetch();

                if ($student_details) {
                    // Insert into banned_users table
                    $stmt_insert = $pdo->prepare("INSERT INTO banned_users (original_student_id, first_name, last_name, email, reason_for_ban) VALUES (?, ?, ?, ?, ?)");
                    $stmt_insert->execute([$student_id, $student_details['first_name'], $student_details['last_name'], $student_details['email'], $reason]);

                    // Delete from students table
                    $stmt_delete = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                    $stmt_delete->execute([$student_id]);

                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'Student banned successfully.']);
                } else {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Student not found for banning.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Student ID not provided for banning.']);
            }
            break;

        case 'unban':
            // Remove a user from the 'banned_users' table
            // Note: This does NOT re-insert them into the 'students' table.
            // They would need to re-register to become an active student again.
            $banned_id = $_POST['banned_id'] ?? null;
            if ($banned_id) {
                $stmt = $pdo->prepare("DELETE FROM banned_users WHERE banned_id = ?");
                $stmt->execute([$banned_id]);
                echo json_encode(['success' => true, 'message' => 'User unbanned successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Banned ID not provided for unbanning.']);
            }
            break;

        case 'approve':
            // Approve a student (set is_approved to TRUE and set approved_at)
            $student_id = $_POST['student_id'] ?? null;
            if ($student_id) {
                $stmt = $pdo->prepare("UPDATE students SET is_approved = TRUE, approved_at = NOW() WHERE student_id = ?");
                $stmt->execute([$student_id]);
                echo json_encode(['success' => true, 'message' => 'Student approved successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student ID not provided for approval.']);
            }
            break;

        case 'update':
            // Update an existing student's information
            $student_id = $_POST['student_id'] ?? null;
            $first_name = $_POST['first_name'] ?? null;
            $last_name = $_POST['last_name'] ?? null;
            $username = $_POST['username'] ?? null;
            $email = $_POST['email'] ?? null;
            $date_of_birth = $_POST['date_of_birth'] ?: null;
            $cnic = $_POST['cnic'] ?: null;
            $age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
            $phone = $_POST['phone'] ?: null;
            $bio = $_POST['bio'] ?: null;
            $academic_history = $_POST['academic_history'] ?: null;
            $country = $_POST['country'] ?: null;
            $province = $_POST['province'] ?: null;
            $city = $_POST['city'] ?: null;
            $area = $_POST['area'] ?: null;
            $street = $_POST['street'] ?: null;
            $postal_code = $_POST['postal_code'] ?: null;
            $agreed_terms = isset($_POST['agreed_terms']) ? (int)$_POST['agreed_terms'] : 0; // Cast to int (1 or 0)
            $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0; // Cast to int (1 or 0)
            
            if ($student_id && $first_name && $last_name && $username && $email) {
                $sql = "UPDATE students SET 
                            first_name = :first_name,
                            last_name = :last_name,
                            username = :username,
                            email = :email,
                            date_of_birth = :date_of_birth,
                            cnic = :cnic,
                            age = :age,
                            phone = :phone,
                            bio = :bio,
                            academic_history = :academic_history,
                            country = :country,
                            province = :province,
                            city = :city,
                            area = :area,
                            street = :street,
                            postal_code = :postal_code,
                            agreed_terms = :agreed_terms,
                            is_approved = :is_approved";
                
                // Only update approved_at if status changes to approved and it's not already set
                if ($is_approved == 1) {
                    $sql .= ", approved_at = COALESCE(approved_at, NOW())"; // Set if null, otherwise keep existing
                } else {
                    $sql .= ", approved_at = NULL"; // Clear if unapproved
                }

                $sql .= " WHERE student_id = :student_id";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':date_of_birth', $date_of_birth);
                $stmt->bindParam(':cnic', $cnic);
                $stmt->bindParam(':age', $age, PDO::PARAM_INT);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':bio', $bio);
                $stmt->bindParam(':academic_history', $academic_history);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':province', $province);
                $stmt->bindParam(':city', $city);
                $stmt->bindParam(':area', $area);
                $stmt->bindParam(':street', $street);
                $stmt->bindParam(':postal_code', $postal_code);
                $stmt->bindParam(':agreed_terms', $agreed_terms, PDO::PARAM_INT);
                $stmt->bindParam(':is_approved', $is_approved, PDO::PARAM_INT);
                $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Student information updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No changes made or student not found.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required student data for update.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error in user_crud_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in user_crud_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
?>
