-- Vehicle Rental Booking System Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS rental_db;
USE rental_db;

-- Users table (customers and admins)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Create default admin account (password: motmot)
INSERT INTO users (name, email, password, phone, role) VALUES
('Administrator', 'admin@gmail.com', '$2y$10$uHueAG7reMIOxd3Mq5ppxO2gZRjaw7Shp5os41mBARsV2WD0a5WcW', '09123456789', 'admin');

-- Insert sample vehicles using existing images
INSERT INTO vehicles (name, type, brand, model, price_per_day, image, description, status) VALUES
('Toyota Vios', 'Sedan', 'Toyota', 'Vios 2023', 1500.00, 'Images/1.jpg', 'Comfortable sedan perfect for city driving and family trips. Features automatic transmission and air conditioning.', 'available'),
('Honda Click', 'Motorcycle', 'Honda', 'Click 125i', 350.00, 'Images/2.jpg', 'Fuel-efficient scooter ideal for daily commute. Easy to ride with automatic transmission.', 'available'),
('Toyota Hiace', 'Van', 'Toyota', 'Hiace Commuter', 3500.00, 'Images/4.jpg', 'Spacious van that can accommodate up to 15 passengers. Perfect for group travels and events.', 'available'),
('Mitsubishi Montero', 'SUV', 'Mitsubishi', 'Montero Sport', 2800.00, 'Images/5.webp', 'Powerful SUV with 4x4 capability. Great for adventures and off-road trips.', 'available'),
('Yamaha NMAX', 'Motorcycle', 'Yamaha', 'NMAX 155', 450.00, 'Images/6.jpg', 'Premium scooter with advanced features. Comfortable for long rides.', 'available'),
('Honda Civic', 'Sedan', 'Honda', 'Civic RS', 2200.00, 'Images/7.jpg', 'Sporty sedan with turbocharged engine. Perfect for those who love performance.', 'available'),
('Toyota Fortuner', 'SUV', 'Toyota', 'Fortuner 4x4', 3200.00, 'Images/download.jpg', 'Popular SUV with excellent ground clearance. Ideal for both city and provincial trips.', 'available');
