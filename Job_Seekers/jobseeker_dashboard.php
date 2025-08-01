<?php
// Start the session to access user data
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

// Check if the user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jobseeker') {
    // If not logged in or not a job seeker, redirect to login page
    header("Location: ../login.html");
    exit();
}
$_SESSION['role'] = 'jobseeker';  // or 'employer', etc.
$job_seeker_id = $_SESSION['user_id'];

// Initialize counts
$total_applied_jobs = 0;
$pending_applications = 0;
$shortlisted_accepted_applications = 0;
$my_recent_applications = [];

// Fetch Total Applied Jobs
$sql_total_applied = "SELECT COUNT(id) AS total FROM job_applications WHERE job_seeker_id = ?";
$stmt_total_applied = $conn->prepare($sql_total_applied);
if ($stmt_total_applied) {
    $stmt_total_applied->bind_param("i", $job_seeker_id);
    $stmt_total_applied->execute();
    $result_total_applied = $stmt_total_applied->get_result();
    $total_applied_jobs = $result_total_applied->fetch_assoc()['total'];
    $stmt_total_applied->close();
} else {
    error_log("Error fetching total applied jobs: " . $conn->error);
}

// Fetch Pending Applications
$sql_pending_apps = "SELECT COUNT(id) AS total FROM job_applications WHERE job_seeker_id = ? AND status = 'Pending'";
$stmt_pending_apps = $conn->prepare($sql_pending_apps);
if ($stmt_pending_apps) {
    $stmt_pending_apps->bind_param("i", $job_seeker_id);
    $stmt_pending_apps->execute();
    $result_pending_apps = $stmt_pending_apps->get_result();
    $pending_applications = $result_pending_apps->fetch_assoc()['total'];
    $stmt_pending_apps->close();
} else {
    error_log("Error fetching pending applications: " . $conn->error);
}

// Fetch Shortlisted/Accepted Applications
$sql_short_acc_apps = "SELECT COUNT(id) AS total FROM job_applications WHERE job_seeker_id = ? AND (status = 'Shortlisted' OR status = 'Accepted')";
$stmt_short_acc_apps = $conn->prepare($sql_short_acc_apps);
if ($stmt_short_acc_apps) {
    $stmt_short_acc_apps->bind_param("i", $job_seeker_id);
    $stmt_short_acc_apps->execute();
    $result_short_acc_apps = $stmt_short_acc_apps->get_result();
    $shortlisted_accepted_applications = $result_short_acc_apps->fetch_assoc()['total'];
    $stmt_short_acc_apps->close();
} else {
    error_log("Error fetching shortlisted/accepted applications: " . $conn->error);
}

// Fetch My Recent Applications (for the table) - this query is always run initially
$sql_recent_apps = "
    SELECT
        ja.id AS application_id,
        ja.application_date,
        ja.status,
        ja.interview_date,
        ja.interview_time,
        jp.title AS job_title,
        jp.company_name,
        jp.location
    FROM
        job_applications AS ja
    JOIN
        job_postings AS jp ON ja.job_posting_id = jp.id
    WHERE
        ja.job_seeker_id = ?
    ORDER BY
        ja.application_date DESC
    LIMIT 5"; // Fetching up to 5 recent applications

$stmt_recent_apps = $conn->prepare($sql_recent_apps);
if ($stmt_recent_apps) {
    $stmt_recent_apps->bind_param("i", $job_seeker_id);
    $stmt_recent_apps->execute();
    $result_recent_apps = $stmt_recent_apps->get_result();
    while ($row = $result_recent_apps->fetch_assoc()) {
        $my_recent_applications[] = $row;
    }
    $stmt_recent_apps->close();
} else {
    error_log("Error fetching recent applications: " . $conn->error);
}

// This section will determine which applications to display based on the 'filter' GET parameter
$display_applications = [];
$current_filter = $_GET['filter'] ?? 'recent'; // Default to 'recent' for initial load

// Re-establish connection if it was closed by previous code (though in current structure, it shouldn't be yet)
// This explicit check ensures $conn is available before subsequent queries
if (!$conn->ping()) {
    require_once 'connect.php'; // Reconnect if necessary
}

