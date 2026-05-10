<?php
/**
 * FoodVerse Recommendation Engine
 * 
 * Thin wrapper class that delegates to the standalone functions
 * defined in includes/functions.php. All core algorithms, formulas, 
 * and SQL queries live in functions.php for easy reference.
 * 
 * This class provides an OOP interface used by api/recommendations.php
 * and the frontend pages (index.php, product.php).
 */

class RecommendationEngine
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Strategy 1: Personalized trending from user's preferred categories */
    public function getPopularForYou(int $userId, int $limit = 8): array {
        return get_popular_for_you($this->pdo, $userId, $limit);
    }

    /** Strategy 2: Weighted signal fusion (orders×5, wishlist×3, views×1) */
    public function getRecommendedForYou(int $userId, int $limit = 8): array {
        return get_recommended_for_you($this->pdo, $userId, $limit);
    }

    /** Strategy 3: Item-based collaborative filtering */
    public function getCollaborativeFiltering(int $userId, int $limit = 8): array {
        return get_collaborative_filtering($this->pdo, $userId, $limit);
    }

    /** Strategy 4: Content-based similarity (category + price ±20% + rating) */
    public function getSimilarProducts(int $productId, int $limit = 6): array {
        return get_similar_products($this->pdo, $productId, $limit);
    }

    /** Strategy 5: Global popularity for guests / cold-start */
    public function getTrendingNow(int $limit = 8): array {
        return get_trending_now($this->pdo, $limit);
    }

    /** Composite scorer: merge multiple strategy results into one ranked list */
    public function computeCompositeScore(array $sources): array {
        return compute_composite_score($sources);
    }

    /** Record a product view */
    public function trackView(int $userId, int $productId): bool {
        return track_product_view($this->pdo, $userId, $productId);
    }

    /** Get a full personalized feed combining all strategies */
    public function getPersonalizedFeed(int $userId, int $limit = 12): array {
        return get_personalized_feed($this->pdo, $userId, $limit);
    }
}
