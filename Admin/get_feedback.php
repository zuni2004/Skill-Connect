<?php
// get_feedback.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

session_start(); // Start the session at the very beginning

// IMPORTANT: This is a TEMPORARY and INSECURE bypass for development.
// In a real application, you MUST implement a proper login and session management.
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login as admin.']);
    exit();
}
*/

try {
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $sort_by = $_GET['sort'] ?? 'submitted_at_desc';

    $sql = "SELECT id, student_id, tutor_id, description, submitted_at, reply, replied_at, read_by_admin FROM feedback";
    $conditions = [];
    $params = [];

    // Search condition
    if (!empty($search)) {
        $conditions[] = "(description LIKE ? OR CAST(student_id AS CHAR) LIKE ? OR CAST(tutor_id AS CHAR) LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Status filter condition
    if (!empty($status_filter)) {
        if ($status_filter === 'unread') {
            $conditions[] = "read_by_admin = FALSE AND reply IS NULL";
        } elseif ($status_filter === 'read') {
            $conditions[] = "read_by_admin = TRUE AND reply IS NULL";
        } elseif ($status_filter === 'replied') {
            $conditions[] = "reply IS NOT NULL";
        }
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    // Sorting
    if ($sort_by === 'submitted_at_asc') {
        $sql .= " ORDER BY submitted_at ASC";
    } else { // Default to submitted_at_desc
        $sql .= " ORDER BY submitted_at DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedback_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Feedback fetched successfully.',
        'data' => $feedback_data
    ]);

} catch (PDOException $e) {
    error_log("Error fetching feedback: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch feedback. Database error.'
    ]);
}
?>
