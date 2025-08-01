<?php
include('config.php');
include('api.php');

// Get OAuth access token
function getZoomAccessToken() {
    $accountId = ZOOM_ACCOUNT_ID;
    $clientId = ZOOM_CLIENT_ID;
    $clientSecret = ZOOM_CLIENT_SECRET;

    $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$accountId}";
    $headers = [
        "Authorization: Basic " . base64_encode("$clientId:$clientSecret")
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response);
    return $data->access_token ?? null;
}

$accessToken = getZoomAccessToken();

if (!$accessToken) {
    echo "Failed to get access token.";
    exit;
}

$arr['topic'] = 'Interview for SkillConnect';
$arr['start_date'] = date('Y-m-d\TH:i:s');
$arr['duration'] = 30;
$arr['password'] = 'vishal';
$arr['type'] = 2;

// Pass the access token to createMeeting
$result = createMeeting($accessToken, $arr);

if (isset($result->id)) {
    // --- DB Connection ---
    $mysqli = new mysqli("sql12.freesqldatabase.com", "sql12784403", "WAuJFq9xaX", "sql12784403", 3306);

    // These should come from your application logic/request
    $employer_id = 1; // Example
    $jobseeker_id = 2; // Example
    $job_posting_id = 3; // Example

    // Insert into interviews table
    $stmt = $mysqli->prepare("INSERT INTO interviews (employer_id, jobseeker_id, job_posting_id, join_url, password, start_time, duration, topic, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')");
    $start_time = $result->start_time;
    $duration = $result->duration;
    $topic = $arr['topic'];
    $join_url = $result->join_url;
    $password = $result->password;
    $stmt->bind_param("iiisssis", $employer_id, $jobseeker_id, $job_posting_id, $join_url, $password, $start_time, $duration, $topic);
    $stmt->execute();
    $stmt->close();

    // Fetch emails
    $employer_email = '';
    $jobseeker_email = '';
    $employer_res = $mysqli->query("SELECT email FROM employers WHERE id = $employer_id");
    if ($row = $employer_res->fetch_assoc()) $employer_email = $row['email'];
    $jobseeker_res = $mysqli->query("SELECT email FROM job_seekers WHERE id = $jobseeker_id");
    if ($row = $jobseeker_res->fetch_assoc()) $jobseeker_email = $row['email'];

    // Email content
    $subject = "Zoom Interview Scheduled - SkillConnect";
    $message = "Your interview has been scheduled.<br>
    <b>Join URL:</b> <a href='{$join_url}'>{$join_url}</a><br>
    <b>Password:</b> {$password}<br>
    <b>Start Time:</b> {$start_time}<br>
    <b>Duration:</b> {$duration} minutes<br>
    <b>Topic:</b> {$topic}<br>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@skillconnect.com\r\n";

    // Send to employer
    if ($employer_email) mail($employer_email, $subject, $message, $headers);
    // Send to job seeker
    if ($jobseeker_email) mail($jobseeker_email, $subject, $message, $headers);

    echo "Join URL: <a href='" . $join_url . "'>" . $join_url . "</a><br/>";
    echo "Password: " . $password . "<br/>";
    echo "Start Time: " . $start_time . "<br/>";
    echo "Duration: " . $duration . "<br/>";
    echo "<br/>Interview details have been emailed to both parties.";
} else {
    echo '<pre>';
    print_r($result);
}
?>