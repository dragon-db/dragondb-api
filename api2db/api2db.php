<?php
// This PHP script is ment to be used as an API using which we can collect & store the response header, type and body from any service.

// Import configuration
require_once 'config.php';

// Create connection using config variables
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    die("Connection failed: " . $conn->connect_error);
}

// Set the timezone from config
date_default_timezone_set($timezone);

// Get the current date and time using config format
$timestamp = date($date_format);

// Function to get all headers
function get_all_headers()
{
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get headers
$headers = get_all_headers();
$headers_json = json_encode($headers);

// Get the body content
$body = file_get_contents('php://input');
$body_json = json_decode($body, true);

// Get the full URL
$url = $_SERVER['REQUEST_URI'];
$full_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$url";

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO `app_to_db`(`app_usage`, `app_res_url`, `app_res_header`, `app_req_method`, `app_res_body`, `app_res_time`) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $app_usage, $full_url, $headers_json, $method, $body, $timestamp);

// Execute SQL statement
if ($stmt->execute()) {
    // Respond with a success message
    http_response_code(200); // OK
    echo $success_message;
} else {
    // Respond with error
    http_response_code(500); // Internal Server Error
    echo $error_prefix . $stmt->error;
}

// Close connections
$stmt->close();
$conn->close();

