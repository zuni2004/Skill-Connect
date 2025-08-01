<?php
// Start the session to get user ID
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    echo "<script>alert('You must be logged in as a Job Seeker to apply for jobs.'); window.location.href='../login.html';</script>";
    exit();
}

$job_seeker_id = $_SESSION['user_id']; // Get the logged-in job seeker's ID

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['job_id'])) {
    $job_id = $conn->real_escape_string(htmlspecialchars($_POST['job_id']));
    $applicantName = $conn->real_escape_string(htmlspecialchars($_POST['applicantName']));
    $applicantEmail = $conn->real_escape_string(htmlspecialchars($_POST['applicantEmail']));
    // $applicantPhone = ''; // Removed: No longer handling phone number if column doesn't exist

    // Initialize resume_filepath. It will be updated if a file is uploaded.
    $resume_filepath = ''; // Changed variable name to match DB column 'resume_filepath'

    // Basic validation
    if (empty($applicantName) || empty($applicantEmail) || empty($job_id)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    // Check if the job seeker has already applied for this job
    $sql_check_application = "SELECT id FROM job_applications WHERE job_posting_id = ? AND job_seeker_id = ?";
    $stmt_check = $conn->prepare($sql_check_application);
    if ($stmt_check) {
        $stmt_check->bind_param("ii", $job_id, $job_seeker_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            echo "<script>alert('You have already applied for this job.'); window.location.href='jobseeker_dashboard.php';</script>";
            exit();
        }
        $stmt_check->close();
    } else {
        error_log("Error preparing application check: " . $conn->error);
        echo "<script>alert('An internal error occurred. Please try again.'); window.history.back();</script>";
        exit();
    }

    // Handle Resume Upload (logic copied from process_application.php for consistency)
    if (isset($_FILES['resumeUpload']) && $_FILES['resumeUpload']['error'] == UPLOAD_ERR_OK) { // Changed to 'resumeUpload' to match form
        $target_dir = "uploads/resumes/"; // Ensure this directory exists and is writable
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = basename($_FILES["resumeUpload"]["name"]);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_file_name = uniqid('resume_', true) . '.' . $file_extension;
        $target_file = $target_dir . $unique_file_name;

        $uploadOk = 1;
        $fileType = mime_content_type($_FILES["resumeUpload"]["tmp_name"]);

        if ($_FILES["resumeUpload"]["size"] > 2000000) { // Max 2MB as per form, was 5MB in process_application.php
            echo "<script>alert('Sorry, your file is too large. Max 2MB.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            $uploadOk = 0;
        }

        if($fileType != "application/pdf") { // Form only accepts .pdf
            echo "<script>alert('Sorry, only PDF files are allowed.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["resumeUpload"]["tmp_name"], $target_file)) {
                $resume_filepath = $target_file; // Assign uploaded file path
            } else {
                echo "<script>alert('Sorry, there was an error uploading your resume.'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
                $conn->close();
                exit();
            }
        }
    } else if ($_FILES['resumeUpload']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors if a file was attempted to be uploaded but failed
        echo "<script>alert('File upload error: " . $_FILES['resumeUpload']['error'] . "'); window.location.href='apply_job.php?job_id=" . htmlspecialchars($job_id) . "';</script>";
        $conn->close();
        exit();
    }


    // Insert the application into the database
    $status = 'Pending'; // Default status for new applications
    $application_date = date('Y-m-d H:i:s'); // Current timestamp

    $sql_insert_application = "INSERT INTO job_applications (
                                    job_posting_id, job_seeker_id, applicant_name,
                                    applicant_email, resume_filepath, status, application_date
                                ) VALUES (?, ?, ?, ?, ?, ?, ?)"; // 7 placeholders

    $stmt_insert = $conn->prepare($sql_insert_application);

    if ($stmt_insert) {
        $stmt_insert->bind_param(
            "iisssss", // i: job_posting_id, i: job_seeker_id, s: name, s: email, s: resume_filepath, s: status, s: date (7 types)
            $job_id,
            $job_seeker_id,
            $applicantName,
            $applicantEmail,
            $resume_filepath, // Now correctly binding $resume_filepath
            $status,
            $application_date
        );

        if ($stmt_insert->execute()) {
            echo "<script>alert('Your application has been submitted successfully!'); window.location.href='jobseeker_dashboard.php';</script>";
        } else {
            error_log("Error submitting application: " . $stmt_insert->error);
            echo "<script>alert('Error submitting application: " . $stmt_insert->error . "'); window.history.back();</script>";
        }
        $stmt_insert->close();
    } else {
        error_log("Error preparing insert statement for application: " . $conn->error);
        echo "<script>alert('An internal database error occurred. Please try again.'); window.history.back();</script>";
    }

    $conn->close();

} else if (isset($_GET['job_id'])) {
    // This block is for displaying the application form
    $job_id = $conn->real_escape_string(htmlspecialchars($_GET['job_id']));

    $job_details = null;
    $sql_job_details = "SELECT title, company_name FROM job_postings WHERE id = ?";
    $stmt_job_details = $conn->prepare($sql_job_details);
    if ($stmt_job_details) {
        $stmt_job_details->bind_param("i", $job_id);
        $stmt_job_details->execute();
        $result_job_details = $stmt_job_details->get_result();
        if ($result_job_details->num_rows > 0) {
            $job_details = $result_job_details->fetch_assoc();
        }
        $stmt_job_details->close();
    }

    // You might also want to fetch job seeker's name/email from the session
    $prefill_name = $_SESSION['username'] ?? ''; // Assuming username might be full name or part of it
    $prefill_email = ''; // Keep empty for now if no easy way to get it from session or DB for prefill

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .apply-form-section {
            padding: 3rem 0;
            background-color: #f8f9fa;
        }
        .apply-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 700px;
            margin: 0 auto;
        }
        .apply-title {
            color: #220359;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .form-control {
            height: 48px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            margin-bottom: 1.5rem;
        }
        .form-control:focus {
            border-color: #4906bf;
            box-shadow: 0 0 0 0.25rem rgba(73, 6, 191, 0.15);
        }
        .btn-submit-application {
            background: linear-gradient(135deg, #220359, #4906bf);
            color: white;
            border: none;
            height: 50px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-submit-application:hover {
            background: linear-gradient(135deg, #1a0247, #3a0599);
        }
        .job-info-header {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .job-info-header h5 {
            color: #220359;
            font-weight: 600;
        }
        .job-info-header p {
            margin-bottom: 0;
            color: #495057;
        }
    </style>
</head>
<body>

<section class="apply-form-section">
    <div class="container">
        <div class="apply-card">
            <h2 class="apply-title text-center">Apply for Job</h2>

            <?php if ($job_details): ?>
                <div class="job-info-header text-center">
                    <h5>Applying for: <?php echo htmlspecialchars($job_details['title']); ?></h5>
                    <p>at <?php echo htmlspecialchars($job_details['company_name']); ?></p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center" role="alert">
                    Job details not found.
                </div>
            <?php endif; ?>

            <form action="apply_job.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
                
                <div class="mb-3">
                    <label for="applicantName" class="form-label">Your Full Name</label>
                    <input type="text" class="form-control" id="applicantName" name="applicantName" value="<?php echo htmlspecialchars($prefill_name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="applicantEmail" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="applicantEmail" name="applicantEmail" value="<?php echo htmlspecialchars($prefill_email); ?>" required>
                </div>
                <!-- Removed the applicant phone number field -->
                <!--
                <div class="mb-3">
                    <label for="applicantPhone" class="form-label">Phone Number (Optional)</label>
                    <input type="tel" class="form-control" id="applicantPhone" name="applicantPhone">
                </div>
                -->
                <div class="mb-4">
                    <label for="resumeUpload" class="form-label">Upload Resume (PDF only)</label>
                    <input class="form-control" type="file" id="resumeUpload" name="resumeUpload" accept=".pdf">
                    <small class="text-muted">Max file size 2MB.</small>
                </div>
                
                <button type="submit" class="btn btn-submit-application">Submit Application</button>
            </form>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
} else {
    // If accessed directly without job_id or POST data
    echo "<script>alert('Invalid access. Please select a job to apply.'); window.location.href='job_listings.php';</script>";
    exit();
}
?>
