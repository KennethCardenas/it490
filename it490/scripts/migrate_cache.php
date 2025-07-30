<?php
/**
 * Migration script to set up dog image cache table
 * Run this once to create the database table for caching
 */

require_once __DIR__ . '/../api/connect.php';

echo "Running migration: Create dog image cache table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS DOG_IMAGE_CACHE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breed_name VARCHAR(100) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_width INT,
    image_height INT,
    breed_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    INDEX idx_breed_name (breed_name),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY unique_breed_url (breed_name, image_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ Dog image cache table created successfully!\n";
    
    // Test the cache functionality
    require_once __DIR__ . '/../api/dog_cache.php';
    
    echo "Testing cache functionality...\n";
    
    // Test cache stats
    $stats = DogCache::getCacheStats();
    echo "📊 Cache stats: " . json_encode($stats) . "\n";
    
    echo "✅ Migration completed successfully!\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
    exit(1);
}

$conn->close();
?>