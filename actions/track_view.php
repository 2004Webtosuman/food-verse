<?php
/**
 * Track Product View
 * 
 * Lightweight fire-and-forget endpoint for recording product views.
 * Called via async fetch from product.php.
 */
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/recommendation_engine.php';

header('Content-Type: application/json');

// Only track for logged-in users
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product_id']);
    exit;
}

try {
    $engine = new RecommendationEngine($pdo);
    $engine->trackView($userId, $productId);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
