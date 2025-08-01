<?php
// update_job_seeker.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Enable error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication (uncomment in production)
/*
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit();
}

// Get and validate input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
// Changed 'name' to 'full_name' for backend validation as we will map frontend 'name' to backend 'full_name'
$required_fields = ['id', 'name', 'email', 'user_type', 'is_approved', 'is_banned']; 
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize and validate input
$id = (int)$data['id'];
$name = trim($data['name']); // This is the 'name' sent from the frontend HTML form
$email = trim($data['email']);
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$user_type = trim($data['user_type']);
$is_approved = (bool)$data['is_approved'];
$is_banned = (bool)$data['is_banned'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate user_type against allowed values
$allowed_user_types = ['student', 'tutor', 'outsider'];
if (!in_array($user_type, $allowed_user_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user type specified']);
    exit();
}

try {
    // Check if job seeker exists first
    $check_stmt = $pdo->prepare("SELECT id, is_approved FROM job_seekers WHERE id = ?");
    $check_stmt->execute([$id]);
    $job_seeker_current = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job_seeker_current) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job seeker not found']);
        exit();
    }

    // Determine if approved_at needs to be updated
    $current_is_approved_status = (bool)$job_seeker_current['is_approved'];
    $update_approved_at = ($is_approved === true && $current_is_approved_status === false) ? "NOW()" : "approved_at";

    // Build the update query - Changed 'name' to 'full_name'
    $sql = "UPDATE job_seekers SET 
                full_name = ?,  -- Corrected column name to 'full_name'
                email = ?, 
                phone = ?, 
                user_type = ?, 
                is_approved = ?, 
                is_banned = ?,
                approved_at = " . $update_approved_at . "
            WHERE id = ?";
    
    $params = [
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), // 'name' from frontend maps to 'full_name' in DB
        filter_var($email, FILTER_SANITIZE_EMAIL),
        $phone ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : null,
        htmlspecialchars($user_type, ENT_QUOTES, 'UTF-8'),
        $is_approved ? 1 : 0,
        $is_banned ? 1 : 0,
        $id
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Job seeker updated successfully',
            'changes' => [
                'name' => $name, // Return the name as used in frontend
                'email' => $email,
                'user_type' => $user_type,
                'is_approved' => $is_approved,
                'is_banned' => $is_banned
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes made to job seeker',
            'debug' => 'Data may be identical to existing record'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in update_job_seeker.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage() // Keep for development, remove in production
    ]);
} catch (Exception $e) {
    error_log("General error in update_job_seeker.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
}
