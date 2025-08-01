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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $file_type = $conn->real_escape_string($_POST['file_type']);

    $uploadDir = 'uploads/resources/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid('resource_') . '_' . basename($_FILES['resource_file']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $targetPath)) {
        $sql = "INSERT INTO tutor_resources (tutor_id, title, description, file_path, file_type) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $tutor_id, $title, $description, $targetPath, $file_type);

        if ($stmt->execute()) {
            $message = "Resource uploaded successfully!";
        } else {
            $error = "Error saving to database: " . $stmt->error;
            unlink($targetPath); // Remove uploaded file if DB insert fails
        }
    } else {
        $error = "Error uploading file.";
    }
}

// Get tutor's resources
$sql = "SELECT * FROM tutor_resources WHERE tutor_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Resources - Tutor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Copy all your existing CSS styles from tutor_dashboard.php */
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

        .hero-section h1 {
            font-size: 2.5rem;
        }

        .hero-section .highlight {
            background-color: #ffc107;
            color: #000;
            padding: 0 6px;
            border-radius: 4px;
        }

        .floating-box {
            position: absolute;
            background: white;
            color: #000;
            padding: 8px 12px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .floating-box.students {
            top: 20px;
            right: 0;
        }

        .floating-box.enrolled {
            bottom: 20px;
            left: 0;
        }

        .courses-section {
            padding: 5rem 0;
        }

        .course-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-card .card-header {
            background-color: #220359;
            color: white;
            font-weight: bold;
        }

        .course-card .enrollment-badge {
            background-color: #ffc107;
            color: #000;
        }

        .add-course-card {
            border: 2px dashed #220359;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            height: 100%;
            min-height: 200px;
            transition: all 0.3s;
        }

        .add-course-card:hover {
            background-color: #f8f9fa;
            border-color: #4906bf;
        }

        .add-course-card .add-icon {
            font-size: 2rem;
            color: #220359;
        }

        .dashboard-section .card {
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .dashboard-section .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card {
            height: 100%;
        }

        footer {
            background-color: #000;
            color: white;
            padding: 3rem 0;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 1.8rem;
            }
        }

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

        /* Add any additional styles you need */
        .resource-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }

        .resource-card:hover {
            transform: translateY(-5px);
        }

        .resource-card .card-header {
            background-color: #220359;
            color: white;
            font-weight: bold;
        }

        .file-icon {
            font-size: 2rem;
            margin-right: 10px;
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
            <div class="col-md-8 mx-auto">
                <h2 class="mb-4">🗂️ Your Resources</h2>

                <!-- Success/Error Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <!-- Upload Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Upload New Resource
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title*</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="file_type" class="form-label">Resource Type*</label>
                                <select class="form-select" id="file_type" name="file_type" required>
                                    <option value="document">Document (PDF, Word, etc.)</option>
                                    <option value="video">Video</option>
                                    <option value="audio">Audio</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="resource_file" class="form-label">File*</label>
                                <input type="file" class="form-control" id="resource_file" name="resource_file"
                                    required>
                                <small class="text-muted">Max file size: 10MB</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Resource</button>
                        </form>
                    </div>
                </div>

                <!-- Resources List -->
                <h4 class="mt-5 mb-3">Your Uploaded Resources</h4>

                <?php if (count($resources) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($resources as $resource): ?>
                            <div class="col-md-6">
                                <div class="card resource-card shadow-sm h-100">
                                    <div class="card-header d-flex align-items-center">
                                        <?php
                                        $icon = match ($resource['file_type']) {
                                            'document' => '📄',
                                            'video' => '🎬',
                                            'audio' => '🎧',
                                            default => '📁'
                                        };
                                        ?>
                                        <span class="file-icon"><?= $icon ?></span>
                                        <?= htmlspecialchars($resource['title']) ?>
                                    </div>
                                    <div class="card-body">
                                        <p><?= htmlspecialchars($resource['description']) ?></p>
                                        <small class="text-muted">
                                            Uploaded: <?= date('M j, Y', strtotime($resource['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="card-footer bg-white d-flex justify-content-between">
                                        <a href="<?= $resource['file_path'] ?>" class="btn btn-sm btn-outline-primary" download>
                                            Download
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger delete-resource"
                                            data-id="<?= $resource['resource_id'] ?>">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't uploaded any resources yet.
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

    <!-- JavaScript for delete functionality -->
    <script>
        document.querySelectorAll('.delete-resource').forEach(button => {
            button.addEventListener('click', function () {
                if (confirm('Are you sure you want to delete this resource?')) {
                    const resourceId = this.getAttribute('data-id');
                    fetch('delete_resource.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `resource_id=${resourceId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('.col-md-6').remove();
                            } else {
                                alert('Error deleting resource: ' + data.message);
                            }
                        });
                }
            });
        });
    </script>
</body>

</html>