-- Recommendation Engine: product_views table
-- Tracks user browsing behavior for recommendation scoring

CREATE TABLE IF NOT EXISTS product_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    view_count INT DEFAULT 1,
    last_viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    first_viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance indexes for recommendation queries
CREATE INDEX idx_pv_user ON product_views(user_id);
CREATE INDEX idx_pv_product ON product_views(product_id);
CREATE INDEX idx_pv_last_viewed ON product_views(last_viewed_at);

-- Index on order_items for faster collaborative filtering joins
CREATE INDEX idx_oi_product ON order_items(product_id);
CREATE INDEX idx_oi_order ON order_items(order_id);

-- Index on orders for user lookups
CREATE INDEX idx_orders_user_status ON orders(user_id, status);

-- Index on wishlist
CREATE INDEX idx_wishlist_user ON wishlist(user_id);
CREATE INDEX idx_wishlist_product ON wishlist(product_id);

-- Index on reviews for rating aggregation
CREATE INDEX idx_reviews_product ON product_reviews(product_id, status);