if ($current_filter === 'all_applications') {
    // Fetch ALL applications for the job seeker
    $sql_all_apps = "
        SELECT
            ja.id AS application_id,
            ja.application_date,
            ja.status,
            ja.interview_date,
            ja.interview_time,
            jp.title AS job_title,
            jp.company_name,
            jp.location
        FROM
            job_applications AS ja
        JOIN
            job_postings AS jp ON ja.job_posting_id = jp.id
        WHERE
            ja.job_seeker_id = ?
        ORDER BY
            ja.application_date DESC";
    $stmt_all_apps = $conn->prepare($sql_all_apps);
    if ($stmt_all_apps) {
        $stmt_all_apps->bind_param("i", $job_seeker_id);
        $stmt_all_apps->execute();
        $result_all_apps = $stmt_all_apps->get_result();
        while ($row = $result_all_apps->fetch_assoc()) {
            $display_applications[] = $row;
        }
        $stmt_all_apps->close();
    } else {
        error_log("Error fetching all applications: " . $conn->error);
    }
} elseif ($current_filter === 'pending') {
    // Fetch only PENDING applications
    $sql_filtered_apps = "
        SELECT
            ja.id AS application_id,
            ja.application_date,
            ja.status,
            ja.interview_date,
            ja.interview_time,
            jp.title AS job_title,
            jp.company_name,
            jp.location
        FROM
            job_applications AS ja
        JOIN
            job_postings AS jp ON ja.job_posting_id = jp.id
        WHERE
            ja.job_seeker_id = ? AND ja.status = 'Pending'
        ORDER BY
            ja.application_date DESC";
    $stmt_filtered_apps = $conn->prepare($sql_filtered_apps);
    if ($stmt_filtered_apps) {
        $stmt_filtered_apps->bind_param("i", $job_seeker_id);
        $stmt_filtered_apps->execute();
        $result_filtered_apps = $stmt_filtered_apps->get_result();
        while ($row = $result_filtered_apps->fetch_assoc()) {
            $display_applications[] = $row;
        }
        $stmt_filtered_apps->close();
    } else {
        error_log("Error fetching pending applications: " . $conn->error);
    }
} elseif ($current_filter === 'shortlisted_accepted') {
    // Fetch only SHORTLISTED or ACCEPTED applications
    $sql_filtered_apps = "
        SELECT
            ja.id AS application_id,
            ja.application_date,
            ja.status,
            ja.interview_date,
            ja.interview_time,
            jp.title AS job_title,
            jp.company_name,
            jp.location
        FROM
            job_applications AS ja
        JOIN
            job_postings AS jp ON ja.job_posting_id = jp.id
        WHERE
            ja.job_seeker_id = ? AND (ja.status = 'Shortlisted' OR ja.status = 'Accepted')
        ORDER BY
            ja.application_date DESC";
    $stmt_filtered_apps = $conn->prepare($sql_filtered_apps);
    if ($stmt_filtered_apps) {
        $stmt_filtered_apps->bind_param("i", $job_seeker_id);
        $stmt_filtered_apps->execute();
        $result_filtered_apps = $stmt_filtered_apps->get_result();
        while ($row = $result_filtered_apps->fetch_assoc()) {
            $display_applications[] = $row;
        }
        $stmt_filtered_apps->close();
    } else {
        error_log("Error fetching shortlisted/accepted applications: " . $conn->error);
    }
} elseif ($current_filter === 'interview_schedule') {
    // Fetch applications with an interview scheduled
    $sql_interview_apps = "
        SELECT
            ja.id AS application_id,
            ja.application_date,
            ja.status,
            ja.interview_date,
            ja.interview_time,
            jp.title AS job_title,
            jp.company_name,
            jp.location
        FROM
            job_applications AS ja
        JOIN
            job_postings AS jp ON ja.job_posting_id = jp.id
        WHERE
            ja.job_seeker_id = ? AND ja.interview_date IS NOT NULL AND ja.interview_time IS NOT NULL
        ORDER BY
            ja.interview_date ASC, ja.interview_time ASC";
    $stmt_interview_apps = $conn->prepare($sql_interview_apps);
    if ($stmt_interview_apps) {
        $stmt_interview_apps->bind_param("i", $job_seeker_id);
        $stmt_interview_apps->execute();
        $result_interview_apps = $stmt_interview_apps->get_result();
        while ($row = $result_interview_apps->fetch_assoc()) {
            $display_applications[] = $row;
        }
        $stmt_interview_apps->close();
    } else {
        error_log("Error fetching interview schedule applications: " . $conn->error);
    }
} else {
    // Default: Display recent applications (same as initial fetch)
    $display_applications = $my_recent_applications;
}

