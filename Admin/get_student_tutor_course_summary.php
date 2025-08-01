<?php
// get_student_tutor_course_summary.php
require_once 'db_connect.php';

header('Content-Type: application/json');
session_start();

// PRODUCTION: Uncomment and implement proper authentication
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}
*/

try {
    // Initialize data structure with default values
    $summary_data = [
        'students' => [
            'total' => 0,
            'daily_registrations' => []
        ],
        'tutors' => [
            'total' => 0,
            'daily_registrations' => []
        ],
        'courses' => [
            'total' => 0
        ]
    ];

    // Start transaction for atomic operations
    $pdo->beginTransaction();

    // --- Fetch Student Data ---
    $stmt = $pdo->query("SELECT COUNT(id) AS total FROM students");
    if ($stmt) {
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($counts && isset($counts['total'])) {
            $summary_data['students']['total'] = (int)$counts['total'];
        }
    }

    // Daily student registrations
    $stmt = $pdo->query("SELECT DATE(created_at) AS date, COUNT(id) AS count
                         FROM students
                         WHERE created_at >= CURDATE() - INTERVAL 29 DAY
                         GROUP BY date
                         ORDER BY date ASC");
    if ($stmt) {
        $summary_data['students']['daily_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // --- Fetch Tutor Data ---
    $stmt = $pdo->query("SELECT COUNT(id) AS total FROM tutors");
    if ($stmt) {
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($counts && isset($counts['total'])) {
            $summary_data['tutors']['total'] = (int)$counts['total'];
        }
    }

    // Daily tutor registrations
    $stmt = $pdo->query("SELECT DATE(created_at) AS date, COUNT(id) AS count
                         FROM tutors
                         WHERE created_at >= CURDATE() - INTERVAL 29 DAY
                         GROUP BY date
                         ORDER BY date ASC");
    if ($stmt) {
        $summary_data['tutors']['daily_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // --- Fetch Courses Data ---
    $stmt = $pdo->query("SELECT COUNT(id) AS total FROM courses");
    if ($stmt) {
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($counts && isset($counts['total'])) {
            $summary_data['courses']['total'] = (int)$counts['total'];
        }
    }

    $pdo->commit();

    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => 'Student, Tutor, and Course data fetched successfully.',
        'data' => $summary_data
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in get_student_tutor_course_summary.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while fetching data.'
        // Production: Remove error_details
        // 'error_details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error in get_student_tutor_course_summary.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while fetching data.'
    ]);
}
?>