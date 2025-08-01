<?php


// --- get_course_data.php ---
// This script fetches all courses (approved and pending) and their instructor names.
header('Content-Type: application/json');
// Using PDO for consistency with course_crud_actions.php (from earlier responses)
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => '', 'courses' => []];

try {
    // Prepare SQL to fetch courses and join with tutors to get instructor name
    // Assumes 'tutors' table has 'id' and 'name' columns and 'courses' table has 'subject'
    $sql = "
        SELECT 
            c.course_id, 
            c.title, 
            COALESCE(t.name, 'N/A') AS tutor_name, 
            c.subject, -- Explicitly selecting 'subject' column
            c.location,
            c.mode,
            c.fee_type,
            c.fee_amount,
            c.tutor_id,
            c.rating,
            c.description,
            c.image_url,
            c.is_approved,
            c.is_active, 
            c.approved_at,
            c.created_at
        FROM 
            courses c
        LEFT JOIN 
            tutors t ON c.tutor_id = t.id
        ORDER BY c.created_at DESC;
    ";
    
    $stmt = $pdo->prepare($sql); 
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC); 

    $response['success'] = true;
    $response['courses'] = $courses;

} catch (PDOException $e) { 
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = "General error: " . $e->getMessage();
} finally {
    echo json_encode($response);
}
?>