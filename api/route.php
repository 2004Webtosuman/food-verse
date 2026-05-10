<?php
// api/route.php - Fetches route from OSRM and stores it in the database if order_id is provided
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $startLat = $_GET['start_lat'] ?? null;
    $startLng = $_GET['start_lng'] ?? null;
    $endLat = $_GET['end_lat'] ?? null;
    $endLng = $_GET['end_lng'] ?? null;
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

    if (!$startLat || !$startLng || !$endLat || !$endLng) {
        echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
        exit;
    }

    // 1. Try to fetch from DB first if we have an orderId
    if ($orderId) {
        $stmt = $pdo->prepare("SELECT route_geometry, distance, estimated_time FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && !empty($order['route_geometry'])) {
            $geom = json_decode($order['route_geometry']);
            if ($geom) {
                echo json_encode([
                    'success' => true,
                    'source' => 'database',
                    'geometry' => $geom,
                    'distance' => $order['distance'],
                    'estimated_time' => $order['estimated_time']
                ]);
                exit;
            }
        }
    }

    // 2. Fetch from OSRM Public API
    $osrmUrl = "https://router.project-osrm.org/route/v1/driving/{$startLng},{$startLat};{$endLng},{$endLat}?overview=full&geometries=geojson";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $osrmUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FoodVerseTracker/1.0');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        if (isset($data['routes'][0])) {
            $route = $data['routes'][0];
            $geometry = $route['geometry']; 
            $distVal = round($route['distance'] / 1000, 2) . ' km';
            $timeVal = round($route['duration'] / 60) . ' mins';
            
            // Save to DB if order_id is present
            if ($orderId) {
                try {
                    $upd = $pdo->prepare("UPDATE orders SET route_geometry = ?, distance = ?, estimated_time = ? WHERE id = ?");
                    $upd->execute([json_encode($geometry), $distVal, $timeVal, $orderId]);
                } catch (Exception $e) {
                    // Log silently but don't break the response
                }
            }
            
            echo json_encode([
                'success' => true,
                'source' => 'osrm',
                'geometry' => $geometry,
                'distance' => $distVal,
                'estimated_time' => $timeVal
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No route found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'OSRM Request Failed', 'code' => $httpCode]);
    }

} catch (Exception $e) {
    // Global catch to prevent ANY HTML output
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
