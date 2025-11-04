-- Tourism Guide System Database
-- Run this in phpMyAdmin to create the database and tables

CREATE DATABASE IF NOT EXISTS tourism_guide;
USE tourism_guide;

-- Admin/Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Destinations Table
CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    contact_number VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(200),
    opening_hours VARCHAR(100),
    entry_fee VARCHAR(100),
    rating DECIMAL(2,1) DEFAULT 0,
    image_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Routes Table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(200),
    origin_id INT,
    destination_id INT,
    transport_mode ENUM('jeepney', 'taxi', 'bus', 'van', 'tricycle', 'walking') NOT NULL,
    distance_km DECIMAL(6,2),
    estimated_time_minutes INT,
    base_fare DECIMAL(8,2),
    fare_per_km DECIMAL(8,2),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (origin_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);

-- Destination Images Table (for multiple images per destination)
CREATE TABLE IF NOT EXISTS destination_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(200),
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);

-- Reviews/Ratings Table (optional for future)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    user_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tourismguide.com', 'admin');

-- Insert default categories
INSERT INTO categories (name, icon) VALUES 
('Tourist Spot', 'fa-camera'),
('Restaurant', 'fa-utensils'),
('Hotel', 'fa-bed'),
('Transport Terminal', 'fa-bus'),
('Landmark', 'fa-landmark'),
('Nature', 'fa-tree'),
('Shopping', 'fa-shopping-bag'),
('Entertainment', 'fa-film');

-- Sample Destinations (Cebu City examples)
INSERT INTO destinations (name, category_id, description, address, latitude, longitude, contact_number, opening_hours, entry_fee) VALUES
('Magellan\'s Cross', 5, 'Historic cross planted by Ferdinand Magellan in 1521', 'P. Burgos St, Cebu City', 10.2937, 123.9021, '+63-32-123-4567', '8:00 AM - 5:00 PM', 'Free'),
('Fort San Pedro', 5, 'Oldest triangular bastion fort in the Philippines', 'A. Pigafetta Street, Cebu City', 10.2918, 123.9019, '+63-32-256-2284', '8:00 AM - 7:00 PM', 'PHP 30'),
('Tops Lookout', 6, 'Scenic viewpoint overlooking Cebu City and nearby islands', 'Busay, Cebu City', 10.3418, 123.9285, '+63-917-123-4567', '24 hours', 'PHP 100');