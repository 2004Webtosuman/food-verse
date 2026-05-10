-- Fix for missing tracking infrastructure in FoodVerse
USE food_verse;

-- 1. Create drivers table if it doesn't exist (Stores live GPS)
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Add tracking columns to orders table (using safer syntax)
-- Note: MySQL 5.7+ supports IF NOT EXISTS for columns in some contexts, 
-- but we will list them here. If they already exist, the migration might error 
-- but that's safe. We'll add them one by one to ensure partial success.

ALTER TABLE orders ADD COLUMN IF NOT EXISTS rider_lat DECIMAL(10, 8) AFTER total_price;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS rider_lng DECIMAL(11, 8) AFTER rider_lat;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS route_geometry LONGTEXT AFTER rider_lng;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS distance VARCHAR(50) AFTER route_geometry;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS estimated_time VARCHAR(50) AFTER distance;
