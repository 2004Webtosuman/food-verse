-- Schema Updates for Delivery Tracking System
-- Execute this on the food_verse database

-- 1. Create Drivers Table
CREATE TABLE IF NOT EXISTS drivers (
    user_id INT PRIMARY KEY,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Modify Orders Table
ALTER TABLE orders ADD COLUMN IF NOT EXISTS route_geometry TEXT;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS distance VARCHAR(50);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS estimated_time VARCHAR(50);
