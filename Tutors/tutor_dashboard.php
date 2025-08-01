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

// Get tutor's courses
$tutor_id = $_SESSION['user_id'];
$sql = "SELECT * FROM courses WHERE tutor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tutor Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Your Custom Styles -->
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
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <span class="ms-3 fw-bold">Tutor Dashboard</span>
      </a>
    </div>
    <form action="../logout.php" method="post" style="display:inline;">
      <button type="submit"
        style="background:#d9534f;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">
        Logout
      </button>
    </form>
  </nav>

  <!-- HERO -->
  <section class="hero-section">
    <div class="container">
      <h1>Welcome, <span class="highlight">Tutor!</span></h1>
      <p class="lead">Manage your courses and student schedules here.</p>
    </div>
  </section>

  <!-- YOUR COURSES SECTION -->
  <section class="courses-section bg-light">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Your Course Offerings</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
          + Add New Course
        </button>
      </div>

      <div class="row g-4">
        <?php if (count($courses) > 0): ?>
          <?php foreach ($courses as $course): ?>
            <!-- Course Card -->
            <div class="col-md-4">
              <div class="card course-card shadow-sm">
                <div class="card-header" style="background-color: #220359; color: white;">
                  <?= htmlspecialchars($course['title']) ?>
                </div>
                <div class="card-body">
                  <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-<?= $course['rating'] > 3 ? 'success' : 'warning' ?>">
                      <?= number_format($course['rating'], 1) ?> ★
                    </span>
                    <span class="badge enrollment-badge">
                      <?= $course['fee_amount'] ?>     <?= $course['fee_type'] ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 text-center py-5">
            <h4>You haven't created any courses yet</h4>
            <p>Click the "Add New Course" button to get started</p>
          </div>
        <?php endif; ?>

        <div class="col-md-4">
          <div class="add-course-card rounded shadow-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <div class="text-center">
              <div class="add-icon mb-2">+</div>
              <h5>Add New Course</h5>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- DASHBOARD OPTIONS -->
  <section class="dashboard-section py-5">
    <div class="container">
      <h2 class="mb-4 text-center">Actions</h2>
      <div class="row g-4">



        

        <!-- Schedule Sessions Card -->
        <div class="col-md-4">
          <a href="schedule_sessions.php" class="text-decoration-none">
            <div class="card course-card shadow-sm h-100">
              <div class="card-header" style="background-color: #220359; color: white;">
                📅 Schedule Sessions
              </div>
              <div class="card-body">
                <p class="card-text">Manage your tutoring schedule and confirm session requests.</p>
              </div>
            </div>
          </a>
        </div>

        <!-- Communication Card -->
        <div class="col-md-4">
          <a href="chat_video.php" class="text-decoration-none">
            <div class="card course-card shadow-sm h-100">
              <div class="card-header" style="background-color: #220359; color: white;">
                💬 Communication
              </div>
              <div class="card-body">
                <p class="card-text">Chat with students and start video calls for your sessions.</p>
              </div>
            </div>
          </a>
        </div>

        <!-- Resources Card -->
        <div class="col-md-4">
          <a href="resources.php" class="text-decoration-none">
            <div class="card course-card shadow-sm h-100">
              <div class="card-header" style="background-color: #220359; color: white;">
                🗂️ Resources
              </div>
              <div class="card-body">
                <p class="card-text">Upload notes, share recorded sessions, and manage folders.</p>
              </div>
            </div>
          </a>
        </div>

        <!-- Earnings Card -->
        <div class="col-md-4">
          <a href="earnings.html" class="text-decoration-none">
            <div class="card course-card shadow-sm h-100">
              <div class="card-header" style="background-color: #220359; color: white;">
                💵 Earnings
              </div>
              <div class="card-body">
                <p class="card-text">Track payments, view earnings, and withdraw funds.</p>
              </div>
            </div>
          </a>
        </div>

        <!-- Reviews & Ratings Card -->
        <div class="col-md-4">
          <a href="reviews.php" class="text-decoration-none">
            <div class="card course-card shadow-sm h-100">
              <div class="card-header" style="background-color: #220359; color: white;">
                ⭐ Reviews & Ratings
              </div>
              <div class="card-body">
                <p class="card-text">See student feedback and respond to improve your rating.</p>
                <div class="d-flex align-items-center mt-2">
                  <span class="text-warning me-2">★★★★☆</span>
                  <small class="text-muted">4.2/5.0 (24 reviews)</small>
                </div>
              </div>
            </div>
          </a>
        </div>

      </div>
    </div>
  </section>

  <!-- Add Course Modal -->
  <!-- Add Course Modal -->
  <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="courseForm" action="add_course.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="row">
              <!-- Left Column -->
              <div class="col-md-6">
                <!-- Title -->
                <div class="mb-3">
                  <label for="courseTitle" class="form-label">Course Title*</label>
                  <input type="text" class="form-control" id="courseTitle" name="title" required minlength="5"
                    maxlength="100">
                </div>

                <!-- Subject -->
                <div class="mb-3">
                  <label for="courseSubject" class="form-label">Subject*</label>
                  <select class="form-select" id="courseSubject" name="subject" required>
                    <option value="" disabled selected>Select subject</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Science">Science</option>
                    <option value="English">English</option>
                    <option value="Programming">Programming</option>
                    <option value="Test Preparation">Test Preparation</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <!-- Location -->
                <div class="mb-3">
                  <label for="courseLocation" class="form-label">Location*</label>
                  <select class="form-select" id="courseLocation" name="location" required>
                    <option value="" disabled selected>Select location</option>
                    <option value="Online">Online</option>
                    <option value="In-person">In-person</option>
                    <option value="Hybrid">Hybrid</option>
                  </select>
                </div>

                <!-- Mode -->
                <div class="mb-3">
                  <label for="courseMode" class="form-label">Teaching Mode*</label>
                  <select class="form-select" id="courseMode" name="mode" required>
                    <option value="" disabled selected>Select mode</option>
                    <option value="One-on-One">One-on-One</option>
                    <option value="Group">Group</option>
                    <option value="Both">Both</option>
                  </select>
                </div>
              </div>

              <!-- Right Column -->
              <div class="col-md-6">
                <!-- Fee Type -->
                <div class="mb-3">
                  <label for="courseFeeType" class="form-label">Fee Type*</label>
                  <select class="form-select" id="courseFeeType" name="fee_type" required>
                    <option value="" disabled selected>Select fee type</option>
                    <option value="Per Hour">Per Hour</option>
                    <option value="Per Session">Per Session</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Full Course">Full Course</option>
                  </select>
                </div>

                <!-- Fee Amount -->
                <div class="mb-3">
                  <label for="courseFeeAmount" class="form-label">Fee Amount*</label>
                  <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="courseFeeAmount" name="fee_amount" min="0" step="0.01"
                      required>
                  </div>
                </div>

                <!-- Image Upload -->
                <div class="mb-3">
                  <label for="courseImage" class="form-label">Course Image</label>
                  <input type="file" class="form-control" id="courseImage" name="course_image" accept="image/*">
                  <small class="text-muted">Max size: 2MB (JPEG, PNG)</small>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="rating" value="0">
                <input type="hidden" name="tutor_id"
                  value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                <input type="hidden" name="created_at" value="<?php echo date('Y-m-d H:i:s'); ?>">
              </div>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="courseDescription" class="form-label">Description*</label>
              <textarea class="form-control" id="courseDescription" name="description" rows="4" required minlength="20"
                maxlength="500"></textarea>
              <small class="text-muted">Minimum 20 characters</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitCourseBtn">Add Course</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JavaScript Validation -->
  <script>
    document.getElementById('courseForm').addEventListener('submit', function (e) {
      const submitBtn = document.getElementById('submitCourseBtn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

      // You can add additional client-side validation here if needed
      return true;
    });
  </script>

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