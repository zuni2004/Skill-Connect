<?php
session_start(); // ✅ Start session before accessing $_SESSION

require_once 'connect.php';

$message = "";

// ✅ Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("<p class='error'>❌ You must be logged in as a student to enroll in courses.</p>");
}

$student_id = $_SESSION['user_id'];
$message = "";
$messages = [];
$tutor_id = null;
$tutor_name = '';
$chat_mode = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_name = trim($_POST['tutor_name']);
    $content = trim($_POST['content']);

    // Get tutor_id
    $stmt = $conn->prepare("SELECT id FROM tutors WHERE name = ?");
    $stmt->bind_param("s", $tutor_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutor = $result->fetch_assoc();
    $stmt->close();

    if ($tutor) {
        $tutor_id = $tutor['id'];
        $chat_mode = true;

        // Insert message into tutor_messages
        $stmt = $conn->prepare("INSERT INTO tutor_messages (tutor_id, student_id, content, sender, created_at, is_read) VALUES (?, ?, ?, 'student', NOW(), 0)");
        $stmt->bind_param("iis", $tutor_id, $student_id, $content);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Message sent to $tutor_name</p>";
        } else {
            $message = "<p class='error'>❌ Error sending message: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p class='error'>❌ Tutor not found. Please check the name.</p>";
    }
}

// Load chat history if tutor name was submitted
if ($chat_mode || !empty($_GET['tutor_name'])) {
    if (!$chat_mode) {
        $tutor_name = trim($_GET['tutor_name']);
        $stmt = $conn->prepare("SELECT id FROM tutors WHERE name = ?");
        $stmt->bind_param("s", $tutor_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $tutor = $result->fetch_assoc();
        $stmt->close();
        if ($tutor) {
            $tutor_id = $tutor['id'];
        }
    }

    if ($tutor_id) {
        $stmt = $conn->prepare("SELECT * FROM tutor_messages WHERE student_id = ? AND tutor_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("ii", $student_id, $tutor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat with Tutor</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; padding: 30px; }
        h2, h3 { text-align: center; }
        .chat-container {
            max-width: 700px; margin: 20px auto; background: white;
            padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .message-box {
            display: flex; margin-bottom: 10px;
        }
        .student-msg {
            background-color: #d9edf7; color: #31708f; margin-right: auto;
            padding: 10px; border-radius: 10px; max-width: 70%;
        }
        .tutor-msg {
            background-color: #dff0d8; color: #3c763d; margin-left: auto;
            padding: 10px; border-radius: 10px; max-width: 70%;
        }
        form textarea, form input[type="text"] {
            width: 100%; padding: 10px; margin-top: 10px;
        }
        .success { color: green; text-align: center; font-weight: bold; }
        .error { color: red; text-align: center; font-weight: bold; }
        nav {
            background-color: #333; padding: 10px; margin-bottom: 30px;
        }
        nav a {
            color: white; text-decoration: none; margin-right: 20px; font-weight: bold;
        }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<nav>
    <a href="timetable.php">Timetable</a>
    <a href="feedback.php">Feedback</a>
    <a href="tutor_feedback.php">Tutor Feedback</a>
    <a href="chat.php">Chat</a>
</nav>

<div class="chat-container">
    <h2>Chat with Tutor</h2>
    <?php if (!empty($message)) echo $message; ?>

    <form method="POST">
        <label for="tutor_name">Tutor Name:</label>
        <input type="text" name="tutor_name" value="<?= htmlspecialchars($tutor_name) ?>" required placeholder="e.g., Zainab Khan">

        <label for="content">Your Message:</label>
        <textarea name="content" rows="4" required placeholder="Write your message..."></textarea>

        <input type="submit" value="Send Message">
    </form>

    <?php if (!empty($messages)): ?>
        <h3>Chat with <?= htmlspecialchars($tutor_name) ?></h3>
        <?php foreach ($messages as $msg): ?>
            <div class="message-box">
                <?php if ($msg['sender'] == 'student'): ?>
                    <div class="student-msg"><?= htmlspecialchars($msg['content']) ?></div>
                <?php elseif ($msg['sender'] == 'tutor' && $msg['is_read'] == 1): ?>
                    <div class="tutor-msg"><?= htmlspecialchars($msg['content']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
