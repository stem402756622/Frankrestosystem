-- Frank Restaurant Database Schema
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS frank_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frank_restaurant;

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

-- Reservations
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

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(category_id) ON DELETE SET NULL
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    table_id INT,
    reservation_id INT NULL,
    status ENUM('pending','preparing','ready','served','completed','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
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

-- ============ SAMPLE DATA ============

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
