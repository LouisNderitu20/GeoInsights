<?php
// Set headers for CORS (Cross-Origin Resource Sharing)
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Allow requests from any origin (for development)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if a file was actually uploaded and if there were no errors
if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit();
}

$file = $_FILES['data_file'];
$fileName = basename($file['name']);
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

// Define allowed file extensions
$allowedExtensions = ['json', 'csv'];
if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JSON and CSV are allowed.']);
    exit();
}

// Define the directory to save uploaded files
// IMPORTANT: Ensure this directory exists and is writable by your web server!
// This path is relative to the 'api' folder. If 'geoinsights' is in htdocs,
// then 'uploads' should be a sibling directory to 'api' within 'geoinsights'.
$uploadDir = '../uploads/'; // This means: geoinsights/uploads/

// Create the upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // 0777 grants full permissions (adjust in production)
}

// Create a unique file name to prevent overwriting
$destPath = $uploadDir . uniqid() . '.' . $fileExtension;

// Move the uploaded file from temporary location to the destination
if (move_uploaded_file($fileTmpPath, $destPath)) {
    // File moved successfully, now attempt to process its content
    $uploadedContent = file_get_contents($destPath);
    $parsedData = [];
    $message = "File uploaded and processed successfully!";

    if (strtolower($fileExtension) === 'csv') {
        $rows = array_map('str_getcsv', explode("\n", $uploadedContent));
        $header = array_shift($rows); // Get header row (e.g., 'latitude', 'longitude', 'region', etc.)
        foreach ($rows as $row) {
            if (count($row) === count($header)) { // Ensure row has same number of columns as header
                $parsedData[] = array_combine($header, $row);
            }
        }
        // Basic validation for CSV: check if 'latitude' and 'longitude' columns exist
        if (empty($parsedData) || !isset($parsedData[0]['latitude']) || !isset($parsedData[0]['longitude'])) {
            unlink($destPath); // Delete the file if invalid format
            echo json_encode(['success' => false, 'message' => 'CSV file must contain "latitude" and "longitude" columns.']);
            exit();
        }
    } elseif (strtolower($fileExtension) === 'json') {
        $jsonData = json_decode($uploadedContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            unlink($destPath); // Delete the file if invalid JSON
            echo json_encode(['success' => false, 'message' => 'Invalid JSON file format.']);
            exit();
        }

        // Check if it's a GeoJSON FeatureCollection
        if (isset($jsonData['type']) && $jsonData['type'] === 'FeatureCollection' && isset($jsonData['features']) && is_array($jsonData['features'])) {
            foreach ($jsonData['features'] as $feature) {
                if (isset($feature['geometry']['coordinates']) && isset($feature['properties'])) {
                    $lon = $feature['geometry']['coordinates'][0];
                    $lat = $feature['geometry']['coordinates'][1];
                    $props = $feature['properties'];
                    // Merge coordinates with properties to match your expected markerData format
                    $parsedData[] = array_merge(['latitude' => $lat, 'longitude' => $lon], $props);
                }
            }
        } else if (is_array($jsonData)) {
            // Assume it's a direct array of objects like [{"latitude": ..., "longitude": ..., ...}]
            $parsedData = $jsonData;
        } else {
             unlink($destPath);
             echo json_encode(['success' => false, 'message' => 'JSON data must be an array of objects or a GeoJSON FeatureCollection with coordinates and properties.']);
             exit();
        }

        // Basic validation for JSON: check if parsed data has latitude and longitude
        if (empty($parsedData) || (!isset($parsedData[0]['latitude']) || !isset($parsedData[0]['longitude']))) {
            unlink($destPath); // Delete the file if invalid format
            echo json_encode(['success' => false, 'message' => 'JSON data must contain "latitude" and "longitude" or be a valid GeoJSON FeatureCollection.']);
            exit();
        }
    }

    // Send a success response back to the client, including the parsed data for immediate display
    echo json_encode(['success' => true, 'message' => $message, 'data' => $parsedData]);

} else {
    // If file move failed, send an error response
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check server permissions for the "uploads" directory.']);
}
?>