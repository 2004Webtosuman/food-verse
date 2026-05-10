<?php
// actions/search_products.php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$veg_type = isset($_GET['veg_type']) ? sanitize($_GET['veg_type']) : 'all';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'best_match';

try {
    $sql = "SELECT p.*, c.name as category_name, 
            (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND status = 'active') as avg_rating
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if (!empty($query)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }

    if ($category_id > 0) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if ($veg_type !== 'all') {
        $sql .= " AND p.veg_type = ?";
        $params[] = $veg_type;
    }

    // Sorting logic
    switch ($sort) {
        case 'price_low':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'rating_high':
            $sql .= " ORDER BY avg_rating DESC, p.id DESC";
            break;
        case 'rating_low':
            $sql .= " ORDER BY avg_rating ASC, p.id DESC";
            break;
        case 'best_match':
        default:
            $sql .= " ORDER BY p.is_deal DESC, p.id DESC";
            break;
    }

    $sql .= " LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'products' => $products]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

