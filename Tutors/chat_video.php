<?php
session_start();

// Redirect if not logged in as tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit;
}

// Database configuration
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tutor_id = $_SESSION['user_id'];
$active_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$message = '';
$error = '';

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $student_id = intval($_POST['student_id']);
    $content = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO tutor_messages (tutor_id, student_id, content, sender) 
            VALUES (?, ?, ?, 'tutor')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $tutor_id, $student_id, $content);

    if (!$stmt->execute()) {
        $error = "Error sending message: " . $stmt->error;
    }
}

// Handle video session creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $student_id = intval($_POST['student_id']);
    $session_token = bin2hex(random_bytes(16));
    $scheduled_time = $conn->real_escape_string($_POST['scheduled_time']);

    $sql = "INSERT INTO video_sessions (tutor_id, student_id, session_token, scheduled_time) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $tutor_id, $student_id, $session_token, $scheduled_time);

    if ($stmt->execute()) {
        $message = "Video session scheduled successfully!";
    } else {
        $error = "Error scheduling session: " . $stmt->error;
    }
}

// Get students who have communicated with tutor
$sql = "SELECT DISTINCT s.student_id, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.photo
        FROM tutor_messages m
        JOIN students s ON m.student_id = s.student_id
        WHERE m.tutor_id = ?
        ORDER BY MAX(m.created_at) DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get messages for active student
