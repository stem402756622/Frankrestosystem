-- Frank Restaurant Database Schema - Complete Version
-- All tables and features in one file
-- Run this file to set up the full database with all features

CREATE DATABASE IF NOT EXISTS frank_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frank_restaurant;

-- ============================================
-- CORE TABLES
-- ============================================

-- Restaurant Tables (physical tables in restaurant)
CREATE TABLE IF NOT EXISTS restaurant_tables (
    table_id INT PRIMARY KEY AUTO_INCREMENT,
    table_number VARCHAR(10) UNIQUE NOT NULL,
    capacity INT NOT NULL DEFAULT 4,
    status ENUM('available','occupied','reserved','maintenance','cleaning') DEFAULT 'available',
    location VARCHAR(50) DEFAULT 'Main Hall',
    table_type ENUM('standard','booth','private','outdoor','bar') DEFAULT 'standard',
    min_party_size INT DEFAULT 1,
    max_party_size INT DEFAULT 20,
    features TEXT,
    last_cleaned TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','manager','staff','customer') DEFAULT 'customer',
    loyalty_points INT DEFAULT 0,
    vip_status TINYINT(1) DEFAULT 0,
    preferred_table_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (preferred_table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL
);

-- Reservations (with enhanced features)
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    table_id INT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INT NOT NULL,
    special_requests TEXT,
    occasion VARCHAR(50) DEFAULT 'dining',
    status ENUM('pending','confirmed','seated','completed','cancelled','no_show') DEFAULT 'pending',
    reschedule_count INT DEFAULT 0,
    is_reminded TINYINT(1) DEFAULT 0,
    priority ENUM('low','normal','high','vip') DEFAULT 'normal',
    estimated_duration INT DEFAULT 120,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL
);

-- Customer Preferences
CREATE TABLE IF NOT EXISTS customer_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_type ENUM('table_location','table_type','dining_time','ambiance','seating') NOT NULL,
    preference_value VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Menu Categories
CREATE TABLE IF NOT EXISTS menu_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0
);

-- Menu Items (with dietary tags)
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    dietary_tags SET('Vegetarian','Vegan','Gluten-Free','Halal','Spicy') DEFAULT NULL,
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(category_id) ON DELETE SET NULL
);

-- Orders (with promo code support)
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    table_id INT,
    reservation_id INT NULL,
    status ENUM('pending','preparing','ready','served','completed','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    promo_code_id INT DEFAULT NULL,
    total DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(table_id) ON DELETE SET NULL
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
);

-- ============================================
-- ADDITIONAL FEATURE TABLES
-- ============================================

-- Waitlist
CREATE TABLE IF NOT EXISTS waitlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    party_size INT NOT NULL,
    requested_date DATE NOT NULL,
    requested_time TIME NOT NULL,
    status ENUM('waiting','notified','converted','cancelled') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Peak Hours
CREATE TABLE IF NOT EXISTS peak_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_bookings_per_slot INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Allergens
CREATE TABLE IF NOT EXISTS allergens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Menu Item Allergens (junction table)
CREATE TABLE IF NOT EXISTS menu_item_allergens (
    menu_item_id INT NOT NULL,
    allergen_id INT NOT NULL,
    PRIMARY KEY (menu_item_id, allergen_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    menu_item_id INT DEFAULT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Saved Favorites
CREATE TABLE IF NOT EXISTS saved_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_item (user_id, menu_item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
);

-- Promo Codes
CREATE TABLE IF NOT EXISTS promo_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage','fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT 0,
    used_count INT DEFAULT 0,
    valid_from DATE DEFAULT NULL,
    valid_until DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('unpaid','paid','voided') DEFAULT 'unpaid',
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Inventory Items
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 0,
    low_stock_threshold DECIMAL(10,2) DEFAULT 10,
    cost_per_unit DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(100),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory Transactions
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    type ENUM('restock','used','waste','adjustment') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

-- Menu Item Ingredients (junction table)
CREATE TABLE IF NOT EXISTS menu_item_ingredients (
    menu_item_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity_needed DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (menu_item_id, inventory_item_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
);

-- Queue
CREATE TABLE IF NOT EXISTS queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    party_size INT NOT NULL,
    status ENUM('waiting','seated','left') DEFAULT 'waiting',
    queue_number INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seated_at TIMESTAMP NULL
);

-- Feedback
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    name VARCHAR(100),
    email VARCHAR(100),
    overall_rating INT NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5),
    food_rating INT,
    service_rating INT,
    ambiance_rating INT,
    comment TEXT,
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Sample Tables
INSERT IGNORE INTO restaurant_tables (table_number, capacity, status, location, table_type) VALUES
('T01', 2, 'available', 'Window', 'standard'),
('T02', 4, 'available', 'Main Hall', 'standard'),
('T03', 4, 'occupied', 'Main Hall', 'booth'),
('T04', 6, 'reserved', 'Main Hall', 'standard'),
('T05', 2, 'available', 'Patio', 'outdoor'),
('T06', 8, 'available', 'Private Room', 'private'),
('T07', 4, 'cleaning', 'Bar Area', 'bar'),
('T08', 6, 'available', 'Main Hall', 'booth'),
('T09', 2, 'available', 'Window', 'standard'),
('T10', 10, 'available', 'Private Room', 'private'),
('T11', 4, 'available', 'Patio', 'outdoor'),
('T12', 4, 'maintenance', 'Main Hall', 'standard');

-- Admin User (password: password)
INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@frank.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'Frank Admin', 'admin');

