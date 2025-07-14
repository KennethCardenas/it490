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
EOF

echo "=== [DB SETUP] MySQL DB setup complete ==="
