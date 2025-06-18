-- Create database if not exists
CREATE DATABASE IF NOT EXISTS el7arefaa;
USE el7arefaa;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courts table
CREATE TABLE IF NOT EXISTS courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_hour DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    capacity INT NOT NULL,
    status ENUM('available', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    court_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (court_id) REFERENCES courts(id)
);

-- Ratings table
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_booking_rating (booking_id, user_id)
);

-- Insert default admin user if not exists (password: admin123)
INSERT INTO users (name, email, password, phone, role) 
SELECT 'Admin', 'admin@example.com', '$2y$10$8KzQ8IzAF9M8mFX5QX1Yw.GrM.BAQGxKzXB2HfX9KuLZY5fQu1wPi', '1234567890', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com');

-- Insert sample courts if none exist
INSERT INTO courts (name, description, price_per_hour, capacity, image_url, status)
SELECT 'Premium Indoor Court', 'Professional indoor football court with high-quality turf', 100.00, 14, 'https://example.com/court1.jpg', 'available'
WHERE NOT EXISTS (SELECT 1 FROM courts LIMIT 1);

INSERT INTO courts (name, description, price_per_hour, capacity, image_url, status)
SELECT 'Elite Outdoor Court', 'Spacious outdoor court with floodlights', 80.00, 14, 'https://example.com/court2.jpg', 'available'
WHERE NOT EXISTS (SELECT 1 FROM courts LIMIT 1); 