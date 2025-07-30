-- Migration: Create dog image cache table
-- This table will cache dog breed images from The Dog API to improve performance

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
    
    -- Indexes for performance
    INDEX idx_breed_name (breed_name),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY unique_breed_url (breed_name, image_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;