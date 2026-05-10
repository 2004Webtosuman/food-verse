<?php
/**
 * Recommendations API Endpoint
 * 
 * Returns personalized recommendation data as JSON.
 * Supports: popular, recommended, collaborative, similar, trending
 */
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/recommendation_engine.php';

header('Content-Type: application/json');

$engine = new RecommendationEngine($pdo);
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$userId = $_SESSION['user_id'] ?? 0;
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 8;

try {
    $response = ['success' => true];

    switch ($type) {
        case 'popular':
            $response['products'] = $userId
                ? $engine->getPopularForYou($userId, $limit)
                : $engine->getTrendingNow($limit);
            $response['strategy'] = $userId ? 'popular_for_you' : 'trending';
            break;

        case 'recommended':
            if (!$userId) {
                $response['products'] = [];
                $response['strategy'] = 'none';
                $response['message'] = 'Login required for personalized recommendations.';
                break;
            }
            $response['products'] = $engine->getRecommendedForYou($userId, $limit);
            $response['strategy'] = 'recommended_for_you';
            break;

        case 'collaborative':
            if (!$userId) {
                $response['products'] = [];
                $response['strategy'] = 'none';
                break;
            }
            $response['products'] = $engine->getCollaborativeFiltering($userId, $limit);
            $response['strategy'] = 'collaborative_filtering';
            break;

        case 'similar':
            if ($productId <= 0) {
                $response['success'] = false;
                $response['message'] = 'product_id is required for similar products.';
                break;
            }
            $response['products'] = $engine->getSimilarProducts($productId, $limit);
            $response['strategy'] = 'similar_products';
            break;

        case 'trending':
            $response['products'] = $engine->getTrendingNow($limit);
            $response['strategy'] = 'trending';
            break;

        case 'all':
            // Return all applicable recommendation sections
            if ($userId) {
                $response['popular'] = $engine->getPopularForYou($userId, $limit);
                $response['recommended'] = $engine->getRecommendedForYou($userId, $limit);
                $response['collaborative'] = $engine->getCollaborativeFiltering($userId, $limit);
            } else {
                $response['popular'] = $engine->getTrendingNow($limit);
                $response['recommended'] = [];
                $response['collaborative'] = [];
            }
            $response['strategy'] = 'all';
            break;

        default:
            $response['success'] = false;
            $response['message'] = 'Invalid recommendation type. Use: popular, recommended, collaborative, similar, trending, all';
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Recommendation engine error: ' . $e->getMessage()
    ]);
}
