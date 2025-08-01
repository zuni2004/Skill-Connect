<?php
// get_job_posting_full_details.php
// This script fetches full details for a single job posting.

// Include the database connection file
require_once 'db_connect.php'; // Ensure this file exists and provides $pdo connection

// Set the content type header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'job_posting' => null];

$job_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($job_id) {
    try {
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
            WHERE 
                jp.id = ?;
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$job_id]);
        $job_posting = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job_posting) {
            $response['success'] = true;
            $response['job_posting'] = $job_posting;
        } else {
            $response['message'] = 'Job posting not found.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Error in get_job_posting_full_details.php: " . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred.';
        error_log("General error in get_job_posting_full_details.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Job ID not provided.';
}

echo json_encode($response);
exit(); 
?>
