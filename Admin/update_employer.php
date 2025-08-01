<?php
// update_employer.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

session_start(); // Start the session at the very beginning

// IMPORTANT: This is a TEMPORARY and INSECURE bypass for development.
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login as admin.']);
    exit();
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$required_fields = ['id', 'organization_name', 'contact_person', 'email', 'is_approved', 'is_banned'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$id = (int)$data['id'];
$organization_name = htmlspecialchars(trim($data['organization_name']));
$contact_person = htmlspecialchars(trim($data['contact_person']));
$email = htmlspecialchars(trim($data['email']));
$phone = htmlspecialchars(trim($data['phone'] ?? ''));
$address = htmlspecialchars(trim($data['address'] ?? ''));
$website = htmlspecialchars(trim($data['website'] ?? ''));
$is_approved = (bool)$data['is_approved'];
$is_banned = (bool)$data['is_banned'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    // Check if employer exists and get current approval status to manage approved_at
    $check_stmt = $pdo->prepare("SELECT id, is_approved FROM employers WHERE id = ?");
    $check_stmt->execute([$id]);
    $current_employer = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_employer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employer not found.']);
        exit();
    }

    $current_is_approved = (bool)$current_employer['is_approved'];
    $approved_at_update = "";
    $params = [
        $organization_name, $contact_person, $email, $phone, $address, $website, 
        $is_approved ? 1 : 0, $is_banned ? 1 : 0
    ];

    // If changing to approved from not approved, set approved_at to NOW()
    if ($is_approved && !$current_is_approved) {
        $approved_at_update = ", approved_at = NOW()";
    } elseif (!$is_approved && $current_is_approved) {
        // If changing to not approved from approved, set approved_at to NULL
        $approved_at_update = ", approved_at = NULL";
    }


    $sql = "UPDATE employers SET 
                organization_name = ?, 
                contact_person = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                website = ?, 
                is_approved = ?, 
                is_banned = ?
                {$approved_at_update}
            WHERE id = ?";
    
    // Add the ID to the parameters at the end
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Employer updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employer. No changes made or employer not found.']);
    }

} catch (PDOException $e) {
    error_log("Error updating employer: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update employer. Database error.',
        'error_details' => $e->getMessage() // For debugging only, remove in production
    ]);
}
?>
