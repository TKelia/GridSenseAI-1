-- GridSenseAI Database Schema

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS gridsense_ai;
CREATE DATABASE gridsense_ai;
USE gridsense_ai;

-- Users table (must be created first due to foreign key dependencies)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Devices table
CREATE TABLE devices (
    device_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    max_power_rating DECIMAL(10,2),  -- in watts
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Power usage readings
CREATE TABLE power_readings (
    reading_id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    power_usage DECIMAL(10,2) NOT NULL,  -- in watts
    voltage DECIMAL(6,2),                -- in volts
    current DECIMAL(6,2),                -- in amperes
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    INDEX idx_timestamp (timestamp)      -- Index for faster time-based queries
);

-- Daily usage summaries
CREATE TABLE daily_summaries (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    date DATE NOT NULL,
    total_usage DECIMAL(10,2) NOT NULL,  -- in watt-hours
    peak_usage DECIMAL(10,2),            -- peak power in watts
    average_usage DECIMAL(10,2),         -- average power in watts
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_device (device_id, date)
);

-- User preferences
CREATE TABLE user_preferences (
    pref_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    alert_threshold DECIMAL(10,2),       -- power threshold for alerts (watts)
    alert_email BOOLEAN DEFAULT true,
    alert_browser BOOLEAN DEFAULT true,
    theme VARCHAR(20) DEFAULT 'light',
    currency VARCHAR(3) DEFAULT 'USD',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Energy saving tips
CREATE TABLE energy_tips (
    tip_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50),
    potential_saving INT,                -- estimated saving percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
-- First, insert the demo user
INSERT INTO users (username, email, password_hash) VALUES
('demo_user', 'demo@gridsense.ai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Then insert the devices for the demo user
INSERT INTO devices (user_id, device_name, device_type, max_power_rating) VALUES
(1, 'Living Room TV', 'Entertainment', 150),
(1, 'Kitchen Refrigerator', 'Appliance', 400),
(1, 'Bedroom AC', 'HVAC', 1500),
(1, 'Home Office PC', 'Electronics', 500),
(1, 'Washing Machine', 'Appliance', 800);

-- Insert user preferences
INSERT INTO user_preferences (user_id, alert_threshold, theme, currency) VALUES
(1, 2000, 'dark', 'USD');

-- Insert energy saving tips
INSERT INTO energy_tips (title, description, category, potential_saving) VALUES
('LED Lighting Upgrade', 'Replace traditional bulbs with LED lights to reduce lighting energy consumption.', 'Lighting', 80),
('Smart Thermostat Usage', 'Program your thermostat to adjust temperatures automatically when you\'re away or sleeping.', 'HVAC', 15),
('Appliance Peak Hours', 'Run major appliances during off-peak hours to reduce energy costs.', 'Appliances', 20),
('Regular HVAC Maintenance', 'Clean or replace HVAC filters monthly to maintain efficiency.', 'HVAC', 15),
('Phantom Load Management', 'Unplug electronics when not in use to eliminate standby power consumption.', 'Electronics', 10);

-- Insert sample power readings for the current day
INSERT INTO power_readings (device_id, power_usage, voltage, current, timestamp)
WITH power_calc AS (
    SELECT 
        device_id,
        CASE 
            WHEN device_type = 'HVAC' THEN FLOOR(RAND() * 1000 + 500)
            WHEN device_type = 'Appliance' THEN FLOOR(RAND() * 300 + 100)
            ELSE FLOOR(RAND() * 100 + 50)
        END as calc_power
    FROM devices
    WHERE user_id = 1
)
SELECT 
    device_id,
    calc_power as power_usage,
    220 as voltage,
    ROUND(calc_power / 220, 2) as current,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 300) MINUTE) as timestamp
FROM power_calc;

-- Insert sample daily summaries for the past week
INSERT INTO daily_summaries (device_id, date, total_usage, peak_usage, average_usage)
SELECT 
    d.device_id,
    DATE_SUB(CURRENT_DATE, INTERVAL n DAY) as date,
    FLOOR(RAND() * 10000 + 1000) as total_usage,
    FLOOR(RAND() * 2000 + 500) as peak_usage,
    FLOOR(RAND() * 1000 + 200) as average_usage
FROM devices d
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
) as days
WHERE d.user_id = 1;
