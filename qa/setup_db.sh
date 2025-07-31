#!/bin/bash

echo "=== [DB SETUP] Starting MySQL installation and configuration ==="

# Update package list
sudo apt update

# Install MySQL Server
sudo apt install -y mysql-server

# Secure installation (skip interactive mode)
echo "=== [DB SETUP] Securing MySQL installation (skipping prompts) ==="
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'rootpassword'; FLUSH PRIVILEGES;"

# Start and enable MySQL
sudo systemctl enable mysql
sudo systemctl start mysql

# Create database and user
echo "=== [DB SETUP] Creating BARKBUDDY DB and user ==="
sudo mysql -u root -prootpassword <<EOF
CREATE DATABASE IF NOT EXISTS BARKBUDDY;
CREATE USER IF NOT EXISTS 'BARKBUDDYUSER'@'%' IDENTIFIED BY 'new_secure_password';
GRANT ALL PRIVILEGES ON BARKBUDDY.* TO 'BARKBUDDYUSER'@'%';
FLUSH PRIVILEGES;
EOF

# Create tables
echo "=== [DB SETUP] Creating USERS table ==="
sudo mysql -u root -prootpassword BARKBUDDY <<EOF
CREATE TABLE IF NOT EXISTS USERS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner', 'sitter', 'admin') DEFAULT 'owner'
);

CREATE TABLE IF NOT EXISTS DOGS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    breed VARCHAR(100),
    health_status VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

CREATE TABLE IF NOT EXISTS DOG_TASKS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dog_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATETIME,
    completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dog_id) REFERENCES DOGS(id),
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

# New tables for care logs, medication scheduling, behavior tracking, and user points
CREATE TABLE IF NOT EXISTS CARE_LOGS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dog_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dog_id) REFERENCES DOGS(id),
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

CREATE TABLE IF NOT EXISTS MEDICATION_SCHEDULES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dog_id INT NOT NULL,
    user_id INT NOT NULL,
    medication VARCHAR(100) NOT NULL,
    dosage VARCHAR(100),
    schedule_time DATETIME,
    notes TEXT,
    completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dog_id) REFERENCES DOGS(id),
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

CREATE TABLE IF NOT EXISTS BEHAVIOR_LOGS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dog_id INT NOT NULL,
    user_id INT NOT NULL,
    behavior VARCHAR(255) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dog_id) REFERENCES DOGS(id),
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

CREATE TABLE IF NOT EXISTS USER_POINTS (
    user_id INT PRIMARY KEY,
    points INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

# Tables for achievements/badges
CREATE TABLE IF NOT EXISTS ACHIEVEMENTS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    badge_img VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS USER_ACHIEVEMENTS (
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES USERS(id),
    FOREIGN KEY (achievement_id) REFERENCES ACHIEVEMENTS(id)
);

# default achievements
INSERT IGNORE INTO ACHIEVEMENTS (code, name, description, badge_img) VALUES
 ('first_care_log', 'Care Novice', 'Logged your first care entry', '/it490/images/badges/first_care_log.png'),
 ('first_med_schedule', 'Med Prepper', 'Scheduled your first medication', '/it490/images/badges/first_med_schedule.png'),
 ('first_behavior_log', 'Behavior Tracker', 'Recorded your first behavior log', '/it490/images/badges/first_behavior_log.png'),
 ('first_task_complete', 'Task Master', 'Completed your first dog task', '/it490/images/badges/first_task_complete.png');
EOF

echo "=== [DB SETUP] MySQL DB setup complete ==="
