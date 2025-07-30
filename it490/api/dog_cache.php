<?php
/**
 * Dog Image Cache Helper
 * Provides database-backed caching for dog images and breed data
 */

class DogCache {
    private static $conn = null;
    private const DEFAULT_CACHE_DURATION = 86400; // 24 hours in seconds
    
    /**
     * Initialize database connection
     */
    private static function getConnection() {
        if (self::$conn === null) {
            // Use the same connection logic as connect.php but create our own instance
            $config = [
                'host'      => '100.70.204.26',
                'port'      => 3306,
                'username'  => 'BARKBUDDYUSER',
                'password'  => 'new_secure_password',
                'database'  => 'BARKBUDDY',
                'ssl'       => false,
                'timeout'   => 5
            ];
            
            try {
                $conn = mysqli_init();
                $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, $config['timeout']);
                
                if (!$conn->real_connect(
                    $config['host'],
                    $config['username'],
                    $config['password'],
                    $config['database'],
                    $config['port']
                )) {
                    throw new Exception("MySQL connection failed: " . $conn->connect_error);
                }
                
                $conn->set_charset("utf8mb4");
                self::$conn = $conn;
                
            } catch (Exception $e) {
                error_log("DogCache connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$conn;
    }
    
    /**
     * Get cached breed image from database
     * @param string $breed The breed name
     * @return array|null Cached image data or null if not found/expired
     */
    public static function getBreedImage($breed) {
        $conn = self::getConnection();
        
        $stmt = $conn->prepare("
            SELECT image_url, image_width, image_height, breed_info 
            FROM DOG_IMAGE_CACHE 
            WHERE breed_name = ? AND expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $breed);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row) {
                return [
                    'image_url' => $row['image_url'],
                    'breed' => $breed,
                    'width' => $row['image_width'],
                    'height' => $row['image_height'],
                    'breed_info' => $row['breed_info'] ? json_decode($row['breed_info'], true) : null
                ];
            }
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Cache a breed image in database
     * @param string $breed The breed name
     * @param array $imageData Image data from Dog API
     * @param int $cacheDuration Cache duration in seconds
     */
    public static function cacheBreedImage($breed, $imageData, $cacheDuration = null) {
        $conn = self::getConnection();
        $cacheDuration = $cacheDuration ?: self::DEFAULT_CACHE_DURATION;
        
        // Calculate expiration time
        $expiresAt = date('Y-m-d H:i:s', time() + $cacheDuration);
        
        $stmt = $conn->prepare("
            INSERT INTO DOG_IMAGE_CACHE 
            (breed_name, image_url, image_width, image_height, breed_info, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            image_width = VALUES(image_width),
            image_height = VALUES(image_height),
            breed_info = VALUES(breed_info),
            updated_at = CURRENT_TIMESTAMP,
            expires_at = VALUES(expires_at)
        ");
        
        $breedInfoJson = isset($imageData['breed_info']) ? json_encode($imageData['breed_info']) : null;
        
        $stmt->bind_param("ssiiss", 
            $breed,
            $imageData['image_url'],
            $imageData['width'],
            $imageData['height'],
            $breedInfoJson,
            $expiresAt
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get multiple cached images for a breed
     * @param string $breed The breed name
     * @param int $limit Maximum number of images to return
     * @return array Array of image URLs
     */
    public static function getBreedImages($breed, $limit = 3) {
        $conn = self::getConnection();
        
        $stmt = $conn->prepare("
            SELECT image_url 
            FROM DOG_IMAGE_CACHE 
            WHERE breed_name = ? AND expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->bind_param("si", $breed, $limit);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $images = [];
            
            while ($row = $result->fetch_assoc()) {
                $images[] = $row['image_url'];
            }
            
            $stmt->close();
            return $images;
        }
        
        $stmt->close();
        return [];
    }
    
    /**
     * Cache multiple images for a breed
     * @param string $breed The breed name
     * @param array $imageUrls Array of image URLs
     * @param int $cacheDuration Cache duration in seconds
     */
    public static function cacheBreedImages($breed, $imageUrls, $cacheDuration = null) {
        foreach ($imageUrls as $imageUrl) {
            $imageData = [
                'image_url' => $imageUrl,
                'width' => null,
                'height' => null,
                'breed_info' => null
            ];
            self::cacheBreedImage($breed, $imageData, $cacheDuration);
        }
    }
    
    /**
     * Clean up expired cache entries
     * @return int Number of deleted entries
     */
    public static function cleanupExpiredCache() {
        $conn = self::getConnection();
        
        $stmt = $conn->prepare("DELETE FROM DOG_IMAGE_CACHE WHERE expires_at < NOW()");
        $stmt->execute();
        
        $deletedCount = $conn->affected_rows;
        $stmt->close();
        
        return $deletedCount;
    }
    
    /**
     * Clear all cache for a specific breed
     * @param string $breed The breed name
     * @return int Number of deleted entries
     */
    public static function clearBreedCache($breed) {
        $conn = self::getConnection();
        
        $stmt = $conn->prepare("DELETE FROM DOG_IMAGE_CACHE WHERE breed_name = ?");
        $stmt->bind_param("s", $breed);
        $stmt->execute();
        
        $deletedCount = $conn->affected_rows;
        $stmt->close();
        
        return $deletedCount;
    }
    
    /**
     * Get cache statistics
     * @return array Cache statistics
     */
    public static function getCacheStats() {
        $conn = self::getConnection();
        
        $stats = [];
        
        // Total cached entries
        $result = $conn->query("SELECT COUNT(*) as total FROM DOG_IMAGE_CACHE");
        $stats['total_entries'] = $result->fetch_assoc()['total'];
        
        // Active (non-expired) entries
        $result = $conn->query("SELECT COUNT(*) as active FROM DOG_IMAGE_CACHE WHERE expires_at > NOW()");
        $stats['active_entries'] = $result->fetch_assoc()['active'];
        
        // Expired entries
        $result = $conn->query("SELECT COUNT(*) as expired FROM DOG_IMAGE_CACHE WHERE expires_at <= NOW()");
        $stats['expired_entries'] = $result->fetch_assoc()['expired'];
        
        // Unique breeds cached
        $result = $conn->query("SELECT COUNT(DISTINCT breed_name) as breeds FROM DOG_IMAGE_CACHE WHERE expires_at > NOW()");
        $stats['cached_breeds'] = $result->fetch_assoc()['breeds'];
        
        return $stats;
    }
}
?>