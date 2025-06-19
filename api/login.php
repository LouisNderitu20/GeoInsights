<?php
require_once 'config.php'; // Include the database configuration

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true); // Decode JSON into an associative array

// Check if username and password are provided
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit();
}

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Prepare and execute the SQL statement to fetch the user (INCLUDING EMAIL)
$stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ?"); // Added email to SELECT
$stmt->bind_param("s", $username); // "s" means one string parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    // Verify the provided password against the stored hash
    if (password_verify($password, $user['password_hash'])) {
        // Password is correct!
        // In a real application, you would generate and return a secure JWT here.
        // For now, we'll just return a success message and a placeholder token.
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "token" => "dummy_jwt_token_for_user_" . $user['id'], // Placeholder token
            "user" => [ // Return user data for client-side storage
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'], // Added email to returned user object
                "role" => $user['role']
            ]
        ]);
    } else {
        // Password does not match
        echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    }
} else {
    // No user found with that username
    echo json_encode(["success" => false, "message" => "Invalid username or password."]);
}

$stmt->close();
$conn->close();
?>