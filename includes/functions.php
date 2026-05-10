<?php
// includes/functions.php

session_start();

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to a specific page
 */
function redirect($path) {
    header("Location: $path");
    exit;
}

/**
 * Display an alert message if set in session
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        echo '<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert">';
        echo '<p>' . $_SESSION['flash_message'] . '</p>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}

/**
 * Format currency
 */
function format_currency($amount) {
    return 'Rs. ' . number_format($amount, 0);
}

/**
 * Get total number of items in cart
 */
function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $count += $qty;
        }
    }
    return $count;
}

/**
 * Get current user location from session or DB
 */
function get_user_location() {
    if (isset($_SESSION['user_location'])) {
        return $_SESSION['user_location'];
    }
    
    // If logged in but session expired, try DB
    if (function_exists('is_logged_in') && is_logged_in()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT province, district, municipality, latitude, longitude FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && !empty($user['province'])) {
            $_SESSION['user_location'] = [
                'province' => $user['province'],
                'district' => $user['district'],
                'municipality' => $user['municipality'],
                'latitude' => $user['latitude'],
                'longitude' => $user['longitude'],
                'full_address' => "{$user['municipality']}, {$user['district']}, {$user['province']}"
            ];
            return $_SESSION['user_location'];
        }
    }
    
    return null;
}

/**
 * Notifications Helper
 */
function add_notification($user_id, $title, $message, $link = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $link]);
}

/**
 * Assign nearest rider algorithm (Delivery Broadcasting)
 */
function assign_nearest_rider($order_id, $force_expansion = false) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT restaurant_lat, restaurant_lng, status, delivery_user_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    // Safety check: Only broadcast if confirmed and unassigned
    if (!$order || $order['status'] !== 'confirmed' || !empty($order['delivery_user_id'])) {
        return false;
    }

    // Check if we've already done a mass broadcast for this order to avoid spamming
    $b_stmt = $pdo->prepare("SELECT id FROM order_broadcasts WHERE order_id = ?");
    $b_stmt->execute([$order_id]);
    if ($b_stmt->fetch()) {
         return false; // Already mass broadcasted this order
    }

    // Mark as broadcasted
    $pdo->prepare("INSERT INTO order_broadcasts (order_id, current_radius) VALUES (?, 9999)")->execute([$order_id]);

    // Find ALL eligible riders to notify instantly mass-broadcast
    $sql = "
        SELECT id, 
        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance 
        FROM users 
        WHERE role = 'delivery' AND status = 'active' AND verification_status = 'verified'
    ";
    
    $r_stmt = $pdo->prepare($sql);
    $r_stmt->execute([
        $order['restaurant_lat'] ?? 27.7172, 
        $order['restaurant_lng'] ?? 85.3240, 
        $order['restaurant_lat'] ?? 27.7172
    ]);
    $riders = $r_stmt->fetchAll();

    foreach ($riders as $rider) {
        $dist = number_format($rider['distance'], 1);
        add_notification(
            $rider['id'], 
            "New Mission ($dist km)", 
            "Order #$order_id is ready for pickup! Be the first to accept.", 
            "view_order.php?id=$order_id"
        );
    }
    
    return true;
}

// ╔══════════════════════════════════════════════════════════════════════╗
// ║          RECOMMENDATION ENGINE — CORE ALGORITHMS                    ║
// ║  5 Strategies + Composite Scoring + View Tracking                   ║
// ║                                                                      ║
// ║  Signal Weights:  Orders = 5, Wishlist = 3, Views = 1               ║
// ║  Blend Weights:   Popularity = 0.35, Preference = 0.45, Rating = 0.20║
// ║  Price Tolerance:  ±20% for Similar Products                        ║
// ║  Recency Window:   30 days (with 7-day boost at 1.5x)              ║
// ╚══════════════════════════════════════════════════════════════════════╝

// --- Constants ---
define('REC_WEIGHT_ORDER', 5);      // Strongest signal: user purchased
define('REC_WEIGHT_WISHLIST', 3);   // Explicit interest signal
define('REC_WEIGHT_VIEW', 1);       // Passive browsing signal
define('REC_BLEND_POPULARITY', 0.35);
define('REC_BLEND_PREFERENCE', 0.45);
define('REC_BLEND_RATING', 0.20);
define('REC_PRICE_TOLERANCE', 0.20); // ±20% price range
define('REC_RECENCY_DAYS', 30);

