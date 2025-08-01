<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";

// MySQLi connection
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if (empty($username) || empty($password)) {
  die("Please fill all fields.");
}

if ($role === 'student') {
  // Check students table
  $sql = "SELECT * FROM students WHERE username=? OR email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  if ($user && password_verify($password, $user['password'])) {
    if ($user['is_approved']) {
      $_SESSION['user_id'] = $user['student_id'];
      $_SESSION['role'] = 'student';
      header("Location: ./Students/browse_gigs_new.html");
      exit;
    } else {
      echo '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="5;url=login.html" />
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .centered {
      margin: 100px auto;
      padding: 30px 40px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      text-align: center;
    }
    .centered h2 { color: #d9534f; }
    .centered p { color: #333; }
  </style>
</head>
<body>
  <div class="centered">
    <h2>Account Not Approved</h2>
    <p>Your account is not approved by the admin yet.<br>
    Redirecting to login page in 5 seconds...</p>
  </div>
</body>
</html>';
      exit;
    }
  }
} elseif ($role === 'tutor') {
  // Check tutors table
  $sql = "SELECT * FROM tutors WHERE username=? OR email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  if ($user && password_verify($password, $user['password'])) {
    if ($user['is_approved']) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = 'tutor';
      header("Location: ./Tutors/tutor_dashboard.php");
      exit;
    } else {
      echo '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="5;url=login.html" />
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .centered {
      margin: 100px auto;
      padding: 30px 40px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      text-align: center;
    }
    .centered h2 { color: #d9534f; }
    .centered p { color: #333; }
  </style>
</head>
<body>
  <div class="centered">
    <h2>Account Not Approved</h2>
    <p>Your account is not approved by the admin yet.<br>
    Redirecting to login page in 5 seconds...</p>
  </div>
</body>
</html>';
      exit;
    }
  }
} elseif ($role === 'employer') {
  // Check employers table
  $sql = "SELECT * FROM employers WHERE username=? OR email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  if ($user && password_verify($password, $user['password'])) {
    if ($user['is_approved']) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = 'employer';
      header("Location: ./Employers/employer_dashboard.php");
      exit;
    } else {
      echo '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="5;url=login.html" />
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .centered {
      margin: 100px auto;
      padding: 30px 40px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      text-align: center;
    }
    .centered h2 { color: #d9534f; }
    .centered p { color: #333; }
  </style>
</head>
<body>
  <div class="centered">
    <h2>Account Not Approved</h2>
    <p>Your account is not approved by the admin yet.<br>
    Redirecting to login page in 5 seconds...</p>
  </div>
</body>
</html>';
      exit;
    }
  }
} elseif ($role === 'jobseeker') {
  // Check job_seekers table
  $sql = "SELECT * FROM job_seekers WHERE username=? OR email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  if ($user && password_verify($password, $user['password'])) {
    if ($user['is_approved']) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = 'jobseeker';
      header("Location: ./Job_Seekers/jobseeker_dashboard.php");
      exit;
    } else {
      echo '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="5;url=login.html" />
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    .centered {
      margin: 100px auto;
      padding: 30px 40px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      text-align: center;
    }
    .centered h2 { color: #d9534f; }
    .centered p { color: #333; }
  </style>
</head>
<body>
  <div class="centered">
    <h2>Account Not Approved</h2>
    <p>Your account is not approved by the admin yet.<br>
    Redirecting to login page in 5 seconds...</p>
  </div>
</body>
</html>';
      exit;
    }
  }
} elseif ($role === 'admin') {
  // Check admins table
  $sql = "SELECT * FROM admins WHERE username=? OR email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $username, $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  if ($user && $password === $user['password']) {
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['role'] = 'admin';
    header("Location: ./admin/admin_dashboard.html");
    exit;
  } else {
    // Debugging
    if (!$user) {
      echo "No admin found with that username/email.";
    } elseif ($password !== $user['password']) {
      echo "Password incorrect for admin.";
    }
    exit;
  }
}

echo "Invalid credentials.";
$conn->close();
?>