<?php
session_start();

// Check student login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// DB Config
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

$student_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch enrolled courses for subject dropdown (fetching course title)
$enrolled_courses = [];
$course_stmt = $conn->prepare(
    "SELECT c.course_id, c.title 
     FROM student_courses sc 
     JOIN courses c ON sc.course_id = c.course_id 
     WHERE sc.student_id = ?"
);
$course_stmt->bind_param("i", $student_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
while ($row = $course_result->fetch_assoc()) {
    $enrolled_courses[] = $row;
}
$course_stmt->close();

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutor_name = trim($_POST['tutor_name']);
    $subject = trim($_POST['subject']);
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];

    // Validate required fields
    if (!$tutor_name || !$subject || !$session_date || !$session_time) {
        $error = "All fields are required.";
    } else {
        // Find approved tutor
        $stmt = $conn->prepare("SELECT id FROM tutors WHERE name = ? AND is_approved = 1 LIMIT 1");
        $stmt->bind_param("s", $tutor_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $tutor_id = $row['id'];

            // Insert session
            $insert = $conn->prepare("INSERT INTO tutoring_sessions (tutor_id, student_id, subject, session_date, session_time, status, created_at)
                                      VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW())");
            $insert->bind_param("iisss", $tutor_id, $student_id, $subject, $session_date, $session_time);

            if ($insert->execute()) {
                $success = "Session request sent successfully to $tutor_name.";
            } else {
                $error = "Failed to request session: " . $conn->error;
            }
        } else {
            $error = "No approved tutor found with that name.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Request a Tutoring Session</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Request a Tutoring Session</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="bg-white p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="tutor_name" class="form-label">Tutor Name</label>
                <input type="text" class="form-control" name="tutor_name" required>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <select class="form-select" name="subject" required>
                    <option value="" disabled selected>Select a subject</option>
                    <?php foreach ($enrolled_courses as $subj): ?>
                        <option value="<?= htmlspecialchars($subj['title']) ?>"><?= htmlspecialchars($subj['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="session_date" class="form-label">Session Date</label>
                <input type="date" class="form-control" name="session_date" required>
            </div>
            <div class="mb-3">
                <label for="session_time" class="form-label">Session Time</label>
                <input type="time" class="form-control" name="session_time" required>
            </div>
            <div class="mb-3">
                <label for="course_id" class="form-label">Course</label>
                <select class="form-select" name="course_id" required>
                    <option value="" disabled selected>Select a course</option>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <option value="<?= htmlspecialchars($course['course_id']) ?>">
                            <?= htmlspecialchars($course['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Request Session</button>
        </form>

        <a href="browse_gigs_new.html" class="btn btn-link mt-3">← Back to Dashboard</a>
    </div>
</body>

</html>