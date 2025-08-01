<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

$job = null;
$job_id = null;
$message = '';
$message_type = ''; // 'success' or 'danger'

// Handle form submission for updating job
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['job_id'])) {
    $job_id = $conn->real_escape_string($_POST['job_id']);

    // Sanitize and validate input
    $jobTitle = $conn->real_escape_string(htmlspecialchars($_POST['jobTitle']));
    $jobType = $conn->real_escape_string(htmlspecialchars($_POST['jobType']));
    $locationType = $conn->real_escape_string(htmlspecialchars($_POST['locationType']));
    $location = $conn->real_escape_string(htmlspecialchars($_POST['location']));
    $salary = $conn->real_escape_string(htmlspecialchars($_POST['salary']));
    $jobDescription = $conn->real_escape_string(htmlspecialchars($_POST['jobDescription']));
    $skillsRequired = $conn->real_escape_string(htmlspecialchars($_POST['skillsRequired']));
    $applicationDeadline = !empty($_POST['applicationDeadline']) ? $conn->real_escape_string($_POST['applicationDeadline']) : NULL;
    $companyName = $conn->real_escape_string(htmlspecialchars($_POST['companyName']));
    $companyWebsite = !empty($_POST['companyWebsite']) ? $conn->real_escape_string(htmlspecialchars($_POST['companyWebsite'])) : NULL;
    $contactEmail = $conn->real_escape_string(htmlspecialchars($_POST['contactEmail']));
    $postStatus = $conn->real_escape_string(htmlspecialchars($_POST['postStatus']));
    $isPromoted = isset($_POST['isPromoted']) ? 1 : 0; // Checkbox value

    // For simplicity, category_id and employer_id are not editable in this form.
    // They would typically be managed elsewhere or pre-selected from DB.
    // We'll update the other fields based on the form input.

    $sql_update = "UPDATE job_postings SET
                    title = ?,
                    description = ?,
                    requirements = ?,
                    location = ?,
                    work_mode = ?,
                    application_deadline = ?,
                    company_name = ?,
                    company_website = ?,
                    contact_email = ?,
                    salary = ?,
                    job_type = ?,
                    post_status = ?,
                    is_promoted = ?
                   WHERE id = ?";

    $stmt_update = $conn->prepare($sql_update);

    if ($stmt_update) {
        $stmt_update->bind_param(
            "ssssssssssssii", // **FIXED:** 12 's' for strings, 2 'i' for integers (isPromoted, job_id)
            $jobTitle,
            $jobDescription,
            $skillsRequired,
            $location,
            $locationType,
            $applicationDeadline,
            $companyName,
            $companyWebsite,
            $contactEmail,
            $salary,
            $jobType,
            $postStatus,
            $isPromoted,
            $job_id
        );

        if ($stmt_update->execute()) {
            $message = 'Job updated successfully!';
            $message_type = 'success';
            // After successful update, fetch the updated job details to refresh the form
            // This ensures the form displays the very latest data
        } else {
            $message = 'Error updating job: ' . $stmt_update->error;
            $message_type = 'danger';
        }
        $stmt_update->close();
    } else {
        $message = 'Error preparing update statement: ' . $conn->error;
        $message_type = 'danger';
    }
}

// Fetch job details to display in the form (either initial load or after update)
if (isset($_GET['id']) || isset($_POST['job_id'])) {
    $job_id_to_fetch = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : $conn->real_escape_string($_POST['job_id']);

    $sql_fetch = "SELECT * FROM job_postings WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);

    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $job_id_to_fetch);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows > 0) {
            $job = $result_fetch->fetch_assoc();
            $job_id = $job['id']; // Set job_id for the form
        } else {
            $message = 'Job not found.';
            $message_type = 'danger';
        }
        $stmt_fetch->close();
    } else {
        $message = 'Error preparing fetch statement: ' . $conn->error;
        $message_type = 'danger';
    }
} else {
    $message = 'No job ID provided.';
    $message_type = 'danger';
}

