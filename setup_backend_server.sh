#!/bin/bash

# Setup script for backend servers
# Usage: ./setup_backend_server.sh [SERVER_A|SERVER_B] [PORT]

SERVER_NAME=${1:-"SERVER_A"}
PORT=${2:-8080}

echo "Setting up backend server: $SERVER_NAME on port $PORT"

# Install Apache and PHP if not already installed
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-curl php-json

# Create a new site configuration
sudo tee /etc/apache2/sites-available/backend-${PORT}.conf > /dev/null << EOF
<VirtualHost *:${PORT}>
    ServerName backend-${SERVER_NAME,,}.local
    DocumentRoot /var/www/html/it490

    # PHP configuration
    <Directory /var/www/html/it490>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Custom headers to identify server
    Header always set X-Server-Name "${SERVER_NAME}"
    Header always set X-Server-Port "${PORT}"

    ErrorLog \${APACHE_LOG_DIR}/backend_${SERVER_NAME,,}_error.log
    CustomLog \${APACHE_LOG_DIR}/backend_${SERVER_NAME,,}_access.log combined
</VirtualHost>
EOF

# Add the port to ports.conf if not already there
if ! grep -q "Listen ${PORT}" /etc/apache2/ports.conf; then
    echo "Listen ${PORT}" | sudo tee -a /etc/apache2/ports.conf
fi

# Enable the site
sudo a2ensite backend-${PORT}.conf

# Enable necessary modules
sudo a2enmod headers
sudo a2enmod rewrite

# Copy the application files
sudo rm -rf /var/www/html/it490
sudo cp -r /home/cja48/it490/it490 /var/www/html/
sudo chown -R www-data:www-data /var/www/html/it490

# Modify login page to show server name
sudo sed -i "s/<h2>Welcome<\/h2>/<h2>Welcome - ${SERVER_NAME}<\/h2>/" /var/www/html/it490/pages/login.php

# Add server info to the login page
sudo sed -i "/Please enter your credentials to login/a\\
        <div style=\"background: rgba(255,255,255,0.1); padding: 10px; margin: 10px 0; border-radius: 5px; text-align: center;\">\\
            <strong>Served by: ${SERVER_NAME}</strong> | Port: ${PORT}\\
        </div>" /var/www/html/it490/pages/login.php

# Test configuration
sudo apache2ctl configtest

if [ $? -eq 0 ]; then
    echo "Configuration test passed. Restarting Apache..."
    sudo systemctl restart apache2
    sudo systemctl enable apache2
    
    echo "Backend server $SERVER_NAME setup complete!"
    echo "Server accessible at: http://$(hostname -I | awk '{print $1}'):${PORT}"
    echo "Application URL: http://$(hostname -I | awk '{print $1}'):${PORT}/pages/login.php"
else
    echo "Configuration test failed. Please check the configuration."
    exit 1
fi