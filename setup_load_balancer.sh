#!/bin/bash

# Setup Load Balancer for IT490 App
# This script configures Apache load balancing between two app servers

echo "Setting up Apache Load Balancer..."

# Enable required Apache modules
echo "Enabling Apache modules..."
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_balancer
sudo a2enmod lbmethod_byrequests
sudo a2enmod headers
sudo a2enmod status

# Copy the load balancer configuration
echo "Installing load balancer configuration..."
sudo cp /home/cja48/it490/load-balancer.conf /etc/apache2/sites-available/

# Disable default site and enable load balancer
echo "Configuring sites..."
sudo a2dissite 000-default
sudo a2ensite load-balancer

# Test Apache configuration
echo "Testing Apache configuration..."
sudo apache2ctl configtest

if [ $? -eq 0 ]; then
    echo "Configuration test passed. Restarting Apache..."
    sudo systemctl restart apache2
    sudo systemctl status apache2
else
    echo "Configuration test failed. Please check the configuration."
    exit 1
fi

echo "Load balancer setup complete!"
echo ""
echo "You can monitor the load balancer at:"
echo "  http://your-server-ip/balancer-manager"
echo "  http://your-server-ip/server-status"
echo ""
echo "Make sure the second server (178.156.159.246) is running your app on port 80"