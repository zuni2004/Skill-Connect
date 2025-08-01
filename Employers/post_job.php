<?php
// Start the session to access user data
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

// Check if the user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    echo "<script>alert('You must be logged in as an Employer to post jobs.'); window.location.href='login.html';</script>";
    exit();
}

$employer_id = $_SESSION['user_id'];

// Fetch categories for the dropdown
$categories = [];
$sql_categories = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
$result_categories = $conn->query($sql_categories);

if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    // Log error if categories cannot be fetched
    error_log("Error fetching categories: " . $conn->error);
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $title = $conn->real_escape_string(htmlspecialchars($_POST['title']));
    $description = $conn->real_escape_string(htmlspecialchars($_POST['description']));
    $requirements = $conn->real_escape_string(htmlspecialchars($_POST['requirements']));
    $location = $conn->real_escape_string(htmlspecialchars($_POST['location']));
    $work_mode = $conn->real_escape_string(htmlspecialchars($_POST['work_mode']));
    $salary = $conn->real_escape_string(htmlspecialchars($_POST['salary']));
    $job_type = $conn->real_escape_string(htmlspecialchars($_POST['job_type']));
    $application_deadline = $conn->real_escape_string(htmlspecialchars($_POST['application_deadline']));
    $company_name = $conn->real_escape_string(htmlspecialchars($_POST['company_name']));
    $company_website = $conn->real_escape_string(htmlspecialchars($_POST['company_website']));
    $contact_email = $conn->real_escape_string(htmlspecialchars($_POST['contact_email']));
    $category_id = intval($_POST['category_id']); // Ensure this is an integer
    $is_promoted = isset($_POST['is_promoted']) ? 1 : 0; // Checkbox value

    // Basic validation
    if (empty($title) || empty($description) || empty($location) || empty($job_type) || empty($company_name) || empty($contact_email) || empty($category_id)) {
        echo "<script>alert('Please fill in all required fields, including selecting a category.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    // Check if category_id exists in the categories table to prevent foreign key errors
    $sql_check_category = "SELECT category_id FROM categories WHERE category_id = ?";
    $stmt_check_category = $conn->prepare($sql_check_category);
    if ($stmt_check_category) {
        $stmt_check_category->bind_param("i", $category_id);
        $stmt_check_category->execute();
        $result_check_category = $stmt_check_category->get_result();
        if ($result_check_category->num_rows === 0) {
            echo "<script>alert('Invalid category selected. Please choose a valid category.'); window.history.back();</script>";
            $stmt_check_category->close();
            $conn->close();
            exit();
        }
        $stmt_check_category->close();
    } else {
        error_log("Error preparing category check: " . $conn->error);
        echo "<script>alert('An internal error occurred. Please try again.'); window.history.back();</script>";
        $conn->close();
        exit();
    }


    // Insert job posting into the database
    $post_status = 'Active'; // Default status for new posts
    $posted_at = date('Y-m-d H:i:s'); // Current timestamp

    $sql_insert = "INSERT INTO job_postings (
                        employer_id, title, description, requirements, location,
                        work_mode, salary, job_type, application_deadline,
                        company_name, company_website, contact_email,
                        category_id, post_status, posted_at, is_promoted
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_insert);

    if ($stmt) {
        $stmt->bind_param(
            "isssssssssssisss", // Data types for each parameter
            $employer_id,
            $title,
            $description,
            $requirements,
            $location,
            $work_mode,
            $salary,
            $job_type,
            $application_deadline,
            $company_name,
            $company_website,
            $contact_email,
            $category_id,
            $post_status,
            $posted_at,
            $is_promoted
        );

        if ($stmt->execute()) {
            echo "<script>alert('Job posted successfully!'); window.location.href='employer_dashboard.php';</script>";
        } else {
            error_log("Error posting job: " . $stmt->error);
            echo "<script>alert('Error posting job: " . $stmt->error . "'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        error_log("Error preparing insert statement for job posting: " . $conn->error);
        echo "<script>alert('An internal database error occurred. Please try again.'); window.history.back();</script>";
    }

    $conn->close();
    exit(); // Exit after processing POST request

}
// Close connection if no POST request was made (i.e., just displaying the form)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a New Job - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .post-job-section {
            padding: 3rem 0;
            background-color: #f8f9fa;
        }

        .post-job-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .post-job-title {
            color: #220359;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-control,
        .form-select {
            height: 48px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            margin-bottom: 1.5rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4906bf;
            box-shadow: 0 0 0 0.25rem rgba(73, 6, 191, 0.15);
        }

        textarea.form-control {
            height: auto;
            /* Allow textarea to expand */
            min-height: 100px;
            /* Minimum height for description/requirements */
        }

        .btn-post-job {
            background: linear-gradient(135deg, #220359, #4906bf);
            color: white;
            border: none;
            height: 50px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-post-job:hover {
            background: linear-gradient(135deg, #1a0247, #3a0599);
        }

        .form-check-input:checked {
            background-color: #4906bf;
            border-color: #4906bf;
        }
    </style>
</head>

<body>

    <section class="post-job-section">
        <div class="container">
            <div class="post-job-card">
                <a href="employer_dashboard.php" class="btn btn-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <h2 class="post-job-title text-center">Post a New Job Opportunity</h2>
                <form action="post_job.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements (comma-separated)</label>
                        <input type="text" class="form-control" id="requirements" name="requirements"
                            placeholder="e.g., 3+ years experience, Strong communication, Problem-solving skills">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="work_mode" class="form-label">Work Mode</label>
                                <select class="form-select" id="work_mode" name="work_mode" required>
                                    <option value="">Select Work Mode</option>
                                    <option value="On-site">On-site</option>
                                    <option value="Remote">Remote</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary (e.g., $50,000 - $70,000/year)</label>
                                <input type="text" class="form-control" id="salary" name="salary">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="job_type" class="form-label">Job Type</label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Select Job Type</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Temporary">Temporary</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="application_deadline" class="form-label">Application Deadline</label>
                        <input type="date" class="form-control" id="application_deadline" name="application_deadline">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_website" class="form-label">Company Website (Optional)</label>
                                <input type="url" class="form-control" id="company_website" name="company_website">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Job Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_promoted" name="is_promoted" value="1">
                        <label class="form-check-label" for="is_promoted">Promote this job (Featured listing)</label>
                    </div>

                    <button type="submit" class="btn btn-post-job">Post Job</button>
                </form>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>