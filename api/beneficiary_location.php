<?php
/**
 * Beneficiary Location API
 * Fetch beneficiary location coordinates and information
 */

require_once '../functions.php';
requireRole('officer');

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (isset($_GET['project_id'])) {
        $projectId = intval($_GET['project_id']);
        
        // Get project with beneficiary location data
        $query = "SELECT 
                    p.id,
                    p.title,
                    p.location,
                    p.latitude,
                    p.longitude,
                    CONCAT(b.first_name, ' ', b.last_name) as beneficiary_name,
                    b.phone as beneficiary_phone,
                    b.email as beneficiary_email
                  FROM projects p
                  LEFT JOIN users b ON p.beneficiary_id = b.id
                  WHERE p.id = :project_id 
                  AND p.officer_id = :officer_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            // Check if we need to geocode the location
            if ((!$project['latitude'] || !$project['longitude']) && $project['location']) {
                // Return location info with flag for client-side geocoding
                echo json_encode([
                    'success' => true,
                    'project' => $project,
                    'needsGeocoding' => true,
                    'message' => 'Location needs to be geocoded. Use client-side geocoding.'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'project' => $project,
                    'needsGeocoding' => false,
                    'latitude' => floatval($project['latitude']),
                    'longitude' => floatval($project['longitude']),
                    'message' => 'Location data available'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Project not found or access denied'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'project_id parameter required'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
