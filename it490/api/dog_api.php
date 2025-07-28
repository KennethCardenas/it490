<?php
/**
 * The Dog API Helper Functions
 * Integrates with https://api.thedogapi.com (The Dog API)
 */

class DogAPI {
    private const BASE_URL = 'https://api.thedogapi.com/v1';
    private const CACHE_DIR = __DIR__ . '/../cache/dog_api/';
    private const CACHE_DURATION = 3600; // 1 hour in seconds
    private const API_KEY = 'live_gytZAHp0BM0T71kA2V5wJQ3RjAYKK2k9fU2EqSZMXD3W2iHsq96GxoFNOlHmLHAL'; // Replace with your API key from thedogapi.com
    
    /**
     * Initialize cache directory
     */
    public static function init() {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }
    
    /**
     * Get breed images for a specific breed
     * @param string $breed The breed name (e.g., "Golden Retriever", "Labrador")
     * @return array|null Returns array with image data or null on failure
     */
    public static function getBreedImage($breed) {
        if (empty($breed)) {
            return null;
        }
        
        // Check cache first
        $cacheKey = 'breed_image_' . md5(strtolower($breed));
        $cachedData = self::getFromCache($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        // Get breed ID first
        $breedId = self::getBreedId($breed);
        
        if ($breedId) {
            // Get image for specific breed
            $url = self::BASE_URL . '/images/search?breed_ids=' . $breedId . '&limit=1';
        } else {
            // Fallback to random image
            $url = self::BASE_URL . '/images/search?limit=1';
        }
        
        $response = self::makeApiCall($url);
        
        if ($response && !empty($response) && is_array($response)) {
            $imageData = $response[0];
            $result = [
                'image_url' => $imageData['url'],
                'breed' => $breed,
                'width' => $imageData['width'] ?? null,
                'height' => $imageData['height'] ?? null,
                'breed_info' => $imageData['breeds'][0] ?? null
            ];
            
            // Cache the result
            self::saveToCache($cacheKey, $result);
            return $result;
        }
        
        return null;
    }
    
    /**
     * Get multiple breed images
     * @param string $breed The breed name
     * @param int $count Number of images to fetch (max 25)
     * @return array Array of image URLs
     */
    public static function getBreedImages($breed, $count = 3) {
        if (empty($breed) || $count < 1) {
            return [];
        }
        
        $count = min($count, 25); // API limit
        
        // Check cache first
        $cacheKey = 'breed_images_' . md5(strtolower($breed) . '_' . $count);
        $cachedData = self::getFromCache($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        // Get breed ID first
        $breedId = self::getBreedId($breed);
        
        if ($breedId) {
            $url = self::BASE_URL . '/images/search?breed_ids=' . $breedId . '&limit=' . $count;
        } else {
            // Fallback to random images
            $url = self::BASE_URL . '/images/search?limit=' . $count;
        }
        
        $response = self::makeApiCall($url);
        
        if ($response && is_array($response)) {
            $images = array_map(function($item) {
                return $item['url'];
            }, $response);
            
            // Cache the result
            self::saveToCache($cacheKey, $images);
            return $images;
        }
        
        return [];
    }
    
    /**
     * Get all available breeds
     * @return array Array of breed data
     */
    public static function getAllBreeds() {
        $cacheKey = 'all_breeds';
        $cachedData = self::getFromCache($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        $url = self::BASE_URL . '/breeds';
        $response = self::makeApiCall($url);
        
        if ($response && is_array($response)) {
            $breeds = array_map(function($breed) {
                return [
                    'id' => $breed['id'],
                    'name' => $breed['name'],
                    'temperament' => $breed['temperament'] ?? '',
                    'life_span' => $breed['life_span'] ?? '',
                    'origin' => $breed['origin'] ?? '',
                    'weight' => $breed['weight'] ?? [],
                    'height' => $breed['height'] ?? []
                ];
            }, $response);
            
            // Cache for longer since breeds don't change often
            self::saveToCache($cacheKey, $breeds, 86400); // 24 hours
            return $breeds;
        }
        
        return [];
    }
    
    /**
     * Get breed names only (for form suggestions)
     * @return array Array of breed names
     */
    public static function getBreedNames() {
        $breeds = self::getAllBreeds();
        return array_column($breeds, 'name');
    }
    
    /**
     * Get random dog image
     * @return array|null Random dog image data
     */
    public static function getRandomImage() {
        $url = self::BASE_URL . '/images/search?limit=1';
        $response = self::makeApiCall($url);
        
        if ($response && !empty($response) && is_array($response)) {
            $imageData = $response[0];
            return [
                'image_url' => $imageData['url'],
                'width' => $imageData['width'] ?? null,
                'height' => $imageData['height'] ?? null,
                'breed_info' => $imageData['breeds'][0] ?? null
            ];
        }
        
        return null;
    }
    
    /**
     * Get breed ID by name
     * @param string $breedName The breed name
     * @return int|null Breed ID or null if not found
     */
    private static function getBreedId($breedName) {
        $breeds = self::getAllBreeds();
        
        foreach ($breeds as $breed) {
            if (strcasecmp($breed['name'], $breedName) === 0) {
                return $breed['id'];
            }
            
            // Also check for partial matches
            if (stripos($breed['name'], $breedName) !== false || 
                stripos($breedName, $breed['name']) !== false) {
                return $breed['id'];
            }
        }
        
        return null;
    }
    
    /**
     * Make API call with authentication
     * @param string $url API endpoint URL
     * @return array|null Decoded response or null on failure
     */
    private static function makeApiCall($url) {
        $headers = [
            'x-api-key: ' . self::API_KEY,
            'Content-Type: application/json'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 15,
                'user_agent' => 'Bark Buddy App/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("The Dog API call failed: " . $url);
            
            // Check if it's an API key issue
            if (self::API_KEY === 'live_YOUR_API_KEY_HERE') {
                error_log("Warning: Please set your API key from thedogapi.com in dog_api.php");
            }
            
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("The Dog API JSON decode error: " . json_last_error_msg());
            return null;
        }
        
        // Check for API errors
        if (isset($decoded['message']) && isset($decoded['status'])) {
            error_log("The Dog API error: " . $decoded['message']);
            return null;
        }
        
        return $decoded;
    }
    
    /**
     * Get data from cache
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    private static function getFromCache($key) {
        $filename = self::CACHE_DIR . $key . '.json';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filename), true);
        
        if (!$data || !isset($data['timestamp']) || !isset($data['data'])) {
            return null;
        }
        
        // Check if cache is expired
        $duration = $data['duration'] ?? self::CACHE_DURATION;
        if (time() - $data['timestamp'] > $duration) {
            unlink($filename);
            return null;
        }
        
        return $data['data'];
    }
    
    /**
     * Save data to cache
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $duration Cache duration in seconds (optional)
     */
    private static function saveToCache($key, $data, $duration = null) {
        $filename = self::CACHE_DIR . $key . '.json';
        
        $cacheData = [
            'timestamp' => time(),
            'data' => $data,
            'duration' => $duration ?: self::CACHE_DURATION
        ];
        
        file_put_contents($filename, json_encode($cacheData));
    }
    
    /**
     * Clear all cached data
     */
    public static function clearCache() {
        if (is_dir(self::CACHE_DIR)) {
            $files = glob(self::CACHE_DIR . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Check if API key is configured
     * @return bool True if API key is set
     */
    public static function isConfigured() {
        return self::API_KEY !== 'live_YOUR_API_KEY_HERE';
    }
    
    /**
     * Get API status and information
     * @return array API status information
     */
    public static function getApiStatus() {
        if (!self::isConfigured()) {
            return [
                'status' => 'error',
                'message' => 'API key not configured. Please get a free API key from https://thedogapi.com/ and update dog_api.php',
                'configured' => false
            ];
        }
        
        // Test API connection
        $testResponse = self::makeApiCall(self::BASE_URL . '/breeds?limit=1');
        
        if ($testResponse !== null) {
            return [
                'status' => 'success',
                'message' => 'The Dog API is working correctly',
                'configured' => true,
                'base_url' => self::BASE_URL
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Unable to connect to The Dog API. Check your API key and internet connection.',
                'configured' => true
            ];
        }
    }
}

// Initialize the cache directory
DogAPI::init();
?> 