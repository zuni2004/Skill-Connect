<?php
// get_tutoring_data.php
// This script fetches all tutors (active and pending), banned tutors,
// and all tutoring sessions (with tutor and student names).
// It returns all data as a single JSON object.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

try {
    // Fetch all tutors (regardless of approval status, excluding password)
    $stmt_tutors = $pdo->query("SELECT id, name, username, email, phone_number, cnic, bio, fee_type, is_approved, approved_at, created_at FROM tutors ORDER BY id ASC");
    $tutors = $stmt_tutors->fetchAll();

    // Fetch all banned tutors
    $stmt_banned_tutors = $pdo->query("SELECT banned_id, original_tutor_id, name, email, reason_for_ban, banned_at FROM banned_tutors ORDER BY banned_at DESC");
    $banned_tutors = $stmt_banned_tutors->fetchAll();

    // Fetch all tutoring sessions, joining with tutors and students tables to get names
    $stmt_sessions = $pdo->query("
        SELECT 
            ts.session_id,
            ts.subject,
            ts.session_date,
            ts.session_time,
            ts.status,
            ts.created_at,
            t.name AS tutor_name,
            s.first_name AS student_first_name,
            s.last_name AS student_last_name
        FROM 
            tutoring_sessions ts
        JOIN 
            tutors t ON ts.tutor_id = t.id
        JOIN 
            students s ON ts.student_id = s.student_id
        ORDER BY 
            ts.session_date DESC, ts.session_time DESC
    ");
    $sessions = $stmt_sessions->fetchAll();

    // Combine student first_name and last_name into a single 'student_name' field for convenience
    foreach ($sessions as &$session) {
        $session['student_name'] = $session['student_first_name'] . ' ' . $session['student_last_name'];
        unset($session['student_first_name']);
        unset($session['student_last_name']);
    }
    unset($session); // Unset reference after loop

    // Return all data in a single JSON response
    echo json_encode([
        'success' => true, 
        'tutors' => $tutors, 
        'banned_tutors' => $banned_tutors, 
        'sessions' => $sessions
    ]);

} catch (PDOException $e) {
    // If an error occurs, return a JSON error message
    error_log("Error in get_tutoring_data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
