<?php
// get_job_postings.php
// This script fetches all job postings data.

// Include the database connection file
require_once 'db_connect.php'; // Ensure this file exists and provides $pdo connection

// Set the content type header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'job_postings' => []];

try {
    // Fetch all job postings, including all fields as per your schema
    $sql = "
        SELECT 
            jp.id, 
            jp.employer_id,
            jp.title, 
            jp.description,
            jp.category_id,
            jp.requirements,
            jp.location,
            jp.work_mode,
            jp.application_deadline,
            jp.company_name,
            jp.company_website,
            jp.contact_email,
            jp.post_status,
            jp.is_promoted,
            jp.salary,
            jp.job_type,
            jp.posted_at
        FROM 
            job_postings jp
        ORDER BY jp.posted_at DESC;
    ";
    $stmt = $pdo->query($sql);
    $jobPostings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['job_postings'] = $jobPostings;

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("Error in get_job_postings.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'An unexpected error occurred.';
    error_log("General error in get_job_postings.php: " . $e->getMessage());
} finally {
    echo json_encode($response);
    exit(); 
}
?>
