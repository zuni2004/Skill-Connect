<?php
// --- course_crud_actions.php ---
// This script handles CRUD operations for courses: add, update, delete, approve, reject, deactivate.
header('Content-Type: application/json');
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => 'Invalid request.'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'add':
                $title = $_POST['title'] ?? null;
                $tutor_id = $_POST['tutor_id'] ?? null;
                $subject = $_POST['category'] ?? null; // HTML sends 'category' in name attribute, PHP uses 'subject' for DB
                $location = $_POST['location'] ?? null;
                $mode = $_POST['mode'] ?? null;
                $fee_type = $_POST['fee_type'] ?? null;
                $fee_amount = $_POST['fee_amount'] ?? 0.00;
                $description = $_POST['description'] ?? null;
                $image_url = $_POST['image_url'] ?? null;
                $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0;
                $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
                
                $approved_at = ($is_approved == 1) ? date('Y-m-d H:i:s') : null;

                if ($title && $subject && $mode && $fee_type && $tutor_id !== null) {
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (title, subject, location, mode, fee_type, fee_amount, tutor_id, description, image_url, is_approved, is_active, approved_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $subject, $location, $mode, $fee_type, $fee_amount, $tutor_id, $description, $image_url, $is_approved, $is_active, $approved_at
                    ]);
                    $response['success'] = true;
                    $response['message'] = 'Course added successfully!';
                } else {
                    $response['message'] = 'Missing required course data for addition.';
                }
                break;

            case 'update':
                $course_id = $_POST['course_id'] ?? null;
                $title = $_POST['title'] ?? null;
                $subject = $_POST['category'] ?? null;
                $location = $_POST['location'] ?? null;
                $mode = $_POST['mode'] ?? null;
                $fee_type = $_POST['fee_type'] ?? null;
                $fee_amount = $_POST['fee_amount'] ?? 0.00;
                $tutor_id = $_POST['tutor_id'] ?? null;
                $rating = $_POST['rating'] ?? null;
                $description = $_POST['description'] ?? null;
                $image_url = $_POST['image_url'] ?? null;
                $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0;
                $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

                if ($course_id && $title && $subject && $mode && $fee_type && $tutor_id !== null) {
                    $sql = "UPDATE courses SET 
                                title = :title,
                                subject = :subject,
                                location = :location,
                                mode = :mode,
                                fee_type = :fee_type,
                                fee_amount = :fee_amount,
                                tutor_id = :tutor_id,
                                rating = :rating,
                                description = :description,
                                image_url = :image_url,
                                is_approved = :is_approved,
                                is_active = :is_active";
                    
                    if ($is_approved == 1) {
                        $sql .= ", approved_at = COALESCE(approved_at, NOW())";
                    } else {
                        $sql .= ", approved_at = NULL";
                    }

                    $sql .= " WHERE course_id = :course_id";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':location', $location);
                    $stmt->bindParam(':mode', $mode);
                    $stmt->bindParam(':fee_type', $fee_type);
                    $stmt->bindParam(':fee_amount', $fee_amount);
                    $stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
                    $stmt->bindParam(':rating', $rating);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':image_url', $image_url);
                    $stmt->bindParam(':is_approved', $is_approved, PDO::PARAM_INT);
                    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
                    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);

                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Course information updated successfully!';
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'No changes made or course not found.';
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Missing required course data for update.';
                }
                break;

            case 'delete':
                $course_id = $_POST['course_id'] ?? null;
                if ($course_id) {
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $response['success'] = true;
                    $response['message'] = 'Course deleted successfully.';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Course ID not provided for deletion.';
                }
                break;

            case 'approve':
                $course_id = $_POST['course_id'] ?? null;
                if ($course_id) {
                    $stmt = $pdo->prepare("UPDATE courses SET is_approved = TRUE, approved_at = NOW() WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $response['success'] = true;
                    $response['message'] = 'Course approved successfully!';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Course ID not provided for approval.';
                }
                break;

            case 'reject':
                $course_id = $_POST['course_id'] ?? null;
                if ($course_id) {
                    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $response['success'] = true;
                    $response['message'] = 'Course rejected and deleted successfully.';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Course ID not provided for rejection.';
                }
                break;

            case 'activate':
                $course_id = $_POST['course_id'] ?? null;
                if ($course_id) {
                    $stmt = $pdo->prepare("UPDATE courses SET is_active = TRUE WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $response['success'] = true;
                    $response['message'] = 'Course activated successfully!';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Course ID not provided for activation.';
                }
                break;

            case 'deactivate':
                $course_id = $_POST['course_id'] ?? null;
                if ($course_id) {
                    $stmt = $pdo->prepare("UPDATE courses SET is_active = FALSE WHERE course_id = ?");
                    $stmt->execute([$course_id]);
                    $response['success'] = true;
                    $response['message'] = 'Course deactivated successfully!';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Course ID not provided for deactivation.';
                }
                break;

            default:
                $response['message'] = 'Invalid action specified.';
                break;
        }
    }
} catch (PDOException $e) {
    error_log("Error in course_crud_actions.php: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in course_crud_actions.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>