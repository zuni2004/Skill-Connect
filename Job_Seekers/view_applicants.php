<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

$job_id = null;
$job_title = 'Job Applicants'; // Default title
$applicants = [];
$message = '';
$message_type = '';

if (isset($_GET['job_id'])) {
    $job_id = $conn->real_escape_string($_GET['job_id']);

    // Fetch job title first for display
    $sql_job_title = "SELECT title FROM job_postings WHERE id = ?";
    $stmt_job_title = $conn->prepare($sql_job_title);
    if ($stmt_job_title) {
        $stmt_job_title->bind_param("i", $job_id);
        $stmt_job_title->execute();
        $result_job_title = $stmt_job_title->get_result();
        if ($result_job_title->num_rows > 0) {
            $job_row = $result_job_title->fetch_assoc();
            $job_title = htmlspecialchars($job_row['title']) . " - Applicants";
        } else {
            $message = 'Job not found.';
            $message_type = 'danger';
        }
        $stmt_job_title->close();
    } else {
        $message = 'Error preparing job title fetch: ' . $conn->error;
        $message_type = 'danger';
    }


    // Fetch applicants for this job_id
    // Now including interview_date and interview_time
    $sql_applicants = "SELECT
                            id,
                            job_posting_id,
                            job_seeker_id,
                            applicant_name,
                            applicant_email,
                            resume_filepath,
                            cover_letter,
                            application_date,
                            status,
                            interview_date,    -- Added
                            interview_time     -- Added
                        FROM
                            job_applications
                        WHERE
                            job_posting_id = ?
                        ORDER BY
                            application_date DESC";
    $stmt_applicants = $conn->prepare($sql_applicants);

    if ($stmt_applicants) {
        $stmt_applicants->bind_param("i", $job_id);
        $stmt_applicants->execute();
        $result_applicants = $stmt_applicants->get_result();

        if ($result_applicants->num_rows > 0) {
            while ($row = $result_applicants->fetch_assoc()) {
                $applicants[] = $row;
            }
        } else {
            if (empty($message)) {
                $message = 'No applicants found for this job yet.';
                $message_type = 'info';
            }
        }
        $stmt_applicants->close();
    } else {
        $message = 'Error preparing applicants fetch: ' . $conn->error;
        $message_type = 'danger';
    }

} else {
    $message = 'No job ID provided to view applicants.';
    $message_type = 'danger';
}