$messages = [];
$video_sessions = [];
if ($active_student) {
    // Mark messages as read
    $sql = "UPDATE tutor_messages SET is_read = TRUE 
            WHERE tutor_id = ? AND student_id = ? AND sender = 'student'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tutor_id, $active_student);
    $stmt->execute();

    // Get messages
    $sql = "SELECT * FROM tutor_messages 
            WHERE tutor_id = ? AND student_id = ?
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tutor_id, $active_student);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get video sessions
    $sql = "SELECT * FROM video_sessions 
            WHERE tutor_id = ? AND student_id = ?
            ORDER BY scheduled_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $tutor_id, $active_student);
    $stmt->execute();
    $video_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Communication - Tutor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            transition: background 0.3s, color 0.3s;
        }

        .navbar {
            padding: 1rem 2rem;
        }

        .navbar-brand img {
            height: 80px;
        }

        .hero-section {
            background: linear-gradient(to right, #220359, #4906bf);
            color: white;
            padding: 4rem 0;
            text-align: center;
            position: relative;
        }

        /* Chat styles */
        .chat-container {
            display: flex;
            height: 70vh;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .student-list {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .messages-container {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }

        .message-input {
            padding: 15px;
            border-top: 1px solid #ddd;
            background-color: white;
        }

        .student-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }

        .student-item:hover,
        .student-item.active {
            background-color: #f0f0f0;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #220359;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }

        .message-student {
            align-self: flex-start;
            background-color: #e9ecef;
            border-radius: 0 15px 15px 15px;
            padding: 10px 15px;
        }

        .message-tutor {
            align-self: flex-end;
            background-color: #220359;
            color: white;
            border-radius: 15px 0 15px 15px;
            padding: 10px 15px;
        }

        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .video-session-card {
            border-left: 3px solid #220359;
            margin-bottom: 10px;
        }

        .badge-scheduled {
            background-color: #ffc107;
            color: #000;
        }

        .badge-active {
            background-color: #28a745;
        }

        .badge-completed {
            background-color: #6c757d;
        }

        .badge-cancelled {
            background-color: #dc3545;
        }

        footer {
            background-color: #000;
            color: white;
            padding: 3rem 0;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="ms-3 fw-bold">Tutor Dashboard</span>
            </a>
            <a href="tutor_dashboard.php" class="btn btn-outline-secondary me-2" style="margin-left:10px;">
                Back to Your Dashboard
            </a>
        </div>
        <form action="../logout.php" method="post" style="display:inline;">
            <button type="submit"
                style="background:#d9534f;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">
                Logout
            </button>
        </form>
    </nav>

    <!-- MAIN CONTENT -->
    <section class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">💬 Student Communication</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="chat-container">
                    <!-- Student List -->
                    <div class="student-list">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <a href="?student_id=<?= $student['student_id'] ?>" class="text-decoration-none text-dark">
                                    <div class="student-item <?= $active_student == $student['student_id'] ? 'active' : '' ?>">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($student['photo'])): ?>
                                                <img src="<?= htmlspecialchars($student['photo']) ?>" class="student-avatar"
                                                    alt="<?= htmlspecialchars($student['student_name']) ?>">
                                            <?php else: ?>
                                                <div class="default-avatar">
                                                    <?= substr($student['student_name'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($student['student_name']) ?></h6>
                                                <small class="text-muted">Click to chat</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                No students have contacted you yet.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Area -->
                    <div class="chat-area">
                        <?php if ($active_student): ?>
                            <?php
                            $active_student_data = array_filter($students, function ($s) use ($active_student) {
                                return $s['student_id'] == $active_student;
                            });
                            $active_student_data = reset($active_student_data);
                            ?>
                            <div class="messages-container" id="messagesContainer">
                                <?php if (count($messages) > 0): ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div
                                            class="message <?= $msg['sender'] === 'student' ? 'message-student' : 'message-tutor' ?>">
                                            <div><?= htmlspecialchars($msg['content']) ?></div>
                                            <div class="message-time">
                                                <?= date('M j, g:i a', strtotime($msg['created_at'])) ?>
                                                <?php if ($msg['sender'] === 'student' && !$msg['is_read']): ?>
                                                    <span class="badge bg-primary">New</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                                        No messages yet. Start the conversation!
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Message Input -->
                            <div class="message-input">
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="student_id" value="<?= $active_student ?>">
                                    <input type="text" name="message" class="form-control me-2"
                                        placeholder="Type your message..." required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <div class="text-center text-muted">
                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                    <h4>Select a student to start chatting</h4>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Video Sessions Section -->
                <?php if ($active_student): ?>
                    <div class="mt-5">
                        <h4 class="mb-3">
                            <i class="fas fa-video me-2"></i> Video Sessions with
                            <?= htmlspecialchars($active_student_data['student_name']) ?>
                        </h4>

                        <!-- Schedule New Session -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Schedule New Video Session</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="student_id" value="<?= $active_student ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="scheduled_time" class="form-label">Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="scheduled_time"
                                                name="scheduled_time" required>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end mb-3">
                                            <button type="submit" name="create_session" class="btn btn-primary">
                                                <i class="fas fa-calendar-plus me-2"></i> Schedule Session
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Scheduled Sessions -->
                        <?php if (count($video_sessions) > 0): ?>
                            <div class="row g-3">
                                <?php foreach ($video_sessions as $session): ?>
                                    <div class="col-md-6">
                                        <div class="card video-session-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <?= date('M j, Y g:i A', strtotime($session['scheduled_time'])) ?>
                                                        </h5>
                                                        <p class="mb-0">
                                                            Session ID: <?= $session['session_id'] ?>
                                                        </p>
                                                    </div>
                                                    <span class="badge badge-<?= strtolower($session['status']) ?>">
                                                        <?= $session['status'] ?>
                                                    </span>
                                                </div>

                                                <?php if ($session['status'] === 'scheduled' || $session['status'] === 'active'): ?>
                                                    <div class="d-flex justify-content-end mt-3">
                                                        <?php if ($session['status'] === 'scheduled'): ?>
                                                            <a href="#" class="btn btn-sm btn-outline-danger me-2">
                                                                Cancel
                                                            </a>
                                                        <?php endif; ?>

                                                        <a href="video_call.php?token=<?= $session['session_token'] ?>"
                                                            class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-video me-1"></i>
                                                            <?= $session['status'] === 'active' ? 'Join Call' : 'Start Call' ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No video sessions scheduled yet.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SkillConnect</h5>
                    <p>Empowering Tutors to Connect & Educate</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2025 SkillConnect. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript for chat functionality -->
    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Refresh messages every 5 seconds
        <?php if ($active_student): ?>
            setInterval(() => {
                fetch(`get_messages.php?tutor_id=<?= $tutor_id ?>&student_id=<?= $active_student ?>`)
                    .then(response => response.json())
                    .then(messages => {
                        // Update messages display
                        // (Would implement proper DOM update in production)
                        if (messages.length > <?= count($messages) ?>) {
                            location.reload(); // Simple refresh for demo
                        }
                    });
            }, 5000);
        <?php endif; ?>
    </script>
</body>

</html>