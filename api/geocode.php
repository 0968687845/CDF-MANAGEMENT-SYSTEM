<?php
/**
 * Geocoding API Endpoint
 * Handles location geocoding and reverse geocoding requests
 * Improves performance by caching results server-side
 */

require_once '../config.php';
require_once '../functions.php';

// Set response header
header('Content-Type: application/json');

// Check if user is authenticated and is an officer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'officer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get request parameters
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$address = isset($_GET['address']) ? sanitize($_GET['address']) : '';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;

// Initialize cache directory
$cacheDir = __DIR__ . '/../../storage/cache/geocoding';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0700, true);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Generate cache key
 */
function getCacheKey($type, $data) {
    return md5($type . '_' . $data);
}

/**
 * Check cache
 */
function getFromCache($key, $maxAge = 86400) { // 24 hours default
    global $cacheDir;
    $cacheFile = $cacheDir . '/' . $key . '.json';
    
    if (file_exists($cacheFile)) {
        $fileAge = time() - filemtime($cacheFile);
        if ($fileAge < $maxAge) {
            $cached = file_get_contents($cacheFile);
            return json_decode($cached, true);
        }
    }
    return null;
}

/**
 * Save to cache
 */
function saveToCache($key, $data) {
    global $cacheDir;
    $cacheFile = $cacheDir . '/' . $key . '.json';
    file_put_contents($cacheFile, json_encode($data));
}

/**
 * Geocode address to coordinates
 */
function geocodeAddress($address) {
    $cacheKey = getCacheKey('address', $address);
    
    // Check cache first
    $cached = getFromCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    // Make API request
    $apiKey = GOOGLE_MAPS_API_KEY;
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $address,
        'components' => 'country:ZM', // Restrict to Zambia
        'key' => $apiKey
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        return [
            'status' => 'error',
            'message' => 'Reverse geocoding service unavailable',
        ];
    }

    $result = json_decode($response, true);

    if ($result['status'] === 'OK' && count($result['results']) > 0) {
        $location = $result['results'][0];
        $data = [
            'status' => 'success',
            'latitude' => $location['geometry']['location']['lat'],
            'longitude' => $location['geometry']['location']['lng'],
            'address' => $location['formatted_address'],
            'place_id' => $location['place_id'],
            'types' => $location['types']
        ];

        // Save to cache
        saveToCache($cacheKey, $data);
        return $data;
    }

    return [
        'status' => 'error',
        'message' => 'Address not found',
        'api_status' => $result['status']
    ];
}

/**
 * Reverse geocode coordinates to address
 */
function reverseGeocode($lat, $lng) {
    $cacheKey = getCacheKey('coordinates', $lat . '_' . $lng);
    
    // Check cache first
    $cached = getFromCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    // Make API request
    $apiKey = GOOGLE_MAPS_API_KEY;
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'latlng' => $lat . ',' . $lng,
        'key' => $apiKey
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        return [
            'status' => 'error',
            'message' => 'Reverse geocoding service unavailable',
            'http_code' => $httpCode
        ];
    }

    $result = json_decode($response, true);

    if ($result['status'] === 'OK' && count($result['results']) > 0) {
        $location = $result['results'][0];
        $data = [
            'status' => 'success',
            'latitude' => $lat,
            'longitude' => $lng,
            'address' => $location['formatted_address'],
            'place_id' => $location['place_id'],
            'types' => $location['types']
        ];

        // Save to cache
        saveToCache($cacheKey, $data);
        return $data;
    }

    return [
        'status' => 'error',
        'message' => 'Location not found',
        'api_status' => $result['status']
    ];
}

// Route requests
try {
    if ($action === 'geocode' && !empty($address)) {
        $result = geocodeAddress($address);
        echo json_encode($result);
    } elseif ($action === 'reverse' && $lat !== 0 && $lng !== 0) {
        $result = reverseGeocode($lat, $lng);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request. Provide action and required parameters.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
