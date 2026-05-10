-- Create DB safely
IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'food_verse')
BEGIN
    CREATE DATABASE food_verse;
END
GO

USE food_verse;
GO

-- Users table
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at DATETIME DEFAULT GETDATE()
);

-- Categories
CREATE TABLE categories (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(100)
);

-- Products
CREATE TABLE products (
    id INT IDENTITY(1,1) PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    is_deal BIT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart
CREATE TABLE cart_items (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist
CREATE TABLE wishlist (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders
CREATE TABLE orders (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN 
        ('pending','confirmed','preparing','out_for_delivery','delivered','cancelled','paid')),
    delivery_type VARCHAR(10) DEFAULT 'delivery' CHECK (delivery_type IN ('delivery','pickup')),
    delivery_address TEXT,
    delivery_lat DECIMAL(10,8),
    delivery_lng DECIMAL(11,8),
    restaurant_lat DECIMAL(10,8),
    restaurant_lng DECIMAL(11,8),
    payment_method VARCHAR(20) DEFAULT 'cod',
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    transaction_uuid VARCHAR(100),
    created_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items
CREATE TABLE order_items (
    id INT IDENTITY(1,1) PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Audit Logs
CREATE TABLE audit_logs (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    timestamp DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed data
INSERT INTO categories (name, icon) VALUES 
('Burger', 'burger-icon'),
('Fries', 'fries-icon'),
('Pizza', 'pizza-icon'),
('Drinks', 'drinks-icon'),
('Sweets', 'sweets-icon');

INSERT INTO products (category_id, name, description, price, image_url, stock_quantity, is_deal) VALUES 
(1, 'Double Beef burger', 'Juicy double beef patty with cheese.', 1000.00, 'images/burger.png', 50, 1),
(3, 'Peperoni pizza', 'Classic peperoni pizza with mozzarella.', 620.00, 'images/pizza.png', 30, 1),
(4, 'Vanilla Shake', 'Creamy vanilla milkshake.', 200.00, 'images/vanilla_shake.png', 100, 1),
(2, 'Classic Large Fries', 'Crispy golden large fries.', 350.00, 'images/fries.png', 200, 1);