// Close the database connection ONLY AFTER all queries are done
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
            height: 100%;
            /* Make cards fill height */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Space out content vertically */
            align-items: center;
            /* Center horizontally */
        }

        .info-card:hover {
            transform: translateY(-3px);
        }

        .info-card h4 {
            color: #4906bf;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .info-card .display-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #220359;
            margin-bottom: 10px;
            text-align: center;
        }

        .info-card .btn-outline-primary {
            border-color: #4906bf;
            color: #4906bf;
        }

        .info-card .btn-outline-primary:hover {
            background-color: #4906bf;
            color: white;
        }

        .recent-applications-table {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 20px;
        }

        .recent-applications-table h4 {
            color: #220359;
            font-weight: 600;
            margin-bottom: 15px;
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
            <h5 class="text-white mt-2">Job Seeker Panel</h5>
            <p class="small text-white-50">Welcome</p>
        </div>
        <ul class="nav flex-column w-100">
            <li class="nav-item">
                <a class="nav-link active" href="jobseeker_dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="job_listings.php">
                    <i class="bi bi-search"></i> Browse Jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="jobseeker_dashboard.php?filter=all_applications">
                    <i class="bi bi-briefcase-fill"></i> My Applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="interview.php">
                    <i class="bi bi-calendar-check-fill"></i> Interview Schedule
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
        <h2 class="dashboard-header">Job Seeker Dashboard</h2>

        <div class="row">
            <div class="col-md-4">
                <div class="info-card text-center">
                    <h4>Total Applied Jobs</h4>
                    <div class="display-number"><?php echo $total_applied_jobs; ?></div>
                    <a href="jobseeker_dashboard.php?filter=all_applications" class="btn btn-outline-primary mt-2">View
                        My Applications</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card text-center">
                    <h4>Pending Applications</h4>
                    <div class="display-number"><?php echo $pending_applications; ?></div>
                    <a href="jobseeker_dashboard.php?filter=pending" class="btn btn-outline-primary mt-2">Review
                        Pending</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card text-center">
                    <h4>Shortlisted/Accepted</h4>
                    <div class="display-number"><?php echo $shortlisted_accepted_applications; ?></div>
                    <a href="jobseeker_dashboard.php?filter=shortlisted_accepted"
                        class="btn btn-outline-primary mt-2">View Opportunities</a>
                </div>
            </div>
        </div>

        <div class="recent-applications-table mt-4">
            <h4>My Applications Filtered: <?php echo ucfirst(str_replace('_', ' ', $current_filter)); ?></h4>
            <?php
            // The display_applications array is already populated based on the filter logic above
            // The $conn is explicitly closed at the very end of the PHP script
            if (!empty($display_applications)):
                ?>
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
                            <?php foreach ($display_applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['location']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($app['application_date']))); ?>
                                    </td>
                                    <td><span
                                            class="badge status-<?php echo strtolower(htmlspecialchars($app['status'])); ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($app['interview_date']) && !empty($app['interview_time'])): ?>
                                            <?php echo htmlspecialchars(date('M d, Y', strtotime($app['interview_date']))); ?> at
                                            <?php echo htmlspecialchars(date('h:i A', strtotime($app['interview_time']))); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_application_details.php?app_id=<?php echo htmlspecialchars($app['application_id']); ?>"
                                            class="btn btn-sm btn-info">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">You haven't applied to any jobs yet, or no applications match the current filter.</p>
            <?php endif; ?>
        </div>

        <!-- Add more job seeker-specific content cards/sections here -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>