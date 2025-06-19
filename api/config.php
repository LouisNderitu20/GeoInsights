<?php
// Database configuration for MariaDB (MySQL) on XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP MySQL user
define('DB_PASS', '');     // Default XAMPP MySQL password (often empty)
define('DB_NAME', 'geoinsights_db'); // The database we created earlier

// Set headers for CORS (Cross-Origin Resource Sharing)
// IMPORTANT: In a production environment, you should replace '*'
// with the specific domain(s) from which your frontend will make requests.
header('Access-Control-Allow-Origin: *'); // Allows requests from any origin (for development)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Allowed HTTP methods
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allowed request headers
header('Content-Type: application/json'); // Default content type for responses

// Handle preflight requests (OPTIONS method)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>