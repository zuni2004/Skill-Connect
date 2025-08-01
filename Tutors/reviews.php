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

// Handle review response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['response'])) {
    $review_id = intval($_POST['review_id']);
    $response = $conn->real_escape_string($_POST['response']);
    
    $sql = "UPDATE tutor_reviews SET response = ? WHERE review_id = ? AND tutor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $response, $review_id, $tutor_id);
    
    if ($stmt->execute()) {
        $message = "Response submitted successfully!";
    } else {
        $error = "Error saving response: " . $stmt->error;
    }
}

// Get tutor's reviews with student names
$sql = "SELECT r.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.photo as student_photo
        FROM tutor_reviews r
        JOIN students s ON r.student_id = s.student_id
        WHERE r.tutor_id = ?
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum / $total_reviews, 1);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <!-- [Rest of your head content remains the same] -->
    <style>
        /* [Previous styles remain the same] */
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
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .default-avatar {
            background-color: #220359;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- [Header remains the same] -->
  <nav class="navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="tutor_dashboard.php">
                <img src="./logo.jpeg" alt="SkillConnect Logo" />
                <span class="ms-3 fw-bold">SkillConnect Reviews</span>
            </a>
        </div>
    </nav>
    <!-- MAIN CONTENT -->
    <section class="container my-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- [Summary Card remains the same] -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <h1 class="display-4 mb-0"><?= $avg_rating ?></h1>
                                <div class="rating-stars mb-2">
                                    <?= str_repeat('★', floor($avg_rating)) ?><?= str_repeat('☆', 5 - floor($avg_rating)) ?>
                                </div>
                                <small class="text-muted">Average Rating</small>
                            </div>
                            <div class="col-md-8">
                                <div class="progress mb-2" style="height: 20px;">
                                    <?php
                                    $rating_counts = array_count_values(array_column($reviews, 'rating'));
                                    for ($i = 5; $i >= 1; $i--):
                                        $count = $rating_counts[$i] ?? 0;
                                        $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $percentage ?>%" 
                                         title="<?= $i ?> stars: <?= $count ?> reviews">
                                        <?= $i ?>★
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-0 text-end">
                                    <span class="fw-bold"><?= $total_reviews ?></span> total reviews
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews List -->
                <h4 class="mt-5 mb-3">Student Feedback</h4>
                
                <?php if (count($reviews) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="col-12">
                                <div class="card review-card shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if (!empty($review['student_photo'])): ?>
                                                <img src="<?= htmlspecialchars($review['student_photo']) ?>" 
                                                     class="student-avatar" 
                                                     alt="<?= htmlspecialchars($review['student_name']) ?>">
                                            <?php else: ?>
                                                <div class="student-avatar default-avatar">
                                                    <?= substr($review['student_name'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h5 class="student-name mb-0"><?= htmlspecialchars($review['student_name']) ?></h5>
                                                <small class="text-muted">
                                                    <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                                </small>
                                            </div>
                                            <div class="ms-auto rating-stars" title="Rated <?= $review['rating'] ?> out of 5">
                                                <?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?>
                                            </div>
                                        </div>
                                        <p class="mt-2"><?= htmlspecialchars($review['comment']) ?></p>
                                        
                                        <!-- Response Section -->
                                        <?php if (!empty($review['response'])): ?>
                                            <div class="bg-light p-3 mt-3 rounded">
                                                <strong>Your Response:</strong>
                                                <p><?= htmlspecialchars($review['response']) ?></p>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" class="mt-3">
                                                <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                                <div class="mb-2">
                                                    <label for="response_<?= $review['review_id'] ?>" class="form-label">Respond to this review:</label>
                                                    <textarea class="form-control" id="response_<?= $review['review_id'] ?>" 
                                                              name="response" rows="2" required></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">Submit Response</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't received any reviews yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- [Footer remains the same] -->
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
</body>
</html>