$conn->close(); // Close connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $job_title; ?> | SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            transition: background 0.3s, color 0.3s;
        }

        .navbar {
            padding: 1rem 2rem;
        }

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

        .nav-buttons .btn {
            margin-left: 10px;
        }

        .navbar-brand img {
            height: 80px;
        }

        .applicants-section {
            padding: 5rem 0;
            background-color: #f8f9fa;
        }

        .applicant-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .applicant-card .card-header {
            background-color: #f0f2f5;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }

        .applicant-card .card-body {
            padding: 1.5rem;
        }

        .applicant-info p {
            margin-bottom: 0.5rem;
        }

        .applicant-status .badge {
            font-size: 0.9em;
            padding: 0.6em 0.8em;
            border-radius: 0.5rem;
        }

        /* Using your 'status' column, converted to lowercase for class names */
        .status-pending {
            background-color: #ffc107;
            color: #343a40;
        }

        /* Warning yellow */
        .status-shortlisted {
            background-color: #007bff;
            color: white;
        }

        /* Primary blue */
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }

        /* Danger red */
        .status-accepted {
            background-color: #28a745;
            color: white;
        }

        /* Success green */
        .status-withdrawn {
            background-color: #6c757d;
            color: white;
        }

        /* Secondary gray */

        .btn-applicant-action {
            font-size: 0.9em;
            padding: 0.4rem 0.8rem;
        }

        .btn-message {
            background-color: #6f42c1;
            /* Purple */
            border-color: #6f42c1;
            color: white;
        }

        .btn-message:hover {
            background-color: #5a359d;
            border-color: #5a359d;
        }

        .schedule-form-section {
            border-top: 1px solid #e9ecef;
            padding-top: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>

<body>

    <!-- View Applicants Section -->
    <section class="applicants-section">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold"><?php echo $job_title; ?></h2>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($applicants)): ?>
                <div class="row">
                    <?php foreach ($applicants as $applicant): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card applicant-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($applicant['applicant_name']); ?></h5>
                                    <span
                                        class="badge status-<?php echo strtolower(htmlspecialchars($applicant['status'])); ?>">
                                        <?php echo htmlspecialchars($applicant['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p class="applicant-info"><i class="bi bi-envelope"></i> Email:
                                        <?php echo htmlspecialchars($applicant['applicant_email']); ?></p>
                                    <p class="applicant-info"><i class="bi bi-calendar"></i> Applied On:
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($applicant['application_date']))); ?>
                                    </p>
                                    <?php if (!empty($applicant['resume_filepath'])): ?>
                                        <p class="applicant-info"><i class="bi bi-file-earmark-person"></i> <a
                                                href="<?php echo htmlspecialchars($applicant['resume_filepath']); ?>"
                                                target="_blank">View Resume</a></p>
                                    <?php endif; ?>
                                    <?php if (!empty($applicant['cover_letter'])): ?>
                                        <p class="applicant-info"><strong>Cover Letter:</strong></p>
                                        <p class="card-text small">
                                            <?php echo nl2br(htmlspecialchars($applicant['cover_letter'])); ?></p>
                                    <?php endif; ?>
                                    <hr>
                                    <div class="d-flex justify-content-between flex-wrap mb-3">
                                        <!-- Status Update Forms -->
                                        <form action="update_applicant_status.php" method="POST" style="display:inline;"
                                            class="mb-2">
                                            <input type="hidden" name="applicant_id"
                                                value="<?php echo htmlspecialchars($applicant['id']); ?>">
                                            <input type="hidden" name="new_status" value="Shortlisted">
                                            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
                                            <button type="submit" class="btn btn-primary btn-applicant-action me-2" <?php echo ($applicant['status'] == 'Shortlisted') ? 'disabled' : ''; ?>>Shortlist</button>
                                        </form>
                                        <form action="update_applicant_status.php" method="POST" style="display:inline;"
                                            class="mb-2">
                                            <input type="hidden" name="applicant_id"
                                                value="<?php echo htmlspecialchars($applicant['id']); ?>">
                                            <input type="hidden" name="new_status" value="Accepted">
                                            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
                                            <button type="submit" class="btn btn-success btn-applicant-action me-2" <?php echo ($applicant['status'] == 'Accepted') ? 'disabled' : ''; ?>>Accept</button>
                                        </form>
                                        <form action="update_applicant_status.php" method="POST" style="display:inline;"
                                            class="mb-2">
                                            <input type="hidden" name="applicant_id"
                                                value="<?php echo htmlspecialchars($applicant['id']); ?>">
                                            <input type="hidden" name="new_status" value="Rejected">
                                            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
                                            <button type="submit" class="btn btn-danger btn-applicant-action" <?php echo ($applicant['status'] == 'Rejected') ? 'disabled' : ''; ?>>Reject</button>
                                        </form>
                                        <!-- Message Applicant Button -->
                                        <a href="mailto:<?php echo htmlspecialchars($applicant['applicant_email']); ?>?subject=Regarding your application for <?php echo urlencode($job_row['title']); ?>&body=Dear <?php echo htmlspecialchars($applicant['applicant_name']); ?>,"
                                            class="btn btn-message btn-applicant-action mt-2 w-100">
                                            <i class="bi bi-envelope"></i> Message Applicant
                                        </a>
                                    </div>

                                    <!-- Schedule Interview Section -->
                                    <div class="schedule-form-section">
                                        <h6 class="fw-bold mb-2">Schedule Interview:</h6>
                                        <?php if ($applicant['interview_date'] && $applicant['interview_time']): ?>
                                            <p class="mb-1">
                                                <i class="bi bi-calendar-check"></i> Interview Scheduled:
                                                <span
                                                    class="fw-bold"><?php echo htmlspecialchars(date('M d, Y', strtotime($applicant['interview_date']))); ?></span>
                                                at
                                                <span
                                                    class="fw-bold"><?php echo htmlspecialchars(date('h:i A', strtotime($applicant['interview_time']))); ?></span>
                                            </p>
                                            <p class="small text-muted">Use the form below to reschedule if needed.</p>
                                        <?php else: ?>
                                            <p class="small text-muted">No interview scheduled yet.</p>
                                        <?php endif; ?>

                                        <form action="schedule_interview.php" method="POST">
                                            <input type="hidden" name="applicant_id"
                                                value="<?php echo htmlspecialchars($applicant['id']); ?>">
                                            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
                                            <div class="row g-2 mb-2">
                                                <div class="col-md-6">
                                                    <input type="date" class="form-control form-control-sm"
                                                        name="interview_date"
                                                        value="<?php echo htmlspecialchars($applicant['interview_date'] ?: ''); ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="time" class="form-control form-control-sm"
                                                        name="interview_time"
                                                        value="<?php echo htmlspecialchars($applicant['interview_time'] ?: ''); ?>"
                                                        required>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-secondary btn-sm w-100">
                                                <i class="bi bi-calendar-plus"></i> Set/Update Interview
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="lead">No applicants found for this job. Share the listing to get more applications!</p>
                        <a href="post_job.html" class="btn btn-primary mt-3">Post a New Job</a>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <a href="job_listings.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to All
                        Jobs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Same Footer -->
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
                        <li class="mb-2"><a href="./landing_page.html" class="text-white text-decoration-none">Home</a>
                        </li>
                        <li class="mb-2"><a href="./job_listings.php" class="text-white text-decoration-none">All
                                Jobs</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">All Courses</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Services</a></li>
                        <li class="mb-2"><a href="./faq.html" class="text-white text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="./aboutUs.html" class="text-white text-decoration-none">About Us</a>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">Policies</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Terms and Conditions</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Refund and Returns
                                Policy</a></li>
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