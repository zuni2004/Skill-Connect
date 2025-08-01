<?php
// get_dashboard_summary.php
require_once 'db_connect.php'; // Ensure this path is correct for your setup

header('Content-Type: application/json');

session_start();

// IMPORTANT: This is a TEMPORARY and INSECURE bypass for development.
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}
*/

try {
    $dashboard_data = [
        'job_seekers' => [
            'total' => 0,
            'pending_approval' => 0,
            'banned' => 0,
            'recent' => [],
            'daily_registrations' => []
        ],
        'employers' => [
            'total' => 0,
            'pending_approval' => 0,
            'banned' => 0,
            'recent' => [],
            'daily_registrations' => []
        ]
    ];

    // --- Fetch Job Seeker Counts ---
    $stmt = $pdo->query("SELECT 
        COUNT(id) AS total, 
        COUNT(CASE WHEN is_approved = FALSE THEN 1 END) AS pending_approval,
        COUNT(CASE WHEN is_banned = TRUE THEN 1 END) AS banned
        FROM job_seekers");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboard_data['job_seekers']['total'] = (int)$counts['total'];
    $dashboard_data['job_seekers']['pending_approval'] = (int)$counts['pending_approval'];
    $dashboard_data['job_seekers']['banned'] = (int)$counts['banned'];

    // --- Fetch Recent Job Seekers ---
    $stmt = $pdo->query("SELECT id, username, created_at FROM job_seekers ORDER BY created_at DESC LIMIT 5");
    $recent_job_seekers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Sanitize and format
    $dashboard_data['job_seekers']['recent'] = array_map(function($js) {
        return [
            'id' => (int)$js['id'],
            'username' => htmlspecialchars($js['username'], ENT_QUOTES, 'UTF-8'),
            'created_at' => $js['created_at']
        ];
    }, $recent_job_seekers);

    // --- Fetch Daily Job Seeker Registrations (Last 30 Days) ---
    $stmt = $pdo->query("SELECT DATE(created_at) AS date, COUNT(id) AS count
                         FROM job_seekers
                         WHERE created_at >= CURDATE() - INTERVAL 29 DAY
                         GROUP BY date
                         ORDER BY date ASC");
    $dashboard_data['job_seekers']['daily_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --- Fetch Employer Counts ---
    $stmt = $pdo->query("SELECT 
        COUNT(id) AS total, 
        COUNT(CASE WHEN is_approved = FALSE THEN 1 END) AS pending_approval,
        COUNT(CASE WHEN is_banned = TRUE THEN 1 END) AS banned
        FROM employers");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboard_data['employers']['total'] = (int)$counts['total'];
    $dashboard_data['employers']['pending_approval'] = (int)$counts['pending_approval'];
    $dashboard_data['employers']['banned'] = (int)$counts['banned'];

    // --- Fetch Recent Employers ---
    $stmt = $pdo->query("SELECT id, organization_name, created_at FROM employers ORDER BY created_at DESC LIMIT 5");
    $recent_employers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Sanitize and format
    $dashboard_data['employers']['recent'] = array_map(function($emp) {
        return [
            'id' => (int)$emp['id'],
            'organization_name' => htmlspecialchars($emp['organization_name'], ENT_QUOTES, 'UTF-8'),
            'created_at' => $emp['created_at']
        ];
    }, $recent_employers);

    // --- Fetch Daily Employer Registrations (Last 30 Days) ---
    $stmt = $pdo->query("SELECT DATE(created_at) AS date, COUNT(id) AS count
                         FROM employers
                         WHERE created_at >= CURDATE() - INTERVAL 29 DAY
                         GROUP BY date
                         ORDER BY date ASC");
    $dashboard_data['employers']['daily_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        'success' => true,
        'message' => 'Dashboard data fetched successfully.',
        'data' => $dashboard_data
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_dashboard_summary.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching dashboard data.',
        'error_details' => $e->getMessage() // For debugging only, remove in production
    ]);
} catch (Exception $e) {
    error_log("General error in get_dashboard_summary.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching dashboard data.'
    ]);
}
?>
