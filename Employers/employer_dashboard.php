<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../login.html"); // or the correct relative path
    exit();
}

$employer_id = $_SESSION['user_id'];

$jobs = [];
$recent_applications = [];
$total_job_posts = 0;
$total_applications = 0;

// Fetch Employer's Job Posts
$sql_jobs = "SELECT id, title, post_status FROM job_postings WHERE employer_id = ? ORDER BY posted_at DESC";
$stmt_jobs = $conn->prepare($sql_jobs);
if ($stmt_jobs) {
    $stmt_jobs->bind_param("i", $employer_id);
    $stmt_jobs->execute();
    $result_jobs = $stmt_jobs->get_result();
    $total_job_posts = $result_jobs->num_rows;
    while ($row = $result_jobs->fetch_assoc()) {
        $jobs[] = $row;
    }
    $stmt_jobs->close();
} else {
    error_log("Error preparing job fetch: {$conn->error}");
}

// Recent Applications
$sql_applications = "
    SELECT ja.id AS application_id, ja.applicant_name, ja.applicant_email, ja.application_date, ja.status,
           jp.title AS job_title, jp.id AS job_posting_id
    FROM job_applications ja
    JOIN job_postings jp ON ja.job_posting_id = jp.id
    WHERE jp.employer_id = ?
    ORDER BY ja.application_date DESC
    LIMIT 5";

$stmt_applications = $conn->prepare($sql_applications);
if ($stmt_applications) {
    $stmt_applications->bind_param("i", $employer_id);
    $stmt_applications->execute();
    $result_applications = $stmt_applications->get_result();
    while ($row = $result_applications->fetch_assoc()) {
        $recent_applications[] = $row;
    }
    $stmt_applications->close();
} else {
    error_log("Error fetching applications: {$conn->error}");
}

// Total Applications
$sql_total_applications = "SELECT COUNT(ja.id) AS total_apps
                           FROM job_applications ja
                           JOIN job_postings jp ON ja.job_posting_id = jp.id
                           WHERE jp.employer_id = ?";
$stmt_total_applications = $conn->prepare($sql_total_applications);
if ($stmt_total_applications) {
    $stmt_total_applications->bind_param("i", $employer_id);
    $stmt_total_applications->execute();
    $result_total_applications = $stmt_total_applications->get_result();
    $total_applications = $result_total_applications->fetch_assoc()['total_apps'];
    $stmt_total_applications->close();
} else {
    error_log("Error fetching total applications: {$conn->error}");
}

$conn->close(); // Only close after all DB work is done!
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            /* Use flexbox for layout */
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #220359, #4906bf);
            /* Dark gradient similar to your logo */
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
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
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            display: flex;
            align-items: center;
            width: 100%;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
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

        .recent-applications-table .status-badge {
            font-size: 0.8em;
            padding: 0.4em 0.7em;
            border-radius: 0.5rem;
        }

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
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="./logo.jpeg" alt="SkillConnect Logo">
            <h5 class="text-white mt-2">Employer Panel</h5>
            <p class="small text-white-50">Welcome, <?php echo htmlspecialchars($employer_id); ?></p>
        </div>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link active" href="employer_dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="post_job.php">
                    <i class="bi bi-file-earmark-plus"></i> Post New Job
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="job_listings.php?view=my_posts">
                    <i class="bi bi-briefcase-fill"></i> My Job Posts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="job_listings.php"> <!-- NEW LINK: View ALL jobs -->
                    <i class="bi bi-collection-fill"></i> View All Jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                    href="view_applicants.php?job_id=<?php echo htmlspecialchars($jobs[0]['id'] ?? ''); ?>">
                    <i class="bi bi-people-fill"></i> All Applicants
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-chat-dots-fill"></i> Messages
                </a>
            </li>
            <li class="nav-item mt-auto"> <!-- Push logout to bottom -->
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <h2 class="dashboard-header">Employer Dashboard</h2>

        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>Total Job Posts</h4>
                    <div class="display-number"><?php echo $total_job_posts; ?></div>
                    <a href="job_listings.php?view=my_posts" class="btn btn-outline-primary mt-2">View All My Jobs</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>Total Applications</h4>
                    <div class="display-number"><?php echo $total_applications; ?></div>
                    <a href="view_applicants.php?job_id=<?php echo htmlspecialchars($jobs[0]['id'] ?? ''); ?>"
                        class="btn btn-outline-primary mt-2">View All Applicants</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="info-card text-center">
                    <h4>New Job Posting</h4>
                    <div class="display-number"><i class="bi bi-plus-circle"></i></div>
                    <a href="post_job.php" class="btn btn-outline-primary mt-2">Post a New Job</a>
                </div>
            </div>
        </div>

        <div class="info-card mt-4 recent-applications-table">
            <h4>Recent Applications for Your Jobs</h4>
            <?php if (!empty($recent_applications)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Job Title</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($app['application_date']))); ?>
                                    </td>
                                    <td><span
                                            class="badge status-<?php echo strtolower(htmlspecialchars($app['status'])); ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                                    </td>
                                    <td>
                                        <a href="view_applicants.php?job_id=<?php echo htmlspecialchars($app['job_posting_id']); ?>"
                                            class="btn btn-sm btn-info">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No recent applications for your jobs.</p>
            <?php endif; ?>
        </div>

        <!-- Add more employer-specific content cards/sections here -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
?>