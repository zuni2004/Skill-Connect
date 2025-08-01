<?php
// get_job_seekers.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Enable proper error reporting for development. REMOVE OR DISABLE IN PRODUCTION.
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

try {
    // Validate and sanitize input parameters
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    $user_type_filter = isset($_GET['user_type']) ? trim($_GET['user_type']) : '';
    $approval_filter = isset($_GET['is_approved']) ? trim($_GET['is_approved']) : '';
    $ban_filter = isset($_GET['is_banned']) ? trim($_GET['is_banned']) : '';

    // Corrected: Validate user_type against your actual ENUM values
    $valid_user_types = ['', 'student', 'tutor', 'outsider'];
    if ($user_type_filter !== '' && !in_array($user_type_filter, $valid_user_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user type filter provided.']);
        exit();
    }

    // Build the base query - Changed 'name' to 'full_name' as per your database screenshot
    $sql = "SELECT 
                id, 
                username, 
                full_name, 
                email, 
                phone, 
                user_type, 
                is_approved, 
                is_banned, 
                created_at 
            FROM job_seekers";
    
    $conditions = [];
    $params = [];

    // Search condition (case-insensitive) - Updated to search 'full_name'
    if (!empty($search_term)) {
        $conditions[] = "(LOWER(username) LIKE LOWER(?) OR 
                          LOWER(full_name) LIKE LOWER(?) OR 
                          LOWER(email) LIKE LOWER(?))";
        $search_param = '%' . $search_term . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // User Type Filter
    if (!empty($user_type_filter)) {
        $conditions[] = "user_type = ?";
        $params[] = $user_type_filter;
    }

    // Approval Status Filter
    if ($approval_filter !== '') {
        $conditions[] = "is_approved = ?";
        $params[] = ($approval_filter === 'approved') ? 1 : 0; // 1 for TRUE, 0 for FALSE
    }

    // Ban Status Filter
    if ($ban_filter !== '') {
        $conditions[] = "is_banned = ?";
        $params[] = ($ban_filter === 'banned') ? 1 : 0; // 1 for TRUE, 0 for FALSE
    }

    // Combine conditions if any exist
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    // Add sorting
    $sql .= " ORDER BY created_at DESC";

    // --- Debugging the SQL and parameters: IMPORTANT FOR DIAGNOSIS ---
    error_log("SQL Query being prepared: " . $sql);
    error_log("Parameters being passed: " . print_r($params, true));
    // --- End debugging ---

    // Prepare and execute the statement with parameter binding
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($params);
    $job_seekers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output data for display on the frontend - Changed 'name' to 'full_name'
    $sanitized_job_seekers = array_map(function($seeker) {
        return [
            'id' => (int)$seeker['id'],
            'username' => htmlspecialchars($seeker['username'] ?? '', ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($seeker['full_name'] ?? '', ENT_QUOTES, 'UTF-8'), // Output 'full_name' as 'name' for consistency with frontend JS
            'email' => filter_var($seeker['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars($seeker['phone'] ?? '', ENT_QUOTES, 'UTF-8'),
            'user_type' => htmlspecialchars($seeker['user_type'] ?? '', ENT_QUOTES, 'UTF-8'),
            'is_approved' => (bool)$seeker['is_approved'],
            'is_banned' => (bool)$seeker['is_banned'],
            'created_at' => $seeker['created_at'] ?? ''
        ];
    }, $job_seekers);

    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => 'Job seekers fetched successfully',
        'data' => $sanitized_job_seekers,
        'count' => count($sanitized_job_seekers),
        'filters' => [
            'search_term' => $search_term,
            'user_type' => $user_type_filter,
            'approval_status' => $approval_filter,
            'ban_status' => $ban_filter
        ]
    ]);

} catch (PDOException $e) {
    // Log the detailed PDO exception message for server-side debugging
    error_log("Database error in get_job_seekers.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error_details' => $e->getMessage() // Expose details only in development for debugging
    ]);
} catch (Exception $e) {
    // Log any other unexpected exceptions
    error_log("General error in get_job_seekers.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
}
