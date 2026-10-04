CREATE DATABASE IF NOT EXISTS railway_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE railway_booking;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stations (
    station_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    station_code VARCHAR(10) NOT NULL UNIQUE,
    station_name VARCHAR(120) NOT NULL,
    city VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS trains (
    train_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    train_number VARCHAR(20) NOT NULL UNIQUE,
    train_name VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS train_stops (
    stop_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    train_id INT UNSIGNED NOT NULL,
    station_id INT UNSIGNED NOT NULL,
    stop_order INT UNSIGNED NOT NULL,
    arrival_time TIME NULL,
    departure_time TIME NULL,
    FOREIGN KEY (train_id) REFERENCES trains(train_id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(station_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coaches (
    coach_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    train_id INT UNSIGNED NOT NULL,
    coach_type VARCHAR(20) NOT NULL,
    coach_number VARCHAR(20) NOT NULL,
    seat_price DECIMAL(10, 2) NOT NULL DEFAULT 500,
    FOREIGN KEY (train_id) REFERENCES trains(train_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seats (
    seat_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coach_id INT UNSIGNED NOT NULL,
    seat_number VARCHAR(20) NOT NULL,
    seat_type VARCHAR(30) NOT NULL,
    FOREIGN KEY (coach_id) REFERENCES coaches(coach_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meals (
    meal_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meal_name VARCHAR(80) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS train_meals (
    train_id INT UNSIGNED NOT NULL,
    meal_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (train_id, meal_id),
    FOREIGN KEY (train_id) REFERENCES trains(train_id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pnr VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    train_id INT UNSIGNED NOT NULL,
    from_station_id INT UNSIGNED NOT NULL,
    to_station_id INT UNSIGNED NOT NULL,
    coach_id INT UNSIGNED NULL,
    seat_id INT UNSIGNED NULL,
    meal_id INT UNSIGNED NULL,
    travel_date DATE NOT NULL,
    ticket_fare DECIMAL(10, 2) NOT NULL DEFAULT 0,
    meal_fare DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('CONFIRMED', 'CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
    booked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (train_id) REFERENCES trains(train_id),
    FOREIGN KEY (from_station_id) REFERENCES stations(station_id),
    FOREIGN KEY (to_station_id) REFERENCES stations(station_id),
    FOREIGN KEY (coach_id) REFERENCES coaches(coach_id) ON DELETE SET NULL,
    FOREIGN KEY (seat_id) REFERENCES seats(seat_id) ON DELETE SET NULL,
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS booking_passengers (
    passenger_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    seat_id INT UNSIGNED NOT NULL,
    meal_id INT UNSIGNED NULL,
    passenger_name VARCHAR(120) NOT NULL,
    age TINYINT UNSIGNED NOT NULL,
    gender VARCHAR(20) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(seat_id),
    FOREIGN KEY (meal_id) REFERENCES meals(meal_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS wallets (
    wallet_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wallet_transactions (
    transaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('CREDIT', 'DEBIT') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    booking_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_tickets (
    ticket_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT NULL,
    status ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED') NOT NULL DEFAULT 'OPEN',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_support_subject (user_id, subject),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
);

INSERT IGNORE INTO users (full_name, username, email, password_hash, role)
VALUES ('RailEase Admin', 'admin', 'admin@railease.local', '$2y$10$SVORt0wjSPiaTKCo9jWSL.R2vkaFwNUvcwogxF.yzkxgxxnLbwqiW', 'ADMIN');

INSERT IGNORE INTO meals (meal_name, price) VALUES
    ('No Meal', 0), ('Veg', 100), ('Non-Veg', 150), ('Vegan', 120);