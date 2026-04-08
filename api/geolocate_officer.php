<?php
/**
 * IP Geolocation API Endpoint - Enhanced Accuracy Version
 * Detect user's location based on IP address with multiple fallbacks
 * Supports multiple APIs and accuracy levels
 * Used for auto-detecting officer's current location during site visit scheduling
 */

require_once '../functions.php';
requireRole('officer');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Get user's IP address with multiple detection methods
    $userIp = getUserIpAddress();
    
    if (!$userIp) {
        throw new Exception("Could not determine user IP address");
    }
    
    // Check if it's a local/private IP
    $isLocalIp = isPrivateIp($userIp);
    
    // For local testing, use Lusaka test coordinates
    if ($isLocalIp) {
        $locationData = getLocalTestData();
    } else {
        // Try primary API first (IPGeolocation.io)
        $locationData = null;
        $primaryApiKey = getIPGeolocationApiKey();
        
        if (!empty($primaryApiKey) && $primaryApiKey !== 'YOUR_IP_GEOLOCATION_API_KEY_HERE') {
            $locationData = callIPGeolocationIO($userIp, $primaryApiKey);
        }
        
        // Fallback to secondary API (ip-api.com) if primary fails
        if (!$locationData) {
            $locationData = callIPAPIFallback($userIp);
        }
        
        // Fallback to tertiary API (ipinfo.io) if both fail
        if (!$locationData) {
            $locationData = callIPInfoFallback($userIp);
        }
        
        // If all APIs fail, throw error
        if (!$locationData) {
            throw new Exception("All geolocation APIs failed. Please try again.");
        }
    }
    
    // Validate and enhance location data
    $locationData = validateLocationData($locationData);
    
    // Warn if location is outside Zambia
    $warning = null;
    if (strtolower($locationData['country_code2'] ?? '') !== 'zm') {
        $warning = 'Location detected outside Zambia (Country: ' . ($locationData['country_name'] ?? 'Unknown') . '). Please verify your actual location.';
    }
    
    // Format response with 8-decimal precision for accuracy
    $response_data = [
        'success' => true,
        'ip_address' => $userIp,
        'is_local' => $isLocalIp,
        'latitude' => number_format(floatval($locationData['latitude'] ?? 0), 8, '.', ''),
        'longitude' => number_format(floatval($locationData['longitude'] ?? 0), 8, '.', ''),
        'city' => $locationData['city'] ?? 'Unknown',
        'state' => $locationData['state_prov'] ?? 'Unknown',
        'country' => $locationData['country_name'] ?? 'Unknown',
        'country_code' => $locationData['country_code2'] ?? '',
        'timezone' => $locationData['timezone'] ?? 'Unknown',
        'isp' => $locationData['isp'] ?? 'Unknown',
        'accuracy' => $locationData['accuracy'] ?? 'city-level',
        'source' => $locationData['source'] ?? 'ip-geolocation',
        'timestamp' => date('Y-m-d H:i:s'),
        'warning' => $warning
    ];
    
    // Cache successful result
    $_SESSION['cached_geolocation'] = [
        'data' => $response_data,
        'timestamp' => time()
    ];
    
    echo json_encode($response_data);
    exit;
    
} catch (Exception $e) {
    error_log("IP Geolocation Error: " . $e->getMessage() . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
    
    // Try to return cached data if available
    if (isset($_SESSION['cached_geolocation'])) {
        $cached = $_SESSION['cached_geolocation'];
        // Return cached data if less than 4 hours old
        if ((time() - $cached['timestamp']) < 14400) {
            $cached['data']['source'] = 'cached';
            echo json_encode($cached['data']);
            exit;
        }
    }
    
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Error detecting location: ' . $e->getMessage(),
        'ip_address' => getUserIpAddress()
    ]);
    exit;
}

/**
 * Check if IP is private/local
 * @param string $ip
 * @return bool
 */
function isPrivateIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

/**
 * Get local test data for Lusaka, Zambia
 * @return array
 */
function getLocalTestData() {
    return [
        'success' => true,
        'latitude' => -15.38750000,
        'longitude' => 28.28830000,
        'city' => 'Lusaka',
        'state_prov' => 'Lusaka Province',
        'country_name' => 'Zambia',
        'country_code2' => 'ZM',
        'timezone' => 'Africa/Lusaka',
        'isp' => 'Local Test Network',
        'accuracy' => 'local-test',
        'source' => 'local-test'
    ];
}

/**
 * Call IPGeolocation.io API (Primary)
 * @param string $ip
 * @param string $apiKey
 * @return array|null
 */