/**
 * ═══════════════════════════════════════════════════════════════
 * HELPER: Get User's Top Categories by Combined Signal Strength
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   1. Collect category frequencies from 3 signals (orders, wishlist, views)
 *   2. Multiply each by its weight (5, 3, 1)
 *   3. Sum per category → sort descending → return top N category IDs
 * 
 * FORMULA: CategoryScore = (OrderCount × 5) + (WishlistCount × 3) + (ViewCount × 1)
 */
function get_user_top_categories($pdo, $userId, $limit = 3) {
    $sql = "
        SELECT category_id, SUM(signal_weight) AS total_weight
        FROM (
            -- Orders: each order item in a category adds weight 5
            SELECT p.category_id, COUNT(*) * ? AS signal_weight
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? AND o.status NOT IN ('cancelled')
            GROUP BY p.category_id
            
            UNION ALL
            
            -- Wishlist: each wishlisted product in a category adds weight 3
            SELECT p.category_id, COUNT(*) * ? AS signal_weight
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = ?
            GROUP BY p.category_id
            
            UNION ALL
            
            -- Views: total view_count per category, each view adds weight 1
            SELECT p.category_id, SUM(pv.view_count) * ? AS signal_weight
            FROM product_views pv
            JOIN products p ON pv.product_id = p.id
            WHERE pv.user_id = ?
            GROUP BY p.category_id
        ) combined
        GROUP BY category_id
        ORDER BY total_weight DESC
        LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        REC_WEIGHT_ORDER, $userId,
        REC_WEIGHT_WISHLIST, $userId,
        REC_WEIGHT_VIEW, $userId,
        $limit
    ]);

    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category_id');
}

/**
 * ═══════════════════════════════════════════════════════════════
 * STRATEGY 1: POPULAR FOR YOU — Personalized Trending
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   1. Find user's top 3 categories (by weighted signal strength)
 *   2. Within those categories, rank products by RECENT global order volume
 *   3. Exclude products the user already ordered in the last 7 days
 *   4. Falls back to global trending if user has no history (cold start)
 * 
 * KEY FORMULA: PopularityScore = COUNT(orders_in_last_30_days)
 *              Ranked by: PopularityScore DESC, AvgRating DESC
 */
