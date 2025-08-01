<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check employer login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: ../login.html");
    exit;
}

// Database connection
$mysqli = new mysqli("sql12.freesqldatabase.com", "sql12784403", "WAuJFq9xaX", "sql12784403", 3306);

// Check connection
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit;
}

// Fetch all interviews for the table
$stmt = $mysqli->prepare("
    SELECT 
        i.id,
        js.full_name AS candidate_name, 
        IFNULL(jp.title, 'No specific job') AS job_title, 
        i.start_time, 
        i.duration, 
        i.join_url, 
        i.password, 
        i.status
    FROM interviews i
    JOIN job_seekers js ON i.jobseeker_id = js.id
    LEFT JOIN job_postings jp ON i.job_posting_id = jp.id
    WHERE i.employer_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$interviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Interview List | SkillConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="employer_dashboard.php">
                <img src="../logo.jpeg" alt="SkillConnect Logo"
                    style="height: 60px; width: auto; object-fit: contain" />
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="employer_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="post_job.php">Post New Job</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_job_posts.php">My Job Posts</a></li>
                    <li class="nav-item"><a class="nav-link" href="all_applicants.php">All Applicants</a></li>
                    <li class="nav-item"><a class="nav-link" href="messages.php">Messages</a></li>
                </ul>
                <div class="d-flex align-items-center nav-icons">
                    <a href="../logout.php" class="btn btn-danger ms-2">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="table-responsive">
            <h4 class="mb-3 text-center">All Scheduled Interviews</h4>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Candidate</th>
                        <th>Job Title</th>
                        <th>Start Time</th>
                        <th>Duration (min)</th>
                        <th>Join Link</th>
                        <th>Password</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interviews as $meet): ?>
                        <tr>
                            <td><?= htmlspecialchars($meet['id']) ?></td>
                            <td><?= htmlspecialchars($meet['candidate_name']) ?></td>
                            <td><?= htmlspecialchars($meet['job_title']) ?></td>
                            <td><?= htmlspecialchars($meet['start_time']) ?></td>
                            <td><?= htmlspecialchars($meet['duration']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($meet['join_url']) ?>" target="_blank"
                                    class="btn btn-success btn-sm">
                                    <i class="bi bi-camera-video"></i> Join
                                </a>
                            </td>
                            <td><?= htmlspecialchars($meet['password'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge 
                                <?= $meet['status'] === 'Scheduled' ? 'bg-primary' :
                                    ($meet['status'] === 'Completed' ? 'bg-success' : 'bg-secondary') ?>">
                                    <?= htmlspecialchars($meet['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="bg-dark text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a class="navbar-brand d-flex align-items-center mb-3" href="#">
                        <img src="../logo.jpeg" alt="SkillConnect Logo" height="60" />
                    </a>
                    <p class="text-muted">
                        Temporary minds. Share skills. Shape the future - with SkillConnect.
                    </p>
                    <p class="text-muted small">© 2025 SkillConnect. All rights reserved.</p>
                    <p class="text-muted small mb-0">Group B</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="../landing_page.html" class="text-white text-decoration-none">Home</a>
                        </li>
                        <li class="mb-2"><a href="../aboutUs.html" class="text-white text-decoration-none">About Us</a>
                        </li>
                        <li class="mb-2"><a href="../faq.html" class="text-white text-decoration-none">Contact</a>
                        </li>
                        <li class="mb-2"><a href="../all_jobs.php" class="text-white text-decoration-none">Jobs</a>
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
                                Policy</a>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary" />
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small text-white mb-0">© 2025 SkillConnect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="small text-white mb-0">Group B</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>