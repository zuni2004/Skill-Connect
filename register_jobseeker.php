<?php
// Start the session to access user data
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

// Check if the user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    // If not logged in or not a job seeker, redirect to login page
    header("Location: login.html");
    exit();
}

$job_seeker_id = $_SESSION['user_id'];
$job_seeker_username = $_SESSION['username'];

$applied_jobs = [];
$total_applied_jobs = 0;
$pending_applications = 0;
$shortlisted_applications = 0;
$accepted_applications = 0;

// Fetch all applications by this job seeker
$sql_applications = "
    SELECT
        ja.id AS application_id,
        ja.status,
        ja.application_date,
        ja.interview_date,
        ja.interview_time,
        jp.title AS job_title,
        jp.company_name,
        jp.location
    FROM
        job_applications ja
    JOIN
        job_postings jp ON ja.job_posting_id = jp.id
    WHERE
        ja.job_seeker_id = ?
    ORDER BY
        ja.application_date DESC";

$stmt_applications = $conn->prepare($sql_applications);
if ($stmt_applications) {
    $stmt_applications->bind_param("i", $job_seeker_id);
    $stmt_applications->execute();
    $result_applications = $stmt_applications->get_result();

    $total_applied_jobs = $result_applications->num_rows; // Count total
    while ($row = $result_applications->fetch_assoc()) {
        $applied_jobs[] = $row;
        // Count statuses
        switch ($row['status']) {
            case 'Pending':
                $pending_applications++;
                break;
            case 'Shortlisted':
                $shortlisted_applications++;
                break;
            case 'Accepted':
                $accepted_applications++;
                break;
        }
    }
    $stmt_applications->close();
} else {
    error_log("Error preparing applications fetch for job seeker dashboard: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Seeker Dashboard - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex; /* Use flexbox for layout */
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #220359, #4906bf); /* Dark gradient similar to your logo */
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar .logo-container {
            margin-bottom: 30px;
            text-align: center;
        }
        .sidebar .logo-container img {
            max-width: 120px;
            margin-bottom: 10px;
            border-radius: 10px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            display: flex;
            align-items: center;
            width: 100%;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        .main-content {
            flex-grow: 1;
            padding: 30px;
        }
        .dashboard-header {
            color: #220359;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .info-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s ease-in-out;
        }
        .info-card:hover {
            transform: translateY(-3px);
        }
        .info-card h4 {
            color: #4906bf;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .info-card .display-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #220359;
            margin-bottom: 10px;
        }
        .info-card .btn-outline-primary {
            border-color: #4906bf;
            color: #4906bf;
        }
        .info-card .btn-outline-primary:hover {
            background-color: #4906bf;
            color: white;
        }
        .applied-jobs-table .status-badge {
            font-size: 0.8em;
            padding: 0.4em 0.7em;
            border-radius: 0.5rem;
        }
        .status-pending { background-color: #ffc107; color: #343a40; } /* Warning yellow */
        .status-shortlisted { background-color: #007bff; color: white; } /* Primary blue */
        .status-rejected { background-color: #dc3545; color: white; } /* Danger red */
        .status-accepted { background-color: #28a745; color: white; } /* Success green */
        .status-withdrawn { background-color: #6c757d; color: white; } /* Secondary gray */
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="./logo.jpeg" alt="SkillConnect Logo">
            <h5 class="text-white mt-2">Job Seeker Panel</h5>
            <p class="small text-white-50">Welcome, <?php echo htmlspecialchars($job_seeker_username); ?></p>
        </div>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link active" href="jobseeker_dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="job_listings.php">
                    <i class="bi bi-briefcase-fill"></i> Browse Jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#my-applications">
                    <i class="bi bi-file-earmark-text-fill"></i> My Applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-calendar-event-fill"></i> Interview Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-chat-dots-fill"></i> Messages
                </a>
            </li>
            <li class="nav-item mt-auto"> <!-- Push logout to bottom -->
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="dashboard-header">Job Seeker Dashboard</h2>

        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>Total Applied Jobs</h4>
                    <div class="display-number"><?php echo $total_applied_jobs; ?></div>
                    <a href="#my-applications" class="btn btn-outline-primary mt-2">View My Applications</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>Pending Applications</h4>
                    <div class="display-number"><?php echo $pending_applications; ?></div>
                    <a href="#my-applications" class="btn btn-outline-primary mt-2">Review Pending</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>Shortlisted/Accepted</h4>
                    <div class="display-number"><?php echo ($shortlisted_applications + $accepted_applications); ?></div>
                    <a href="#my-applications" class="btn btn-outline-primary mt-2">View Opportunities</a>
                </div>
            </div>
        </div>

        <div class="info-card mt-4 applied-jobs-table" id="my-applications">
            <h4>My Recent Applications</h4>
            <?php if (!empty($applied_jobs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Interview</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applied_jobs as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['location']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($app['application_date']))); ?></td>
                                    <td><span class="badge status-<?php echo strtolower(htmlspecialchars($app['status'])); ?>"><?php echo htmlspecialchars($app['status']); ?></span></td>
                                    <td>
                                        <?php if ($app['interview_date'] && $app['interview_time']): ?>
                                            <?php echo htmlspecialchars(date('M d, Y', strtotime($app['interview_date']))) . ' at ' . htmlspecialchars(date('h:i A', strtotime($app['interview_time']))); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-secondary">View Details</a>
                                        <!-- Add functionality for withdraw/reschedule here later -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">You haven't applied to any jobs yet.</p>
                <a href="job_listings.php" class="btn btn-primary">Browse Jobs to Apply</a>
            <?php endif; ?>
        </div>

        <!-- Add more job seeker-specific content cards/sections here -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
