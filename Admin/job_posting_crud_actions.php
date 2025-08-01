<?php
// job_posting_crud_actions.php
// This script handles CRUD operations for job postings.

// Include the database connection file
require_once 'db_connect.php'; // Ensure this file exists and provides $pdo connection

// Set the content type header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'add':
                // Required fields for adding a job posting
                $employer_id = $_POST['employer_id'] ?? null; // IMPORTANT: Ensure this is a valid employer ID from your 'employers' table
                $title = $_POST['title'] ?? null;
                $description = $_POST['description'] ?? null;
                $category_id = $_POST['category_id'] ?? null;
                $requirements = $_POST['requirements'] ?? null;
                $location = $_POST['location'] ?? null;
                $work_mode = $_POST['work_mode'] ?? null;
                $application_deadline = $_POST['application_deadline'] ?? null;
                $company_name = $_POST['company_name'] ?? null;
                $company_website = $_POST['company_website'] ?? null;
                $contact_email = $_POST['contact_email'] ?? null;
                $salary = $_POST['salary'] ?? null;
                $job_type = $_POST['job_type'] ?? null;
                $is_promoted = isset($_POST['is_promoted']) ? (int)$_POST['is_promoted'] : 0;
                
                // Set initial status to 'Pending' for new posts, admin will approve
                $post_status = 'Pending'; 

                // Validate required fields
                if ($employer_id && $title && $description && $category_id && $requirements && $work_mode && $company_name && $contact_email && $job_type) {
                    $stmt = $pdo->prepare("
                        INSERT INTO job_postings (
                            employer_id, title, description, category_id, requirements, location, 
                            work_mode, application_deadline, company_name, company_website, 
                            contact_email, post_status, is_promoted, salary, job_type
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )
                    ");
                    $stmt->execute([
                        $employer_id, $title, $description, $category_id, $requirements, $location, 
                        $work_mode, $application_deadline, $company_name, $company_website, 
                        $contact_email, $post_status, $is_promoted, $salary, $job_type
                    ]);
                    $response['success'] = true;
                    $response['message'] = 'Job Listing posted successfully and is awaiting approval!';
                } else {
                    $response['message'] = 'Missing required fields for posting a job.';
                }
                break;

            case 'update':
                $id = $_POST['id'] ?? null;
                // Collect all fields, even if they are null, to ensure they are passed to the update query
                $employer_id = $_POST['employer_id'] ?? null;
                $title = $_POST['title'] ?? null;
                $description = $_POST['description'] ?? null;
                $category_id = $_POST['category_id'] ?? null;
                $requirements = $_POST['requirements'] ?? null;
                $location = $_POST['location'] ?? null;
                $work_mode = $_POST['work_mode'] ?? null;
                $application_deadline = $_POST['application_deadline'] ?? null;
                $company_name = $_POST['company_name'] ?? null;
                $company_website = $_POST['company_website'] ?? null;
                $contact_email = $_POST['contact_email'] ?? null;
                $post_status = $_POST['post_status'] ?? null;
                $is_promoted = isset($_POST['is_promoted']) ? (int)$_POST['is_promoted'] : 0; // Checkbox, will be '1' or '0'
                $salary = $_POST['salary'] ?? null;
                $job_type = $_POST['job_type'] ?? null;
                
                if ($id) {
                    $setSql = [];
                    $params = [];

                    // Dynamically build SET clause for update
                    if ($employer_id !== null) { $setSql[] = "employer_id = ?"; $params[] = $employer_id; }
                    if ($title !== null) { $setSql[] = "title = ?"; $params[] = $title; }
                    if ($description !== null) { $setSql[] = "description = ?"; $params[] = $description; }
                    if ($category_id !== null) { $setSql[] = "category_id = ?"; $params[] = $category_id; }
                    if ($requirements !== null) { $setSql[] = "requirements = ?"; $params[] = $requirements; }
                    if ($location !== null) { $setSql[] = "location = ?"; $params[] = $location; }
                    if ($work_mode !== null) { $setSql[] = "work_mode = ?"; $params[] = $work_mode; }
                    if ($application_deadline !== null) { $setSql[] = "application_deadline = ?"; $params[] = $application_deadline; }
                    if ($company_name !== null) { $setSql[] = "company_name = ?"; $params[] = $company_name; }
                    // Handle empty string for optional company_website to allow clearing
                    // Use array_key_exists to check if the key was *sent* in the POST, even if empty
                    if (array_key_exists('company_website', $_POST)) { $setSql[] = "company_website = ?"; $params[] = $_POST['company_website']; }
                    if ($contact_email !== null) { $setSql[] = "contact_email = ?"; $params[] = $contact_email; }
                    if ($post_status !== null) { $setSql[] = "post_status = ?"; $params[] = $post_status; }
                    
                    // Always include is_promoted as its value can be 0 or 1
                    $setSql[] = "is_promoted = ?"; $params[] = $is_promoted; 

                    // Handle empty string for optional salary to allow clearing
                    if (array_key_exists('salary', $_POST)) { $setSql[] = "salary = ?"; $params[] = $_POST['salary']; }
                    if ($job_type !== null) { $setSql[] = "job_type = ?"; $params[] = $job_type; }

                    if (empty($setSql)) {
                        $response['message'] = 'No data provided for update.';
                        break;
                    }

                    $sql = "UPDATE job_postings SET " . implode(', ', $setSql) . " WHERE id = ?";
                    $params[] = $id;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Job Listing updated successfully!';
                    } else {
                        $response['message'] = 'No changes made or Job Listing not found.';
                    }
                } else {
                    $response['message'] = 'Job ID not provided for update.';
                }
                break;

            case 'delete': 
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $pdo->prepare("DELETE FROM job_postings WHERE id = ?");
                    $stmt->execute([$id]);
                    $response['success'] = true;
                    $response['message'] = 'Job Listing deleted successfully.';
                } else {
                    $response['message'] = 'Job ID not provided for deletion.';
                }
                break;

            case 'approve':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE job_postings SET post_status = 'Active' WHERE id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Job Listing approved and set to Active!';
                    } else {
                        $response['message'] = 'Job Listing not found or already active.';
                    }
                } else {
                    $response['message'] = 'Job ID not provided for approval.';
                }
                break;

            case 'expire':
                $id = $_POST['id'] ?? null;
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE job_postings SET post_status = 'Expired' WHERE id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Job Listing expired successfully!';
                    } else {
                        $response['message'] = 'Job Listing not found or already expired.';
                    }
                } else {
                    $response['message'] = 'Job ID not provided for expiring.';
                }
                break;

            default:
                $response['message'] = 'Invalid action specified.';
                break;
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        error_log("Error in job_posting_crud_actions.php: " . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred.';
        error_log("General error in job_posting_crud_actions.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method or action not specified.';
}
echo json_encode($response);
exit(); 
?>
