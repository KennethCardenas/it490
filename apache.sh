#!/bin/bash

# BarkBuddy IT490 Deployment Script
# This script deploys your it490 project to Apache document root

echo "🚀 Starting BarkBuddy deployment to Apache..."

# Define paths
SOURCE_DIR="$HOME/it490/it490"
TARGET_DIR="/var/www/html/it490"
BACKUP_DIR="/var/www/html/it490_backup_$(date +%Y%m%d_%H%M%S)"

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    echo "❌ Error: Source directory $SOURCE_DIR not found!"
    exit 1
fi

# Create backup of existing deployment
if [ -d "$TARGET_DIR" ]; then
    echo "📦 Creating backup at $BACKUP_DIR..."
    sudo cp -r "$TARGET_DIR" "$BACKUP_DIR"
fi

# Copy files to Apache directory
echo "📁 Copying files from $SOURCE_DIR to $TARGET_DIR..."
sudo cp -r "$SOURCE_DIR"/* "$TARGET_DIR/" 2>/dev/null || {
    echo "📁 Creating target directory and copying files..."
    sudo mkdir -p "$TARGET_DIR"
    sudo cp -r "$SOURCE_DIR"/* "$TARGET_DIR/"
}

# Set proper ownership (www-data for Apache)
echo "🔒 Setting proper ownership (www-data:www-data)..."
sudo chown -R www-data:www-data "$TARGET_DIR"

# Set proper permissions
echo "🔐 Setting proper permissions..."
# Directories: 755 (rwxr-xr-x)
sudo find "$TARGET_DIR" -type d -exec chmod 755 {} \;
# PHP files: 644 (rw-r--r--)
sudo find "$TARGET_DIR" -type f -name "*.php" -exec chmod 644 {} \;
# CSS files: 644 (rw-r--r--)
sudo find "$TARGET_DIR" -type f -name "*.css" -exec chmod 644 {} \;
# SQL files: 644 (rw-r--r--)
sudo find "$TARGET_DIR" -type f -name "*.sql" -exec chmod 644 {} \;
# Image files: 644 (rw-r--r--)
sudo find "$TARGET_DIR" -type f \( -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.gif" \) -exec chmod 644 {} \;

# Make specific files executable if needed
# Example: sudo chmod +x "$TARGET_DIR/scripts/some_script.sh"

# Check if Apache is running
if systemctl is-active --quiet apache2; then
    echo "🌐 Apache is running - deployment complete!"
else
    echo "⚠️  Apache is not running. Starting Apache..."
    sudo systemctl start apache2
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache started successfully!"
    else
        echo "❌ Failed to start Apache. Please check the service status."
    fi
fi

# Display access information
echo ""
echo "🎉 Deployment completed successfully!"
echo "📍 Your application is now available at:"
echo "   http://$(hostname -I | awk '{print $1}')/it490/"
echo "   http://localhost/it490/"
echo ""
echo "📋 Deployed files:"
echo "   • API Schema: /var/www/html/it490/api/schema.sql"
echo "   • Header: /var/www/html/it490/header.php"
echo "   • Logout: /var/www/html/it490/pages/logout.php"
echo "   • Styles: /var/www/html/it490/styles/style.css"
echo ""
echo "💡 To redeploy, just run: ./deploy_to_apache.sh"
echo "🔄 Backup created at: $BACKUP_DIR" 