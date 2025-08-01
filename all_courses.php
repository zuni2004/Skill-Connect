<?php
// Database connection
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$courses = [];
$sql = "SELECT * FROM courses WHERE is_approved = 1 AND is_active = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
  $courses[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Courses | SkillConnect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

    .navbar-nav .nav-link {
      font-weight: 600;
      margin: 0 10px;
    }

    .nav-icons i {
      font-size: 1.3rem;
      margin-right: 15px;
      color: #1e1e2f;
      cursor: pointer;
    }

    .nav-buttons .btn {
      margin-left: 10px;
    }

    .navbar-brand img {
      height: 110px;
      transition: height 0.3s;
    }

    @media (max-width: 768px) {
      .navbar-brand img {
        height: 70px;
      }
    }

    .courses-list-section {
      padding: 3rem 0;
      background: #f8f9fa;
      min-height: 60vh;
    }

    .course-card {
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .course-card:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container-fluid">
      <!-- Logo -->
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="./logo.jpeg" alt="SkillConnect Logo" />
      </a>

      <!-- Navigation links + Icons + Buttons -->
      <div class="collapse navbar-collapse justify-content-between">
        <!-- Center nav links -->
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="./landing_page.html">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./all_jobs.php">All Jobs</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./all_courses.php">All Courses</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown"
              aria-expanded="false">
              Services
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
              <li>
                <a class="dropdown-item" href="./registration_form_students_tutor.html">Tutoring</a>
              </li>
              <li>
                <a class="dropdown-item" href="./registration_form_jobSeekers_Employeers.html">Job Matching</a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./faq.html">Contact</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./aboutUs.html">About Us</a>
          </li>
        </ul>

        <!-- Right side icons and buttons -->
        <div class="d-flex align-items-center nav-icons">
          <div class="nav-buttons">
            <a href="./login.html" class="btn btn-primary">Login</a>
            <!-- Register dropdown for all registration options -->
            <div class="btn-group">
              <a href="#" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                Register
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item" href="./registration_form_students_tutor.html">
                    Student / Tutor Registration
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="./registration_form_employer.html">
                    Employer Registration
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="./registration_form_jobseeker.html">
                    Job Seeker Registration
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <section class="courses-list-section">
    <div class="container">
      <h2 class="fw-bold mb-4 text-center">All Courses</h2>
      <div class="row g-4">
        <?php if (empty($courses)): ?>
          <div class="col-12">
            <div class="alert alert-info text-center">No courses found.</div>
          </div>
        <?php else: ?>
          <?php foreach ($courses as $course): ?>
            <div class="col-md-4">
              <div class="card course-card h-100">
                <img
                  src="<?= htmlspecialchars($course['image_url'] ?: 'https://images.unsplash.com/photo-1541462608143-67571c6738dd?auto=format&fit=crop&w=500&q=80') ?>"
                  class="card-img-top" alt="<?= htmlspecialchars($course['title']) ?>" />
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
                  <p class="card-text">
                    <?= htmlspecialchars(mb_strimwidth($course['description'], 0, 100, "...")) ?>
                  </p>
                  <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='login.html'">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <footer class="bg-dark text-white pt-5 pb-4">
    <div class="container">
      <div class="row">
        <!-- Logo and Tagline -->
        <div class="col-lg-4 mb-4">
          <a class="navbar-brand d-flex align-items-center mb-3" href="#">
            <img src="./logo.jpeg" alt="SkillConnect Logo" height="60" />
          </a>
          <p class="text-muted">
            Temporary minds. Share skills. Shape the future – with
            skillConnect.
          </p>
          <p class="text-muted small">
            © 2025 SkillConnect. All rights reserved.
          </p>
          <p class="text-muted small mb-0">Group B</p>
        </div>

        <!-- Quick Links -->
        <div class="col-lg-2 col-md-4 mb-4">
          <h5 class="text-uppercase fw-bold mb-4">All Pages</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="./landing_page.html" class="text-white text-decoration-none">Home</a>
            </li>
            <li class="mb-2">
              <a href="./all_courses.php" class="text-white text-decoration-none">All Courses</a>
            </li>
            <li class="mb-2">
              <a href="./faq.html" class="text-white text-decoration-none">Contact</a>
            </li>
            <li class="mb-2">
              <a href="./aboutUs.html" class="text-white text-decoration-none">About Us</a>
            </li>
            <li class="mb-2">
              <a href="./all_jobs.php" class="text-white text-decoration-none">Jobs</a>
            </li>
          </ul>
        </div>

        <!-- Policies -->
        <div class="col-lg-3 col-md-4 mb-4">
          <h5 class="text-uppercase fw-bold mb-4">Policies</h5>
          <ul class="list-unstyled">
            <li class="mb-2">
              <a href="#" class="text-white text-decoration-none">Privacy Policy</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white text-decoration-none">Terms and Conditions</a>
            </li>
            <li class="mb-2">
              <a href="#" class="text-white text-decoration-none">Refund and Returns Policy</a>
            </li>
          </ul>
        </div>
      </div>
      <hr class="my-4 bg-secondary" />
      <div class="row">
        <div class="col-md-6 text-center text-md-start">
          <p class="small text-white mb-0">
            © 2025 SkillConnect. All rights reserved.
          </p>
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