-- Manager User (password: password)
INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES
('manager', 'manager@frank.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'Sarah Manager', 'manager');

-- Staff User (password: password)
INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES
('staff1', 'staff@frank.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'Tom Staff', 'staff');

-- Sample Customers (password: password)
INSERT IGNORE INTO users (username, email, password, full_name, phone, loyalty_points, vip_status) VALUES
('john_doe', 'john@example.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'John Doe', '+1-555-0101', 250, 0),
('jane_smith', 'jane@example.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'Jane Smith', '+1-555-0102', 1500, 1),
('mike_wilson', 'mike@example.com', '$2y$10$YP9ZlMUpArdhIF2CAi3PhOLbRXSFBxMpfEXAQI1HlWWndynWrfxhO', 'Mike Wilson', '+1-555-0103', 800, 0);

-- Menu Categories
INSERT IGNORE INTO menu_categories (name, description, sort_order) VALUES
('Appetizers', 'Start your meal right', 1),
('Main Course', 'Our signature dishes', 2),
('Desserts', 'Sweet endings', 3),
('Beverages', 'Drinks and cocktails', 4);

-- Menu Items
INSERT IGNORE INTO menu_items (category_id, name, description, price, is_available, is_featured) VALUES
(1, 'Bruschetta al Pomodoro', 'Toasted bread with fresh tomatoes, basil and olive oil', 12.00, 1, 1),
(1, 'Calamari Fritti', 'Crispy fried squid with marinara sauce', 16.00, 1, 0),
(1, 'Charcuterie Board', 'Artisan meats, cheeses, and accompaniments', 22.00, 1, 1),
(2, 'Frank Signature Steak', '12oz ribeye with roasted vegetables and chimichurri', 52.00, 1, 1),
(2, 'Sea Bass Piccata', 'Pan-seared sea bass with lemon caper butter', 38.00, 1, 1),
(2, 'Mushroom Risotto', 'Creamy arborio rice with wild mushrooms and truffle oil', 28.00, 1, 0),
(2, 'Chicken Marsala', 'Pan-seared chicken with marsala wine and mushroom sauce', 32.00, 1, 0),
(3, 'Tiramisu', 'Classic Italian dessert with espresso and mascarpone', 12.00, 1, 1),
(3, 'Crème Brûlée', 'French classic with caramelized sugar crust', 11.00, 1, 0),
(4, 'House Wine (Glass)', 'Red or white selection', 12.00, 1, 0),
(4, 'Craft Cocktails', 'Ask your server for today\'s specials', 16.00, 1, 0),
(4, 'Sparkling Water', 'Still or sparkling', 5.00, 1, 0);

-- Sample Reservations
INSERT IGNORE INTO reservations (user_id, table_id, reservation_date, reservation_time, party_size, occasion, status, priority) VALUES
(4, 2, CURDATE(), '19:00:00', 2, 'anniversary', 'confirmed', 'normal'),
(5, 4, CURDATE(), '20:00:00', 4, 'birthday', 'confirmed', 'vip'),
(6, NULL, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:30:00', 3, 'dining', 'pending', 'normal'),
(4, NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:30:00', 2, 'dining', 'pending', 'normal');

-- Allergens
INSERT IGNORE INTO allergens (name) VALUES 
('Nuts'), ('Gluten'), ('Dairy'), ('Shellfish'), ('Soy'), ('Eggs'), ('Fish'), ('Wheat');

-- Peak Hours
INSERT IGNORE INTO peak_hours (day_of_week, start_time, end_time, max_bookings_per_slot) VALUES 
(5, '18:00:00', '21:00:00', 3),
(6, '19:00:00', '22:00:00', 4);

-- Promo Codes
INSERT IGNORE INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_uses) VALUES 
('WELCOME10', 'percentage', 10.00, 0, 100),
('SAVE5', 'fixed', 5.00, 50.00, 50);

-- Inventory Items
INSERT IGNORE INTO inventory_items (name, unit, quantity, low_stock_threshold, cost_per_unit, supplier) VALUES 
('Chicken Breast', 'kg', 50.00, 10.00, 8.50, 'Premium Meats'),
('Beef Tenderloin', 'kg', 25.00, 5.00, 25.00, 'Prime Cuts'),
('Salmon Fillet', 'kg', 20.00, 5.00, 18.00, 'Fresh Seafood Co'),
('Olive Oil', 'L', 10.00, 2.00, 12.00, 'Mediterranean Imports'),
('Tomatoes', 'kg', 30.00, 5.00, 2.50, 'Local Farms');
