<?php
session_start();
require_once 'connect.php';

$message = "";

// ✅ Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("<div class='alert alert-danger text-center'>❌ You must be logged in as a student to submit feedback.</div>");
}

$student_id = $_SESSION['user_id'];

// ✅ Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tutor_name = trim($_POST['tutor_name']);
    $description = trim($_POST['description']);

    // Get tutor_id from name
    $stmt = $conn->prepare("SELECT id FROM tutors WHERE name = ?");
    $stmt->bind_param("s", $tutor_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutor = $result->fetch_assoc();
    $stmt->close();

    if ($tutor) {
        $tutor_id = $tutor['id'];
        $stmt = $conn->prepare("INSERT INTO feedback (student_id, tutor_id, description, submitted_at, read_by_admin) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->bind_param("iis", $student_id, $tutor_id, $description);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success text-center'>✅ Complain submitted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ Failed to submit feedback: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning text-center'>❌ Tutor not found. Please check the name.</div>";
    }
}

// ✅ Fetch feedback history
$feedbacks = [];
$sql = "SELECT f.*, t.name as tutor_name 
        FROM feedback f 
        JOIN tutors t ON f.tutor_id = t.id 
        WHERE f.student_id = ? 
        ORDER BY f.submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $feedbacks[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Compplain - SkillConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
</head>

<body style="background-color: #f8f9fa;">

    <!-- ✅ Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../logo.jpeg" alt="SkillConnect Logo" style="height: 60px; object-fit: contain" />
            </a>
            <div class="collapse navbar-collapse justify-content-between">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="./landing_page.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="./all_jobs.html">All Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="./all_courses.html">All Courses</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Services</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="./registration_form_students_tutor.html">Tutoring</a>
                            </li>
                            <li><a class="dropdown-item" href="./registration_form_jobSeekers_Employeers.html">Job
                                    Matching</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="./faq.html">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="./aboutUs.html">About Us</a></li>
                </ul>
                <div class="d-flex gap-3 align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">Menu</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="add_course.php">Add Courses</a></li>
                            <li><a class="dropdown-item" href="my_courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="timetable.php">My Timetable</a></li>
                            <li> <a class="dropdown-item" href="schedule_student_session.php">Book Session</a></li>
                            <li><a class="dropdown-item" href="tutor_feedback.php">Tutor Feedback</a></li>
                            <li><a class="dropdown-item" href="submit_feedback.php">Complain</a></li>
                            <li><a class="dropdown-item" href="chat.php">Chat</a></li>
                        </ul>
                    </div>
                    <a href="../logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ✅ Main Content -->
    <div class="container my-5">
        <h2 class="text-center fw-bold mb-4">Submit Complain</h2>

        <?= $message ?>

        <form method="POST" class="bg-white p-4 shadow rounded" style="max-width: 600px; margin: auto;">
            <div class="mb-3">
                <label for="tutor_name" class="form-label">Tutor Name</label>
                <input type="text" name="tutor_name" class="form-control" required placeholder="e.g., Zainab Khan">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Feedback</label>
                <textarea name="description" class="form-control" rows="5" required
                    placeholder="Write your feedback here..."></textarea>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-success">Submit Complain</button>
            </div>
        </form>

        <h3 class="text-center mt-5">Your Previous Complains</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Tutor</th>
                        <th>Description</th>
                        <th>Submitted At</th>
                        <th>Admin Reply</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($feedbacks) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center">No Complains found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['tutor_name']) ?></td>
                                <td><?= htmlspecialchars($f['description']) ?></td>
                                <td><?= htmlspecialchars($f['submitted_at']) ?></td>
                                <td>
                                    <?= ($f['read_by_admin'] && $f['reply'])
                                        ? htmlspecialchars($f['reply'])
                                        : '<em>Pending</em>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ✅ Footer -->
    <footer class="bg-dark text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <a class="navbar-brand d-flex align-items-center mb-3" href="#">
                        <img src="../logo.jpeg" alt="SkillConnect Logo" height="60" />
                    </a>
                    <p class="text-muted">Temporary minds. Share skills. Shape the future – with SkillConnect.</p>
                    <p class="text-muted small">© 2025 SkillConnect. All rights reserved.</p>
                    <p class="text-muted small mb-0">Group B</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="./landing_page.html" class="text-white text-decoration-none">Home</a>
                        </li>
                        <li class="mb-2"><a href="./all_courses.html" class="text-white text-decoration-none">All
                                Courses</a></li>
                        <li class="mb-2"><a href="./faq.html" class="text-white text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="./aboutUs.html" class="text-white text-decoration-none">About Us</a>
                        </li>
                        <li class="mb-2"><a href="./all_jobs.html" class="text-white text-decoration-none">Jobs</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>