#!/bin/bash
# setup_mq.sh – for qa-mq
# This script sets up RabbitMQ message broker for the QA environment

echo "=== [MQ SETUP] Starting RabbitMQ installation and configuration ==="

# Update package list to ensure we get the latest versions
sudo apt update

# Install RabbitMQ server package
echo "=== [MQ SETUP] Installing RabbitMQ server ==="
sudo apt install -y rabbitmq-server

# Enable RabbitMQ to start automatically on boot
echo "=== [MQ SETUP] Enabling RabbitMQ service ==="
sudo systemctl enable rabbitmq-server

# Start the RabbitMQ service immediately
echo "=== [MQ SETUP] Starting RabbitMQ service ==="
sudo systemctl start rabbitmq-server

# Create a new user for the application with secure credentials
echo "=== [MQ SETUP] Creating application user 'kac63' ==="
sudo rabbitmqctl add_user kac63 Linklinkm1!

# Grant administrator privileges to the user for full management access
echo "=== [MQ SETUP] Setting administrator privileges for user ==="
sudo rabbitmqctl set_user_tags kac63 administrator

# Set permissions for the user on the default virtual host (/)
# Format: configure_permissions read_permissions write_permissions
# ".*" grants full access to all queues, exchanges, and bindings
echo "=== [MQ SETUP] Configuring user permissions ==="
sudo rabbitmqctl set_permissions -p / kac63 ".*" ".*" ".*"

echo "=== [MQ SETUP] RabbitMQ setup complete ==="
