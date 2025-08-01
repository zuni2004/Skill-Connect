<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $username = $conn->real_escape_string(htmlspecialchars($_POST['username'])); // Assuming your form has a 'username' field
    $organization_name = $conn->real_escape_string(htmlspecialchars($_POST['organizationName'])); // Assuming 'organizationName'
    $contact_person = $conn->real_escape_string(htmlspecialchars($_POST['contactPerson'])); // Assuming 'contactPerson'
    $email = $conn->real_escape_string(htmlspecialchars($_POST['email']));
    $phone = !empty($_POST['phone']) ? $conn->real_escape_string(htmlspecialchars($_POST['phone'])) : NULL;
    $password = $_POST['password']; // Raw password from form
    $confirm_password = $_POST['confirmPassword']; // Confirm password from form
    $address = !empty($_POST['address']) ? $conn->real_escape_string(htmlspecialchars($_POST['address'])) : NULL;
    $website = !empty($_POST['website']) ? $conn->real_escape_string(htmlspecialchars($_POST['website'])) : NULL;

    $errors = [];

    // Basic validation
    if (empty($username) || empty($organization_name) || empty($contact_person) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All required fields must be filled.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check if email or username already exists
    $sql_check_user = "SELECT id FROM employers WHERE email = ? OR username = ?";
    $stmt_check_user = $conn->prepare($sql_check_user);
    if ($stmt_check_user) {
        $stmt_check_user->bind_param("ss", $email, $username);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        if ($stmt_check_user->num_rows > 0) {
            $errors[] = "Email or Username already registered.";
        }
        $stmt_check_user->close();
    } else {
        $errors[] = "Database error during user check: " . $conn->error;
    }

    if (empty($errors)) {
        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare INSERT statement
        $sql_insert = "INSERT INTO employers (
                            username, organization_name, contact_person, email, phone,
                            password, address, website, is_approved
                       ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?
                       )";
        $stmt_insert = $conn->prepare($sql_insert);

        if ($stmt_insert) {
            $is_approved = FALSE; // Employers might need approval

            $stmt_insert->bind_param(
                "sssssssib", // s:username, s:org_name, s:contact_person, s:email, s:phone, s:password, s:address, s:website, i:is_approved (boolean as integer)
                $username,
                $organization_name,
                $contact_person,
                $email,
                $phone,
                $hashed_password,
                $address,
                $website,
                $is_approved
            );

            if ($stmt_insert->execute()) {
                echo "<script>alert('Employer registration successful! You can now log in.'); window.location.href='login.html';</script>";
            } else {
                echo "<script>alert('Error registering employer: " . $stmt_insert->error . "'); window.location.href='registration_form_jobSeekers_Employeers.html';</script>";
            }
            $stmt_insert->close();
        } else {
            echo "<script>alert('Error preparing statement: " . $conn->error . "'); window.location.href='registration_form_jobSeekers_Employeers.html';</script>";
        }
    } else {
        // Display errors if any
        $error_message = implode("\\n", $errors); // Join errors with newline for alert
        echo "<script>alert('Registration failed:\\n" . $error_message . "'); window.location.href='registration_form_jobSeekers_Employeers.html';</script>";
    }

} else {
    // If accessed directly without POST method
    header("Location: registration_form_jobSeekers_Employeers.html");
    exit();
}

$conn->close(); // Close database connection
?>
