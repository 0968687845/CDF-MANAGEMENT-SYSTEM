<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'cdf_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('BASE_URL', 'http://localhost/cdf_system/');

// Google Maps API Configuration
// Get your key at: https://console.cloud.google.com/
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE');

// IP Geolocation API Configuration
// Get your key at: https://ipgeolocation.io/
define('IP_GEOLOCATION_API_KEY', 'YOUR_IP_GEOLOCATION_API_KEY_HERE');
define('IP_GEOLOCATION_API_URL', 'https://api.ipgeolocation.io/ipgeo');

// System Configuration
define('SITE_NAME', 'CDF Management System');
define('SITE_URL', 'http://localhost/cdf_system');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Function to get Google Maps API Key
function getGoogleMapsApiKey() {
    return defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
}

// Function to get IP Geolocation API Key
function getIPGeolocationApiKey() {
    return defined('IP_GEOLOCATION_API_KEY') ? IP_GEOLOCATION_API_KEY : '';
}

// Function to check if Google Maps API is configured
function hasGoogleMapsApiKey() {
    $apiKey = getGoogleMapsApiKey();
    return !empty($apiKey) && $apiKey !== 'YOUR_GOOGLE_MAPS_API_KEY_HERE';
}

// Function to check if IP Geolocation API is configured
function hasIPGeolocationApiKey() {
    $apiKey = getIPGeolocationApiKey();
    return !empty($apiKey) && $apiKey !== 'YOUR_IP_GEOLOCATION_API_KEY_HERE';
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
