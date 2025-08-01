<?php
// Start the session to get user type
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

// Determine dashboard link based on user type
$dashboard_link = null;
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'employer') {
        $dashboard_link = 'employer_dashboard.php';
    } elseif ($_SESSION['role'] === 'jobseeker') {
        $dashboard_link = 'jobseeker_dashboard.php';
    }
}

// Initialize variables for job fetching
$jobs = [];
$sql_params = [];
$sql_types = '';
$page_title = 'Available Job Opportunities'; // Default title for all jobs

// Check if an employer is logged in AND if they are requesting 'my_posts'
$is_my_posts_view = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'employer' && isset($_GET['view']) && $_GET['view'] === 'my_posts') {
    $is_my_posts_view = true;
    $page_title = 'My Posted Job Openings'; // Change title for employer's own posts
}

// Base SQL query to fetch job postings with joins to get category and employer organization names
$sql = "SELECT
            jp.id,
            jp.title,
            jp.description,
            jp.requirements,
            jp.location,
            jp.work_mode,
            jp.salary,
            jp.job_type,
            jp.application_deadline,
            jp.company_name,
            jp.company_website,
            jp.contact_email,
            jp.posted_at,
            jp.post_status,
            jp.is_promoted,
            c.category_name,
            e.organization_name AS employer_organization,
            jp.employer_id -- **IMPORTANT**: Fetch employer_id to compare with session user_id
        FROM
            job_postings AS jp
        LEFT JOIN                          -- Use LEFT JOIN to ensure jobs are listed even if category/employer is missing (though it shouldn't be with FKs)
            categories AS c ON jp.category_id = c.category_id
        LEFT JOIN
            employers AS e ON jp.employer_id = e.id";

// Add WHERE clause if viewing 'my_posts'
if ($is_my_posts_view) {
    $sql .= " WHERE jp.employer_id = ?";
    $sql_params[] = $_SESSION['user_id'];
    $sql_types = 'i'; // Integer for employer_id
}

$sql .= " ORDER BY jp.posted_at DESC"; // Order by most recent jobs first

$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($is_my_posts_view) {
        // Bind parameters only if there's a WHERE clause
        $stmt->bind_param($sql_types, ...$sql_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
    }
    $stmt->close();
} else {
    // Handle error preparing statement
    $message = "Error preparing job query: " . $conn->error;
    $message_type = "danger";
    $jobs = []; // Ensure jobs array is empty on error
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            padding: 1rem 2rem;
        }

        .navbar-brand img {
            height: 80px;
        }

        .nav-buttons .btn {
            margin-left: 10px;
        }

        /* START: REVERTED AND REFINED STYLES FOR SMALLER CARDS */
        .job-listing-section {
            padding: 5rem 0;
            background-color: #f8f9fa;
            /* Light background for the section */
        }

        .job-card {
            /* Original class for the smaller cards */
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            transition: transform 0.2s ease-in-out;
        }

        .job-card:hover {
            transform: translateY(-5px);
        }

        .job-card .card-header {
            background-color: #220359;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }

        .job-card .card-body {
            padding: 1.5rem;
        }

        .job-card .badge {
            font-size: 0.85em;
            padding: 0.5em 0.7em;
            border-radius: 0.5rem;
        }

        .job-card .salary-info {
            font-weight: bold;
            color: #007bff;
            /* Primary blue color */
        }

        .job-card .meta-info {
            font-size: 0.9em;
            color: #6c757d;
        }

        .job-card .requirements-list ul {
            list-style: none;
            padding-left: 0;
        }

        .job-card .requirements-list li:before {
            content: "✓ ";
            color: #28a745;
            /* Success green */
        }

        .job-card .btn-apply {
            background-color: #4906bf;
            border-color: #4906bf;
        }

        .job-card .btn-apply:hover {
            background-color: #380499;
            border-color: #380499;
        }

        /* New style for action buttons (kept from previous version) */
        .job-card-actions .btn {
            font-size: 0.9em;
            padding: 0.4rem 0.8rem;
        }

        .promoted-badge {
            /* This style was in the previous code, keeping it */
            background-color: #ffc107;
            /* Bootstrap yellow */
            color: #212529;
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.8em;
            /* No longer absolute as it will be inside header */
        }

        .deadline-warning {
            color: #dc3545;
            /* Red for overdue */
            font-weight: bold;
        }

        /* END: REVERTED AND REFINED STYLES FOR SMALLER CARDS */

        .navbar-nav .nav-link {
            font-weight: 600;
            margin: 0 10px;
        }

        .nav-icons i {
            font-size: 1.3rem;
            margin-right: 15px;
            color: #1e1e2f;
            cursor: pointer;
        }

        /* Footer Styling */
        footer {
            background-color: #343a40;
            color: white;
            padding: 3rem 0 2rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, .6);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: white;
        }

        .copyright {
            color: rgba(255, 255, 255, .5);
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color:#220359;"><?php echo htmlspecialchars($page_title); ?></h2>
            <?php if ($dashboard_link): // Only show if a user is logged in and has a dashboard ?>
                <a href="<?php echo htmlspecialchars($dashboard_link ?? 'jobseeker_dashboard.php'); ?>" class="btn btn-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            <?php endif; ?>
        </div>

        <div class="row"> <!-- Added row to wrap the columns -->
            <?php if (isset($message) && $message): ?>
                <div class="col-12">
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($jobs)): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="col-md-6 col-lg-4"> <!-- Restored column classes for grid layout -->
                        <div class="card job-card"> <!-- Restored card and job-card classes -->
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <?php if ($job['is_promoted']): ?>
                                    <span class="badge bg-warning text-dark promoted-badge">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <!-- Displaying company_name and employer_organization -->
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($job['company_name']); ?>
                                    <?php if (!empty($job['employer_organization']) && $job['employer_organization'] !== $job['company_name']): ?>
                                        (<?php echo htmlspecialchars($job['employer_organization']); ?>)
                                    <?php endif; ?>
                                </h6>
                                <p class="meta-info mb-1">
                                    <span><i class="bi bi-geo-alt"></i>
                                        <?php echo htmlspecialchars($job['location'] ?: 'N/A'); ?>
                                        (<?php echo htmlspecialchars($job['work_mode'] ?: 'N/A'); ?>)</span>
                                </p>
                                <p class="meta-info mb-3">
                                    <span><i class="bi bi-briefcase"></i>
                                        <?php echo htmlspecialchars($job['job_type']); ?></span>
                                    <?php if (!empty($job['category_name'])): ?>
                                        <span
                                            class="badge bg-secondary ms-2"><?php echo htmlspecialchars($job['category_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="salary-info mb-3">
                                    <i class="bi bi-currency-dollar"></i> Salary:
                                    <?php echo htmlspecialchars($job['salary'] ?: 'Negotiable'); ?>
                                </p>
                                <p class="card-text description-text">
                                    <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                <div class="requirements-list mb-3">
                                    <strong>Requirements:</strong>
                                    <ul>
                                        <?php
                                        $reqs = explode(',', $job['requirements'] ?? ''); // Handle null requirements
                                        foreach ($reqs as $req) {
                                            $trimmedReq = trim($req);
                                            if (!empty($trimmedReq)) {
                                                echo '<li>' . htmlspecialchars($trimmedReq) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <p class="meta-info mb-3">
                                    <strong>Application Deadline:</strong>
                                    <?php
                                    $deadline_date = null;
                                    if (!empty($job['application_deadline'])) {
                                        // Try to create DateTime object from the stored date
                                        $deadline_date = DateTime::createFromFormat('Y-m-d', $job['application_deadline']);
                                    }

                                    if ($deadline_date && $deadline_date->format('Y') !== '-0001') { // Check for valid year
                                        $now = new DateTime();
                                        if ($deadline_date < $now) {
                                            echo '<span class="deadline-warning">' . htmlspecialchars($deadline_date->format('M d, Y')) . ' (Expired)</span>';
                                        } else {
                                            echo htmlspecialchars($deadline_date->format('M d, Y'));
                                        }
                                    } else {
                                        echo 'N/A'; // Display N/A for invalid or empty dates
                                    }
                                    ?>
                                    <br>
                                    Posted on: <?php echo htmlspecialchars(date('M d, Y', strtotime($job['posted_at']))); ?>
                                </p>
                                <div class="d-flex justify-content-start gap-2 mt-4 job-card-actions">
                                    <?php
                                    // Conditional display for "Apply Now" button
                                    // Show if no one is logged in OR if a job seeker is logged in
                                    if (!isset($_SESSION['role']) || $_SESSION['role'] === 'jobseeker'):
                                        ?>
                                        <a href="apply_job.php?job_id=<?php echo htmlspecialchars($job['id']); ?>"
                                            class="btn btn-apply flex-grow-1 me-2">Apply Now</a>
                                    <?php endif; ?>

                                    <?php
                                    // Conditional display for employer action buttons
                                    // Show ONLY if an employer is logged in AND it's THEIR job
                                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'employer' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['employer_id']):
                                        ?>
                                        <a href="view_applicants.php?job_id=<?php echo htmlspecialchars($job['id']); ?>"
                                            class="btn btn-outline-secondary">View Applicants</a>
                                        <a href="edit_job.php?id=<?php echo htmlspecialchars($job['id']); ?>"
                                            class="btn btn-outline-secondary">Edit Job</a>
                                        <a href="delete_job.php?id=<?php echo htmlspecialchars($job['id']); ?>"
                                            class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this job posting?');">Delete
                                            Job</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End of col-md-6 col-lg-4 -->
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center"> <!-- Added col-12 for proper centering -->
                    <div class="alert alert-info" role="alert">
                        No job postings found at the moment. Check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div> <!-- End of row -->
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row">
                <!-- Logo and Tagline -->
                <div class="col-lg-4 mb-4">
                    <a class="navbar-brand d-flex align-items-center mb-3" href="#">
                        <img src="./logo.jpeg" alt="SkillConnect Logo" height="60">
                    </a>
                    <p class="text-muted">Temporary minds. Share skills. Shape the future – with SkillConnect.</p>
                    <p class="text-muted small">Group 5</p>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">All Courses</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Jobs</a></li>
                    </ul>
                </div>

                <!-- Policies -->
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">Policies</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Refund and Returns
                                Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Terms and Condition</a>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
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
                <div class="col-12 text-center">
                    <p class="copyright mb-0">Copyright © 2025 @SkillConnect</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>