function callIPGeolocationIO($ip, $apiKey) {
    try {
        $apiUrl = "https://api.ipgeolocation.io/ipgeo?apiKey=" . urlencode($apiKey) . "&ip=" . urlencode($ip) . "&fields=geo,asn";
        
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'CDF-Management-System/2.5'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || isset($data['status']) && $data['status'] === 'fail') {
            return null;
        }
        
        return [
            'success' => true,
            'latitude' => floatval($data['latitude'] ?? 0),
            'longitude' => floatval($data['longitude'] ?? 0),
            'city' => $data['city'] ?? 'Unknown',
            'state_prov' => $data['state_prov'] ?? 'Unknown',
            'country_name' => $data['country_name'] ?? 'Unknown',
            'country_code2' => $data['country_code2'] ?? '',
            'timezone' => $data['time_zone']['name'] ?? 'Unknown',
            'isp' => $data['asn']['name'] ?? $data['isp'] ?? 'Unknown',
            'accuracy' => 'city-level',
            'source' => 'ipgeolocation.io'
        ];
    } catch (Exception $e) {
        error_log("IPGeolocation.io API Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fallback to ip-api.com API (Secondary)
 * Note: Free tier has limitations, use with caution
 * @param string $ip
 * @return array|null
 */
function callIPAPIFallback($ip) {
    try {
        // Only use ip-api for fallback with proper rate limiting
        $apiUrl = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,continent,continentCode,country,countryCode,region,regionName,city,district,zip,lat,lon,timezone,isp,org,as";
        
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'CDF-Management-System/2.5'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || $data['status'] !== 'success') {
            return null;
        }
        
        return [
            'success' => true,
            'latitude' => floatval($data['lat'] ?? 0),
            'longitude' => floatval($data['lon'] ?? 0),
            'city' => $data['city'] ?? $data['district'] ?? 'Unknown',
            'state_prov' => $data['regionName'] ?? 'Unknown',
            'country_name' => $data['country'] ?? 'Unknown',
            'country_code2' => $data['countryCode'] ?? '',
            'timezone' => $data['timezone'] ?? 'Unknown',
            'isp' => $data['org'] ?? $data['isp'] ?? 'Unknown',
            'accuracy' => 'city-level',
            'source' => 'ip-api.com'
        ];
    } catch (Exception $e) {
        error_log("IP-API Fallback Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fallback to ipinfo.io API (Tertiary)
 * @param string $ip
 * @return array|null
 */
function callIPInfoFallback($ip) {
    try {
        $apiUrl = "https://ipinfo.io/" . urlencode($ip) . "/json";
        
        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'CDF-Management-System/2.5'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            return null;
        }
        
        // Parse location (lat,lng format)
        $location = isset($data['loc']) ? explode(',', $data['loc']) : [0, 0];
        
        return [
            'success' => true,
            'latitude' => floatval($location[0] ?? 0),
            'longitude' => floatval($location[1] ?? 0),
            'city' => $data['city'] ?? 'Unknown',
            'state_prov' => $data['region'] ?? 'Unknown',
            'country_name' => $data['country'] ?? 'Unknown',
            'country_code2' => $data['country'] ?? '',
            'timezone' => $data['timezone'] ?? 'Unknown',
            'isp' => $data['org'] ?? 'Unknown',
            'accuracy' => 'city-level',
            'source' => 'ipinfo.io'
        ];
    } catch (Exception $e) {
        error_log("IPInfo Fallback Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate and enhance location data
 * @param array $data
 * @return array
 */
function validateLocationData($data) {
    // Ensure coordinates are floats
    $data['latitude'] = floatval($data['latitude'] ?? 0);
    $data['longitude'] = floatval($data['longitude'] ?? 0);
    
    // Validate coordinate ranges
    if ($data['latitude'] < -90 || $data['latitude'] > 90) {
        $data['latitude'] = 0;
    }
    if ($data['longitude'] < -180 || $data['longitude'] > 180) {
        $data['longitude'] = 0;
    }
    
    // Sanitize strings
    $data['city'] = isset($data['city']) ? trim($data['city']) : 'Unknown';
    $data['state_prov'] = isset($data['state_prov']) ? trim($data['state_prov']) : 'Unknown';
    $data['country_name'] = isset($data['country_name']) ? trim($data['country_name']) : 'Unknown';
    $data['country_code2'] = isset($data['country_code2']) ? strtoupper(trim($data['country_code2'])) : '';
    
    return $data;
}

/**
 * Get user's IP address with multiple detection methods
 * @return string|null
 */
function getUserIpAddress() {
    // Check for Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // Check for shared internet
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle multiple IPs (take the first non-local one)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ips as $check_ip) {
            $check_ip = trim($check_ip);
            if (filter_var($check_ip, FILTER_VALIDATE_IP)) {
                $ip = $check_ip;
                break;
            }
        }
        if (!isset($ip)) {
            $ip = trim($ips[0]);
        }
    }
    // Check for X-Real-IP header (Nginx proxy)
    elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    // Check regular remote address
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
        return null;
    }
    
    // Validate IP address
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return null;
}
?>
