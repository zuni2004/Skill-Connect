<?php
session_start();
require_once 'connect.php';

$message = "";

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("<div class='alert alert-danger text-center'>❌ You must be logged in as a student to access the timetable.</div>");
}

$student_id = $_SESSION['user_id'];

// Fetch timetable
$timetable = [];
$sql = "SELECT * FROM student_timetable WHERE course_id IN 
        (SELECT course_id FROM student_courses WHERE student_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$timetableResult = $stmt->get_result();
while ($row = $timetableResult->fetch_assoc()) {
    $timetable[] = $row;
}
$stmt->close();

// Fetch enrolled courses
$courses = [];
$sql = "SELECT c.course_id, c.title, c.tutor_id, t.name as tutor_name, c.mode 
        FROM courses c
        JOIN student_courses sc ON c.course_id = sc.course_id
        JOIN tutors t ON c.tutor_id = t.id
        WHERE sc.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courseResult = $stmt->get_result();
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $day_of_week = $_POST['day_of_week'];
    $time_slot = $_POST['time_slot'];

    $stmt = $conn->prepare("SELECT c.title, c.tutor_id, t.name AS tutor_name, sc.mode
                            FROM courses c
                            JOIN tutors t ON c.tutor_id = t.id
                            JOIN student_courses sc ON c.course_id = sc.course_id
                            WHERE c.course_id = ? AND sc.student_id = ?");
    $stmt->bind_param("ii", $course_id, $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if ($data) {
        $course_name = $data['title'];
        $instructor_id = $data['tutor_id'];
        $instructor_name = $data['tutor_name'];
        $mode = $data['mode'];
        $room = ($mode === 'Online') ? NULL : "Room " . chr(rand(65, 68));

        $stmt = $conn->prepare("INSERT INTO student_timetable 
            (course_id, instructor_id, course_name, instructor_name, day_of_week, time_slot, room) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $course_id, $instructor_id, $course_name, $instructor_name, $day_of_week, $time_slot, $room);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success text-center'>✅ Timetable entry added for <strong>$course_name</strong>.</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ Failed to add entry: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning text-center'>❌ Invalid course selected.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Timetable - SkillConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    <!-- ✅ Page Content -->
    <div class="container py-5">
        <h2 class="text-center mb-4">📅 My Timetable</h2>

        <?= $message ?>

        <div class="table-responsive mb-5">
            <table class="table table-bordered table-hover bg-white shadow-sm">
                <thead class="table-success">
                    <tr>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Day</th>
                        <th>Time Slot</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timetable as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['course_name']) ?></td>
                            <td><?= htmlspecialchars($entry['instructor_name']) ?></td>
                            <td><?= htmlspecialchars($entry['day_of_week']) ?></td>
                            <td><?= htmlspecialchars($entry['time_slot']) ?></td>
                            <td><?= htmlspecialchars($entry['room'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h4 class="mb-3">➕ Add Timetable Slot</h4>
        <form method="POST" class="bg-white p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="course_id" class="form-label">Select Course</label>
                <select name="course_id" class="form-select" required>
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['course_id'] ?>">
                            <?= htmlspecialchars($c['title']) ?> (<?= $c['tutor_name'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="day_of_week" class="form-label">Day of Week</label>
                <select name="day_of_week" class="form-select" required>
                    <option value="">-- Select Day --</option>
                    <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                        <option value="<?= $day ?>"><?= $day ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="time_slot" class="form-label">Time Slot</label>
                <select name="time_slot" class="form-select" required>
                    <option value="">-- Select Slot --</option>
                    <?php foreach (['9:00 AM – 10:00 AM', '10:00 AM – 11:00 AM', '11:00 AM – 12:00 PM', '1:00 PM – 2:00 PM', '2:00 PM – 3:00 PM', '3:00 PM – 4:00 PM'] as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success w-100">Add Timetable Entry</button>
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
                    <p class="text-muted">Temporary minds. Share skills. Shape the future – with skillConnect.</p>
                    <p class="text-muted small">© 2025 SkillConnect. All rights reserved.</p>
                    <p class="text-muted small mb-0">Group B</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
                    <ul class="list-unstyled">
                        <li><a href="./landing_page.html" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="./all_courses.html" class="text-white text-decoration-none">Courses</a></li>
                        <li><a href="./faq.html" class="text-white text-decoration-none">Contact</a></li>
                        <li><a href="./aboutUs.html" class="text-white text-decoration-none">About Us</a></li>
                        <li><a href="./all_jobs.html" class="text-white text-decoration-none">Jobs</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="text-uppercase fw-bold mb-4">Policies</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Terms and Conditions</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Refund Policy</a></li>
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