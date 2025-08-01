<?php
// get_employers.php

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
    $search_term = $_GET['search'] ?? '';
    $approval_filter = $_GET['is_approved'] ?? '';
    $ban_filter = $_GET['is_banned'] ?? '';

    $sql = "SELECT id, username, organization_name, contact_person, email, phone, is_approved, is_banned, created_at FROM employers";
    $conditions = [];
    $params = [];

    // Search condition
    if (!empty($search_term)) {
        $conditions[] = "(organization_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Approval Status Filter
    if ($approval_filter !== '') { // Use !== '' to distinguish empty from '0' or '1'
        if ($approval_filter === 'approved') {
            $conditions[] = "is_approved = TRUE";
        } elseif ($approval_filter === 'pending') {
            $conditions[] = "is_approved = FALSE";
        }
    }

    // Ban Status Filter
    if ($ban_filter !== '') { // Use !== '' to distinguish empty from '0' or '1'
        if ($ban_filter === 'banned') {
            $conditions[] = "is_banned = TRUE";
        } elseif ($ban_filter === 'not_banned') {
            $conditions[] = "is_banned = FALSE";
        }
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY created_at DESC"; // Default sorting by newest first

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output for frontend
    $sanitized_employers = array_map(function($employer) {
        return [
            'id' => (int)$employer['id'],
            'username' => htmlspecialchars($employer['username'] ?? '', ENT_QUOTES, 'UTF-8'),
            'organization_name' => htmlspecialchars($employer['organization_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'contact_person' => htmlspecialchars($employer['contact_person'] ?? '', ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($employer['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($employer['phone'] ?? '', ENT_QUOTES, 'UTF-8'),
            'is_approved' => (bool)$employer['is_approved'],
            'is_banned' => (bool)$employer['is_banned'],
            'created_at' => $employer['created_at'] ?? ''
        ];
    }, $employers);


    echo json_encode([
        'success' => true,
        'message' => 'Employers fetched successfully.',
        'data' => $sanitized_employers
    ]);

} catch (PDOException $e) {
    error_log("Error fetching employers: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch employers. Database error.'
    ]);
}
?>
