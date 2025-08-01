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
$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $session_id = intval($_POST['session_id']);

    if ($_POST['action'] === 'complete') {
        $sql = "UPDATE tutoring_sessions SET status = 'Completed' WHERE session_id = ? AND tutor_id = ?";
    } elseif ($_POST['action'] === 'confirm') {
        $sql = "UPDATE tutoring_sessions SET status = 'Confirmed' WHERE session_id = ? AND tutor_id = ?";
    } elseif ($_POST['action'] === 'cancel') {
        $sql = "UPDATE tutoring_sessions SET status = 'Cancelled' WHERE session_id = ? AND tutor_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $session_id, $tutor_id);

    if ($stmt->execute()) {
        $message = "Session updated successfully!";
    } else {
        $error = "Error updating session: " . $stmt->error;
    }
}

// Get confirmed upcoming sessions
$sql = "SELECT s.*, CONCAT(st.first_name, ' ', st.last_name) as student_name 
        FROM tutoring_sessions s
        JOIN students st ON s.student_id = st.student_id
        WHERE s.tutor_id = ? AND s.status = 'Confirmed' AND s.session_date >= CURDATE()
        ORDER BY s.session_date, s.session_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$upcoming_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pending requests
$sql = "SELECT s.*, CONCAT(st.first_name, ' ', st.last_name) as student_name 
        FROM tutoring_sessions s
        JOIN students st ON s.student_id = st.student_id
        WHERE s.tutor_id = ? AND s.status = 'Scheduled'
        ORDER BY s.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Sessions - Tutor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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

        .session-card {
            border-left: 4px solid #220359;
            transition: transform 0.3s;
        }

        .session-card:hover {
            transform: translateY(-3px);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .badge-confirmed {
            background-color: #28a745;
        }

        .badge-scheduled {
            background-color: #ffc107;
            color: #000;
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
            <div class="col-md-10 mx-auto">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="sessionsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab"
                            data-bs-target="#upcoming" type="button" role="tab">
                            Upcoming Sessions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests"
                            type="button" role="tab">
                            Session Requests (<?= count($pending_requests) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history"
                            type="button" role="tab">
                            <a href="session_history.php" class="text-decoration-none">Session History</a>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="sessionsTabContent">
                    <!-- Upcoming Sessions Tab -->
                    <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                        <h4 class="mb-3">Confirmed Sessions</h4>

                        <?php if (count($upcoming_sessions) > 0): ?>
                            <div class="row g-4">
                                <?php foreach ($upcoming_sessions as $session): ?>
                                    <div class="col-12">
                                        <div class="card session-card shadow-sm mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1"><?= htmlspecialchars($session['subject']) ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Student:</strong>
                                                            <?= htmlspecialchars($session['student_name']) ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Date:</strong>
                                                            <?= date('F j, Y', strtotime($session['session_date'])) ?>
                                                        </p>
                                                        <p class="mb-0">
                                                            <strong>Time:</strong>
                                                            <?= date('g:i A', strtotime($session['session_time'])) ?>
                                                        </p>
                                                    </div>
                                                    <span class="badge status-badge badge-confirmed">
                                                        <?= $session['status'] ?>
                                                    </span>
                                                </div>

                                                <form method="POST" class="mt-3 text-end">
                                                    <input type="hidden" name="session_id"
                                                        value="<?= $session['session_id'] ?>">
                                                    <button type="submit" name="action" value="complete"
                                                        class="btn btn-success btn-sm me-2">
                                                        Mark as Completed
                                                    </button>
                                                    <button type="submit" name="action" value="cancel"
                                                        class="btn btn-danger btn-sm">
                                                        Cancel Session
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You have no upcoming confirmed sessions.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Session Requests Tab -->
                    <div class="tab-pane fade" id="requests" role="tabpanel">
                        <h4 class="mb-3">Session Requests</h4>

                        <?php if (count($pending_requests) > 0): ?>
                            <div class="row g-4">
                                <?php foreach ($pending_requests as $request): ?>
                                    <div class="col-12">
                                        <div class="card session-card shadow-sm mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1"><?= htmlspecialchars($request['subject']) ?></h5>
                                                        <p class="mb-1">
                                                            <strong>Student:</strong>
                                                            <?= htmlspecialchars($request['student_name']) ?>
                                                        </p>
                                                        <p class="mb-1">
                                                            <strong>Date:</strong>
                                                            <?= date('F j, Y', strtotime($request['session_date'])) ?>
                                                        </p>
                                                        <p class="mb-0">
                                                            <strong>Time:</strong>
                                                            <?= date('g:i A', strtotime($request['session_time'])) ?>
                                                        </p>
                                                    </div>
                                                    <span class="badge status-badge badge-scheduled">
                                                        <?= $request['status'] ?>
                                                    </span>
                                                </div>

                                                <div class="d-flex justify-content-end mt-3">
                                                    <form method="POST" class="me-2">
                                                        <input type="hidden" name="session_id"
                                                            value="<?= $request['session_id'] ?>">
                                                        <button type="submit" name="action" value="confirm"
                                                            class="btn btn-primary btn-sm">
                                                            Confirm Session
                                                        </button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="session_id"
                                                            value="<?= $request['session_id'] ?>">
                                                        <button type="submit" name="action" value="cancel"
                                                            class="btn btn-outline-danger btn-sm">
                                                            Decline
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You have no pending session requests.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
</body>

</html>