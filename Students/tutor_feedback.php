<?php
session_start();
require_once 'connect.php';

$message = "";

// Ensure only logged-in students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("<div class='alert alert-danger text-center'>❌ You must be logged in as a student to submit tutor feedback.</div>");
}

$student_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tutor_name = trim($_POST['tutor_name']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Lookup tutor by name
    $stmt = $conn->prepare("SELECT id FROM tutors WHERE name = ?");
    $stmt->bind_param("s", $tutor_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutor = $result->fetch_assoc();
    $stmt->close();

    if ($tutor) {
        $tutor_id = $tutor['id'];
        $stmt = $conn->prepare("INSERT INTO tutor_reviews (tutor_id, student_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $tutor_id, $student_id, $rating, $comment);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success text-center'>✅ Review submitted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ Failed to submit review: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning text-center'>❌ Tutor not found. Please check the name.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tutor Feedback - SkillConnect</title>
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
        <h2 class="text-center fw-bold mb-4">Submit Tutor Review</h2>

        <?= $message ?>

        <form method="POST" class="bg-white p-4 shadow rounded" style="max-width: 600px; margin: auto;">
            <div class="mb-3">
                <label for="tutor_name" class="form-label">Tutor Name</label>
                <input type="text" class="form-control" name="tutor_name" required placeholder="e.g., Zainab Khan">
            </div>

            <div class="mb-3">
                <label for="rating" class="form-label">Rating (1-5)</label>
                <select class="form-select" name="rating" required>
                    <option value="">Select Rating</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="comment" class="form-label">Comment</label>
                <textarea name="comment" class="form-control" rows="4" required
                    placeholder="Write your review..."></textarea>
            </div>

            <div class="d-grid">
                <input type="submit" value="Submit Review" class="btn btn-success">
            </div>
        </form>
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