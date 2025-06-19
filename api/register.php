<?php
require_once 'config.php'; // Include the database configuration

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true); // Decode JSON into an associative array

// Check if username, email, and password are provided
$username = $data['username'] ?? '';
$email = $data['email'] ?? ''; // Get email
$password = $data['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) { // Check email too
    echo json_encode(["success" => false, "message" => "All fields (username, email, password) are required."]);
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute the SQL statement to prevent SQL injection
// Update INSERT statement to include email
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashed_password); // "sss" for three string parameters

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Account created successfully!"]);
} else {
    // Check for duplicate entry error (username or email already exists)
    if ($conn->errno == 1062) { // MariaDB/MySQL error code for duplicate entry (for UNIQUE constraints)
        $error_message = "Registration failed. ";
        // You can try to be more specific about which field caused the duplicate
        if (strpos($stmt->error, 'username') !== false) {
            $error_message .= "Username already exists. Please choose a different one.";
        } elseif (strpos($stmt->error, 'email') !== false) {
            $error_message .= "Email address already exists. Please use a different one.";
        } else {
            $error_message .= "Duplicate entry error."; // Generic fallback
        }
        echo json_encode(["success" => false, "message" => $error_message]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed: " . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>