$conn->close(); // Close connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Job | SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body { transition: background 0.3s, color 0.3s; }
        .navbar { padding: 1rem 2rem; }
        .navbar-nav .nav-link { font-weight: 600; margin: 0 10px; }
        .nav-icons i { font-size: 1.3rem; margin-right: 15px; color: #1e1e2f; cursor: pointer; }
        .nav-buttons .btn { margin-left: 10px; }
        .navbar-brand img { height: 80px; }
        .job-form-section { padding: 5rem 0; }
        .form-card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-header { background: linear-gradient(to right, #220359, #4906bf); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; }
    </style>
</head>
<body>

<!-- Same Navbar as landing_page.html -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="./logo.jpeg" alt="SkillConnect Logo"/>
    </a>

    <div class="collapse navbar-collapse justify-content-between">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="./landing_page.html">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="./job_listings.php">All Jobs</a></li>
        <li class="nav-item"><a class="nav-link" href="#">All Courses</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Services
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="./registration_form_student.html">Tutoring</a></li>
            <li><a class="dropdown-item" href="./registration_form_jobSeekers_Employeers.html">Job Matching</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="./faq.html">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="./aboutUs.html">About Us</a></li>
      </ul>

      <div class="d-flex align-items-center nav-icons">
        <i class="bi bi-search"></i>
        <span class="me-2">0.0 $</span>
        <i class="bi bi-bag"></i>
        <i class="bi bi-person-circle"></i>
        <div class="nav-buttons">
          <a href="./login.html" class="btn btn-primary">Login</a>
          <a href="./registration_form_jobSeekers_Employeers.html" class="btn btn-outline-primary">Register</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Job Editing Form Section -->
<section class="job-form-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card border-0">
                    <div class="form-header text-center">
                        <h2 class="fw-bold mb-0"><?php echo $job ? 'Edit Job Opportunity' : 'Job Not Found'; ?></h2>
                    </div>
                    <div class="card-body p-5">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($job): ?>
                        <form id="jobEditingForm" action="edit_job.php" method="POST">
                            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['id']); ?>">
                            
                            <div class="mb-4">
                                <label for="jobTitle" class="form-label fw-bold">Job Title*</label>
                                <input type="text" class="form-control" id="jobTitle" name="jobTitle" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="jobType" class="form-label fw-bold">Job Type*</label>
                                    <select class="form-select" id="jobType" name="jobType" required>
                                        <?php
                                        $job_types = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'];
                                        foreach ($job_types as $type) {
                                            $selected = ($job['job_type'] == $type) ? 'selected' : '';
                                            echo "<option value='{$type}' {$selected}>{$type}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="locationType" class="form-label fw-bold">Location Type*</label>
                                    <select class="form-select" id="locationType" name="locationType" required>
                                        <?php
                                        $location_types = ['Remote', 'On-site', 'Hybrid'];
                                        foreach ($location_types as $type) {
                                            $selected = ($job['work_mode'] == $type) ? 'selected' : '';
                                            echo "<option value='{$type}' {$selected}>{$type}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="location" class="form-label fw-bold">Location (if not remote)</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="salary" class="form-label fw-bold">Salary Range</label>
                                <input type="text" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary']); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="jobDescription" class="form-label fw-bold">Job Description*</label>
                                <textarea class="form-control" id="jobDescription" name="jobDescription" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="skillsRequired" class="form-label fw-bold">Skills Required*</label>
                                <textarea class="form-control" id="skillsRequired" name="skillsRequired" rows="3" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="applicationDeadline" class="form-label fw-bold">Application Deadline</label>
                                <input type="date" class="form-control" id="applicationDeadline" name="applicationDeadline" value="<?php echo htmlspecialchars($job['application_deadline']); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="companyName" class="form-label fw-bold">Company Name*</label>
                                <input type="text" class="form-control" id="companyName" name="companyName" value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="companyWebsite" class="form-label fw-bold">Company Website</label>
                                <input type="url" class="form-control" id="companyWebsite" name="companyWebsite" value="<?php echo htmlspecialchars($job['company_website']); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="contactEmail" class="form-label fw-bold">Contact Email*</label>
                                <input type="email" class="form-control" id="contactEmail" name="contactEmail" value="<?php echo htmlspecialchars($job['contact_email']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="postStatus" class="form-label fw-bold">Job Status*</label>
                                <select class="form-select" id="postStatus" name="postStatus" required>
                                    <?php
                                    $status_options = ['Active', 'Expired', 'Filled'];
                                    foreach ($status_options as $status) {
                                        $selected = ($job['post_status'] == $status) ? 'selected' : '';
                                        echo "<option value='{$status}' {$selected}>{$status}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="isPromoted" name="isPromoted" <?php echo $job['is_promoted'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="isPromoted">Promote Job (Featured Listing)</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Update Job Opportunity</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger" role="alert">
                                Job details could not be loaded. Please ensure a valid job ID is provided.
                            </div>
                            <a href="job_listings.php" class="btn btn-secondary">Back to Job Listings</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Same Footer as landing_page.html -->
<footer class="bg-dark text-white pt-5 pb-4">
  <div class="container">
    <div class="row">
      <div class="col-lg-4 mb-4">
        <a class="navbar-brand d-flex align-items-center mb-3" href="#">
          <img src="./logo.jpeg" alt="SkillConnect Logo" height="60">
        </a>
        <p class="text-muted">Temporary minds. Share skills. Shape the future – with skillConnect.</p>
        <p class="text-muted small">© 2023 SkillConnect. All rights reserved.</p>
        <p class="text-muted small mb-0">Group B</p>
      </div>

      <div class="col-lg-2 col-md-4 mb-4">
        <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="./landing_page.html" class="text-white text-decoration-none">Home</a></li>
          <li class="mb-2"><a href="./job_listings.php" class="text-white text-decoration-none">All Jobs</a></li>
          <li class="mb-2"><a href="#" class="text-white text-decoration-none">All Courses</a></li>
          <li class="mb-2"><a href="#" class="text-white text-decoration-none">Services</a></li>
          <li class="mb-2"><a href="./faq.html" class="text-white text-decoration-none">Contact</a></li>
          <li class="mb-2"><a href="./aboutUs.html" class="text-white text-decoration-none">About Us</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-4 mb-4">
        <h5 class="text-uppercase fw-bold mb-4">Policies</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
          <li class="mb-2"><a href="#" class="text-white text-decoration-none">Terms and Conditions</a></li>
          <li class="mb-2"><a href="#" class="text-white text-decoration-none">Refund and Returns Policy</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-4 mb-4">
        <h5 class="text-uppercase fw-bold mb-4">Stay Updated</h5>
        <p class="text-muted">Subscribe to our newsletter for the latest updates.</p>
        <div class="input-group mb-3">
          <input type="email" class="form-control" placeholder="Your Email">
          <button class="btn btn-primary" type="button">Subscribe</button>
        </div>
        <div class="d-flex">
          <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
          <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
          <a href="#" class="text-white me-3"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div>
    <hr class="my-4 bg-secondary">
    <div class="row">
      <div class="col-md-6 text-center text-md-start">
        <p class="small text-muted mb-0">© 2023 SkillConnect. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <p class="small text-muted mb-0">Group B</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
