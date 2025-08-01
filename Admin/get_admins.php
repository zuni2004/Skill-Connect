<?php
// get_admins.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

session_start(); // Start the session at the very beginning

// IMPORTANT: This is a TEMPORARY and INSECURE bypass for development.
// In a real application, you MUST implement a proper login and session management.
// For example, after a successful admin login, you would set a session variable:
// $_SESSION['admin_logged_in'] = true;
// Then, this block would look like:
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login as admin.']);
    exit();
}
*/

try {
    // Corrected: Changed 'FROM admin' to 'FROM admins'
    $sql = "SELECT id, username, email FROM admins ORDER BY username ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Admins fetched successfully.',
        'data' => $admins
    ]);

} catch (PDOException $e) {
    error_log("Error fetching admins: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch admins. Database error.' // More generic message for the frontend
    ]);
}
?>
