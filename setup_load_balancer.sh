#!/bin/bash

echo "Setting up Apache Load Balancer..."

# Copy the load balancer configuration
sudo cp /home/cja48/it490/load-balancer.conf /etc/apache2/sites-available/

# Enable the load balancer site
sudo a2ensite load-balancer.conf

# Disable the default site to avoid conflicts
sudo a2dissite 000-default.conf

# Enable required modules (should already be enabled but making sure)
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_balancer
sudo a2enmod lbmethod_byrequests
sudo a2enmod slotmem_shm
sudo a2enmod status
sudo a2enmod headers

# Test the configuration
echo "Testing Apache configuration..."
sudo apache2ctl configtest

if [ $? -eq 0 ]; then
    echo "Configuration test passed. Restarting Apache..."
    sudo systemctl restart apache2
    sudo systemctl enable apache2
    
    echo "Load balancer setup complete!"
    echo "Access balancer manager at: http://$(hostname -I | awk '{print $1}')/balancer-manager"
    echo "Access server status at: http://$(hostname -I | awk '{print $1}')/server-status"
    echo "Backend servers:"
    echo "  - Server A: 178.156.159.246:8080"
    echo "  - Server B: 178.156.166.21:8080"
else
    echo "Configuration test failed. Please check the configuration."
    exit 1
fi