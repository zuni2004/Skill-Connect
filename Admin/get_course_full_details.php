
<?php
// --- get_course_full_details.php ---
// This script fetches full details for a single course, including instructor name.
header('Content-Type: application/json');
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => '', 'course' => null];

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    try {
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
            WHERE 
                c.course_id = ?;
        ";
        
        $stmt = $pdo->prepare($sql); 
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC); 

        if ($course) {
            $response['success'] = true;
            $response['course'] = $course;
        } else {
            $response['message'] = 'Course not found.';
        }

    } catch (PDOException $e) { 
        $response['message'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = "General error: " . $e->getMessage();
    } finally {
    }
} else {
    $response['message'] = 'Course ID not provided.';
}

echo json_encode($response);
?>
