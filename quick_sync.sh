#!/bin/bash

# Quick sync for specific modified files
echo "🔄 Quick syncing modified files to Apache..."

# Define paths
SOURCE_DIR="$HOME/it490/it490"
TARGET_DIR="/var/www/html/it490"

# Files to sync (add more as needed)
FILES=(
    "api/schema.sql"
    "header.php"
    "pages/logout.php"
    "styles/style.css"
)

# Sync each file
for file in "${FILES[@]}"; do
    if [ -f "$SOURCE_DIR/$file" ]; then
        echo "📄 Syncing $file..."
        sudo cp "$SOURCE_DIR/$file" "$TARGET_DIR/$file"
        sudo chown www-data:www-data "$TARGET_DIR/$file"
        sudo chmod 644 "$TARGET_DIR/$file"
    else
        echo "⚠️  Warning: $file not found in source directory"
    fi
done

echo "✅ Quick sync complete!"
echo "🌐 View your changes at: http://$(hostname -I | awk '{print $1}')/it490/" 