function get_popular_for_you($pdo, $userId, $limit = 8) {
    // Step 1: Get user's preferred categories
    $userCategories = get_user_top_categories($pdo, $userId, 3);

    if (empty($userCategories)) {
        // Cold start → fall back to global trending
        return get_trending_now($pdo, $limit);
    }

    $placeholders = implode(',', array_fill(0, count($userCategories), '?'));

    // Step 2: Rank products in those categories by recent global order volume
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            COALESCE(order_stats.order_count, 0) AS popularity_score,
            COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
            COALESCE(rating_stats.review_count, 0) AS review_count
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT oi.product_id, COUNT(*) AS order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status NOT IN ('cancelled')
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY oi.product_id
        ) order_stats ON p.id = order_stats.product_id
        LEFT JOIN (
            SELECT product_id,
                   AVG(rating) AS avg_rating,
                   COUNT(*) AS review_count
            FROM product_reviews
            WHERE status = 'active'
            GROUP BY product_id
        ) rating_stats ON p.id = rating_stats.product_id
        WHERE p.category_id IN ($placeholders)
          AND p.stock_quantity > 0
          AND p.id NOT IN (
              SELECT oi.product_id
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.id
              WHERE o.user_id = ? AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          )
        ORDER BY popularity_score DESC, avg_rating DESC
        LIMIT ?
    ";

    $params = array_merge(
        [REC_RECENCY_DAYS],
        $userCategories,
        [$userId, $limit]
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ═══════════════════════════════════════════════════════════════
 * STRATEGY 2: RECOMMENDED FOR YOU — Weighted Signal Fusion
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   1. Compute per-CATEGORY affinity from 3 behavioral signals
 *   2. Apply recency decay to order signals:
 *        - Last 7 days   → 1.5× boost  (very recent = strong signal)
 *        - Last 30 days  → 1.0× normal
 *        - Older          → 0.5× decay  (stale behavior)
 *   3. Score each product by:
 *        RecommendationScore = (CategoryAffinity × 0.6) 
 *                            + (NormalizedRating × 0.3) 
 *                            + (IsDealBonus × 0.1)
 * 
 * SIGNAL WEIGHTS:
 *   Orders   → 5 × quantity × recencyMultiplier
 *   Wishlist → 3 × count
 *   Views    → 1 × view_count
 */
function get_recommended_for_you($pdo, $userId, $limit = 8) {
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
            COALESCE(rating_stats.review_count, 0) AS review_count,
            
            -- Composite recommendation score per product
            (
                COALESCE(cat_affinity.affinity_score, 0) * 0.6
                + COALESCE(rating_stats.avg_rating, 0) / 5.0 * 0.3
                + CASE WHEN p.is_deal = 1 THEN 0.1 ELSE 0 END
            ) AS recommendation_score
            
        FROM products p
        JOIN categories c ON p.category_id = c.id
        
        -- Category affinity: weighted sum of all user signals per category
        LEFT JOIN (
            SELECT category_id, SUM(weighted_score) AS affinity_score FROM (
                -- Signal 1: Orders (weight=5, with recency time-decay)
                SELECT p2.category_id,
                    SUM(
                        ? * oi.quantity * 
                        CASE 
                            WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1.5
                            WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1.0
                            ELSE 0.5
                        END
                    ) AS weighted_score
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p2 ON oi.product_id = p2.id
                WHERE o.user_id = ? AND o.status NOT IN ('cancelled')
                GROUP BY p2.category_id
                
                UNION ALL
                
                -- Signal 2: Wishlist (weight=3)
                SELECT p3.category_id, COUNT(*) * ? AS weighted_score
                FROM wishlist w
                JOIN products p3 ON w.product_id = p3.id
                WHERE w.user_id = ?
                GROUP BY p3.category_id
                
                UNION ALL
                
                -- Signal 3: Views (weight=1, scaled by view_count)
                SELECT p4.category_id,
                    SUM(pv.view_count * ?) AS weighted_score
                FROM product_views pv
                JOIN products p4 ON pv.product_id = p4.id
                WHERE pv.user_id = ?
                GROUP BY p4.category_id
            ) AS signals
            GROUP BY category_id
        ) cat_affinity ON p.category_id = cat_affinity.category_id
        
        -- Rating stats subquery
        LEFT JOIN (
            SELECT product_id,
                   AVG(rating) AS avg_rating,
                   COUNT(*) AS review_count
            FROM product_reviews
            WHERE status = 'active'
            GROUP BY product_id
        ) rating_stats ON p.id = rating_stats.product_id
        
        WHERE p.stock_quantity > 0
          AND p.id NOT IN (
              SELECT product_id FROM cart_items WHERE user_id = ?
          )
        
        HAVING recommendation_score > 0
        ORDER BY recommendation_score DESC
        LIMIT ?
    ";

    $params = [
        REC_WEIGHT_ORDER,       // order weight multiplier
        REC_RECENCY_DAYS,       // recency window for decay
        $userId,                // order user filter
        REC_WEIGHT_WISHLIST,    // wishlist weight multiplier
        $userId,                // wishlist user filter
        REC_WEIGHT_VIEW,        // view weight multiplier
        $userId,                // views user filter
        $userId,                // cart exclusion
        $limit
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ═══════════════════════════════════════════════════════════════
 * STRATEGY 3: COLLABORATIVE FILTERING — User-User Cosine Similarity
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM (Cosine Similarity):
 *   This is a robust mathematical approach to measure how similar two users are.
 * 
 *   MATHEMATICAL FORMULA:
 *     sim(u, v) = (P_u ∩ P_v) / sqrt(|P_u| * |P_v|)
 * 
 *   Where:
 *     (P_u ∩ P_v)  = Number of unique products both users purchased (Intersection)
 *     |P_u|        = Total unique products user U purchased (Magnitude/Norm)
 *     |P_v|        = Total unique products user V purchased (Magnitude/Norm)
 * 
 *   BENEFIT:
 *     It normalizes the score. A power user who buys everything won't 
 *     automatically be "similar" to every new user. They must share a high 
 *     percentage of their specific interests.
 * 
 *   Final Rank: Products are scored by multiplying the quantity bought by 
 *   similar users by the high similarity score of that user.
 */
function get_collaborative_filtering($pdo, $userId, $limit = 8) {
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            collab.similarity_weighted_score,
            collab.similar_user_count,
            COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
            COALESCE(rating_stats.review_count, 0) AS review_count
        FROM (
            -- Step 3: Recommend products bought by users with high Cosine Similarity
            SELECT 
                oi3.product_id,
                COUNT(DISTINCT similar_users.similar_user_id) AS similar_user_count,
                SUM(similar_users.similarity * oi3.quantity) AS similarity_weighted_score
            FROM (
                -- Step 2: Calculate Cosine Similarity between target user and others
                SELECT 
                    o2.user_id AS similar_user_id,
                    -- COSINE SIMILARITY FORMULA
                    COUNT(DISTINCT oi2.product_id) / SQRT(target_stats.cnt * other_stats.cnt) AS similarity
                FROM order_items oi2
                JOIN orders o2 ON oi2.order_id = o2.id
                -- Stat: How many unique items the TARGET user bought
                JOIN (
                    SELECT COUNT(DISTINCT product_id) as cnt 
                    FROM order_items oi JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = ? AND o.status != 'cancelled'
                ) target_stats
                -- Stat: How many unique items OTHER users bought
                JOIN (
                    SELECT o.user_id, COUNT(DISTINCT product_id) as cnt 
                    FROM order_items oi JOIN orders o ON oi.order_id = o.id 
                    WHERE o.status != 'cancelled'
                    GROUP BY o.user_id
                ) other_stats ON o2.user_id = other_stats.user_id
                WHERE oi2.product_id IN (
                    -- Step 1: Intersection - products the target user ordered
                    SELECT DISTINCT oi1.product_id
                    FROM order_items oi1
                    JOIN orders o1 ON oi1.order_id = o1.id
                    WHERE o1.user_id = ? AND o1.status NOT IN ('cancelled')
                )
                AND o2.user_id != ?
                AND o2.status NOT IN ('cancelled')
                GROUP BY o2.user_id
                HAVING COUNT(DISTINCT oi2.product_id) >= 1
            ) similar_users
            JOIN orders o3 ON similar_users.similar_user_id = o3.user_id
            JOIN order_items oi3 ON o3.id = oi3.order_id
            WHERE o3.status NOT IN ('cancelled')
              -- Exclude products target user already bought
              AND oi3.product_id NOT IN (
                  SELECT DISTINCT oi4.product_id
                  FROM order_items oi4
                  JOIN orders o4 ON oi4.order_id = o4.id
                  WHERE o4.user_id = ? AND o4.status NOT IN ('cancelled')
              )
            GROUP BY oi3.product_id
        ) collab
        JOIN products p ON collab.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT product_id,
                   AVG(rating) AS avg_rating,
                   COUNT(*) AS review_count
            FROM product_reviews
            WHERE status = 'active'
            GROUP BY product_id
        ) rating_stats ON p.id = rating_stats.product_id
        WHERE p.stock_quantity > 0
        ORDER BY collab.similarity_weighted_score DESC, collab.similar_user_count DESC
        LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId, $userId, $userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ═══════════════════════════════════════════════════════════════
 * STRATEGY 4: SIMILAR PRODUCTS — Distance-Based Content Similarity
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   This uses a distance-based similarity metric on item features.
 *   Conceptually related to Cosine Similarity, but optimized for 
 *   scalar attributes like price and rating.
 * 
 * SIMILARITY FORMULA (Inverse Euclidean Proximity):
 *   SimilarityScore = PriceProximity × 0.5
 *                   + RatingProximity × 0.3
 *                   + PopularityBonus × 0.2
 * 
 *   Where:
 *     PriceProximity  = 1.0 - |product_price - source_price| / (source_price × 0.20)
 *     RatingProximity = 1.0 - |product_rating - source_rating| / 5.0
 *     PopularityBonus = MIN(order_count / 10.0, 1.0)
 * 
 *   MATH RATIONALE:
 *     By subtracting the normalized difference from 1.0, we convert 
 *     a "distance" (difference) into a "similarity" (closer = higher).
 * 
 * FALLBACK: If same-category results are too few, expands to ±30% price cross-category.
 */
function get_similar_products($pdo, $productId, $limit = 6) {
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
            COALESCE(rating_stats.review_count, 0) AS review_count,
            COALESCE(order_stats.order_count, 0) AS popularity_score,
            
            -- Similarity score computation
            (
                (1.0 - ABS(p.price - source.price) / (source.price * ?)) * 0.5
                + (1.0 - ABS(COALESCE(rating_stats.avg_rating, 0) - COALESCE(source_rating.avg_rating, 0)) / 5.0) * 0.3
                + LEAST(COALESCE(order_stats.order_count, 0) / 10.0, 1.0) * 0.2
            ) AS similarity_score
            
        FROM products p
        JOIN categories c ON p.category_id = c.id
        CROSS JOIN (SELECT id, category_id, price FROM products WHERE id = ?) source
        CROSS JOIN (
            SELECT COALESCE(AVG(rating), 0) AS avg_rating
            FROM product_reviews WHERE product_id = ? AND status = 'active'
        ) source_rating
        LEFT JOIN (
            SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM product_reviews WHERE status = 'active' GROUP BY product_id
        ) rating_stats ON p.id = rating_stats.product_id
        LEFT JOIN (
            SELECT oi.product_id, COUNT(*) AS order_count
            FROM order_items oi JOIN orders o ON oi.order_id = o.id
            WHERE o.status NOT IN ('cancelled') GROUP BY oi.product_id
        ) order_stats ON p.id = order_stats.product_id
        WHERE p.id != ?
          AND p.category_id = source.category_id
          AND p.price BETWEEN source.price * (1 - ?) AND source.price * (1 + ?)
          AND p.stock_quantity > 0
        ORDER BY similarity_score DESC
        LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        REC_PRICE_TOLERANCE,    // for similarity calc denominator
        $productId,             // source product
        $productId,             // source rating lookup
        $productId,             // exclude self
        REC_PRICE_TOLERANCE,    // price range lower bound
        REC_PRICE_TOLERANCE,    // price range upper bound
        $limit
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: if same-category results are sparse, expand cross-category by price
    if (count($results) < $limit) {
        $remaining = $limit - count($results);
        $existingIds = array_column($results, 'id');
        $existingIds[] = $productId;
        $excludePlaceholders = implode(',', array_fill(0, count($existingIds), '?'));

        $fallbackSql = "
            SELECT p.*, c.name AS category_name,
                   COALESCE(rs.avg_rating, 0) AS avg_rating,
                   COALESCE(rs.review_count, 0) AS review_count,
                   0 AS popularity_score, 0.25 AS similarity_score
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN (
                SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                FROM product_reviews WHERE status = 'active' GROUP BY product_id
            ) rs ON p.id = rs.product_id
            CROSS JOIN (SELECT price FROM products WHERE id = ?) source
            WHERE p.id NOT IN ($excludePlaceholders)
              AND p.price BETWEEN source.price * 0.7 AND source.price * 1.3
              AND p.stock_quantity > 0
            ORDER BY ABS(p.price - source.price) ASC
            LIMIT ?
        ";

        $fallbackParams = array_merge([$productId], $existingIds, [$remaining]);
        $fb = $pdo->prepare($fallbackSql);
        $fb->execute($fallbackParams);
        $results = array_merge($results, $fb->fetchAll(PDO::FETCH_ASSOC));
    }

    return $results;
}

/**
 * ═══════════════════════════════════════════════════════════════
 * STRATEGY 5: TRENDING NOW — Global Popularity (Guest Fallback)
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   Pure global ranking — no user personalization.
 *   Used for guests / users with zero history (cold-start problem).
 * 
 * FORMULA:
 *   TrendingScore = (OrderCount × 0.35) + (AvgRating × 0.20)
 *   OrderCount is limited to the last 30 days for recency.
 */
function get_trending_now($pdo, $limit = 8) {
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            COALESCE(order_stats.order_count, 0) AS popularity_score,
            COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
            COALESCE(rating_stats.review_count, 0) AS review_count,
            (
                COALESCE(order_stats.order_count, 0) * ?
                + COALESCE(rating_stats.avg_rating, 0) * ?
            ) AS trending_score
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT oi.product_id, COUNT(*) AS order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status NOT IN ('cancelled')
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY oi.product_id
        ) order_stats ON p.id = order_stats.product_id
        LEFT JOIN (
            SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM product_reviews WHERE status = 'active' GROUP BY product_id
        ) rating_stats ON p.id = rating_stats.product_id
        WHERE p.stock_quantity > 0
        ORDER BY trending_score DESC, p.is_deal DESC
        LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([REC_BLEND_POPULARITY, REC_BLEND_RATING, REC_RECENCY_DAYS, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ═══════════════════════════════════════════════════════════════
 * COMPOSITE SCORER — Merge Multiple Strategies Into One Feed
 * ═══════════════════════════════════════════════════════════════
 * 
 * ALGORITHM:
 *   Takes results from multiple strategies and produces a single ranked list.
 * 
 *   For each product in each strategy:
 *     1. PositionScore = (maxRank - currentRank) / maxRank   → 0 to 1
 *     2. RatingBonus   = avgRating / 5.0                      → 0 to 1
 *     3. Contribution  = (PositionScore × 0.7 + RatingBonus × 0.3) × strategyWeight
 * 
 *   Products appearing in MULTIPLE strategies get ADDITIVE scores
 *   (reinforcement — if popular AND recommended, it ranks higher).
 * 
 *   Final output: deduplicated, sorted by composite_score DESC.
 * 
 * @param array $sources ['strategy_name' => ['weight' => float, 'items' => array]]
 * @return array Unified ranked product list
 */
function compute_composite_score($sources) {
    $scored = [];

    foreach ($sources as $strategyName => $data) {
        $weight = $data['weight'];
        $items  = $data['items'];
        $maxRank = count($items);

        foreach ($items as $rank => $product) {
            $pid = $product['id'];

            // Position-based score: first item gets highest, decaying linearly
            $positionScore = ($maxRank - $rank) / max($maxRank, 1);

            // Rating bonus normalized to 0-1
            $ratingBonus = ($product['avg_rating'] ?? 0) / 5.0;

            // Weighted contribution from this strategy
            $contribution = ($positionScore * 0.7 + $ratingBonus * 0.3) * $weight;

            if (!isset($scored[$pid])) {
                $scored[$pid] = $product;
                $scored[$pid]['composite_score'] = 0;
                $scored[$pid]['matched_strategies'] = [];
            }

            $scored[$pid]['composite_score'] += $contribution;
            $scored[$pid]['matched_strategies'][] = $strategyName;
        }
    }

    // Sort by composite score descending
    usort($scored, fn($a, $b) => $b['composite_score'] <=> $a['composite_score']);

    return $scored;
}

/**
 * ═══════════════════════════════════════════════════════════════
 * VIEW TRACKING — Record Product Browsing Behavior
 * ═══════════════════════════════════════════════════════════════
 * 
 * Uses MySQL INSERT ON DUPLICATE KEY UPDATE for efficient upsert.
 * If user+product already exists, increments view_count.
 * Otherwise creates a new row.
 */
function track_product_view($pdo, $userId, $productId) {
    $sql = "
        INSERT INTO product_views (user_id, product_id, view_count, first_viewed_at, last_viewed_at)
        VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            view_count = view_count + 1,
            last_viewed_at = NOW()
    ";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $productId]);
}

/**
 * ═══════════════════════════════════════════════════════════════
 * CONVENIENCE: Get Full Personalized Feed
 * ═══════════════════════════════════════════════════════════════
 * 
 * Runs all 3 user strategies and merges via composite scoring.
 * Returns a single deduplicated, ranked product list.
 */
function get_personalized_feed($pdo, $userId, $limit = 12) {
    $popular       = get_popular_for_you($pdo, $userId, 8);
    $recommended   = get_recommended_for_you($pdo, $userId, 8);
    $collaborative = get_collaborative_filtering($pdo, $userId, 8);

    return compute_composite_score([
        'popular' => [
            'weight' => REC_BLEND_POPULARITY,
            'items'  => $popular,
        ],
        'recommended' => [
            'weight' => REC_BLEND_PREFERENCE,
            'items'  => $recommended,
        ],
        'collaborative' => [
            'weight' => REC_BLEND_RATING,
            'items'  => $collaborative,
        ],
    ]);
}
?>
