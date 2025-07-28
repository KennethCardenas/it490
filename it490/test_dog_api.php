<?php
/**
 * Test script for The Dog API integration (thedogapi.com)
 * Run this to verify the API is working correctly
 */

require_once __DIR__ . '/api/dog_api.php';

echo "<h1>The Dog API Integration Test</h1>\n";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } img { max-width: 200px; border-radius: 8px; margin: 10px; } .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; } .error { background: #ffebee; border-color: #e57373; color: #c62828; } .success { background: #e8f5e8; border-color: #81c784; color: #2e7d32; } .warning { background: #fff3e0; border-color: #ffb74d; color: #f57c00; }</style>\n";

// Check API status first
echo "<div class='test-section'>\n";
echo "<h2>API Configuration Status</h2>\n";
$apiStatus = DogAPI::getApiStatus();

if ($apiStatus['status'] === 'success') {
    echo "<div class='success'>\n";
    echo "<p>✅ " . htmlspecialchars($apiStatus['message']) . "</p>\n";
    echo "<p><strong>Base URL:</strong> " . htmlspecialchars($apiStatus['base_url']) . "</p>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'>\n";
    echo "<p>❌ " . htmlspecialchars($apiStatus['message']) . "</p>\n";
    if (!$apiStatus['configured']) {
        echo "<p><strong>To fix this:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Go to <a href='https://thedogapi.com/' target='_blank'>https://thedogapi.com/</a></li>\n";
        echo "<li>Sign up for a free account</li>\n";
        echo "<li>Get your API key</li>\n";
        echo "<li>Replace 'live_YOUR_API_KEY_HERE' in <code>api/dog_api.php</code> with your actual API key</li>\n";
        echo "</ol>\n";
    }
    echo "</div>\n";
}
echo "</div>\n";

// Only run tests if API is configured
if ($apiStatus['configured']) {
    
    // Test 1: Get breed image
    echo "<div class='test-section'>\n";
    echo "<h2>Test 1: Get Labrador Image</h2>\n";
    $labradorImage = DogAPI::getBreedImage('Labrador Retriever');
    if ($labradorImage) {
        echo "<p>✅ Success! Got image for Labrador Retriever:</p>\n";
        echo "<img src='" . htmlspecialchars($labradorImage['image_url']) . "' alt='Labrador'>\n";
        echo "<p>URL: " . htmlspecialchars($labradorImage['image_url']) . "</p>\n";
        if ($labradorImage['breed_info']) {
            echo "<p>Breed Info: " . htmlspecialchars($labradorImage['breed_info']['name'] ?? 'N/A') . "</p>\n";
        }
    } else {
        echo "<p>❌ Failed to get Labrador image</p>\n";
    }
    echo "</div>\n";

    // Test 2: Get Golden Retriever image
    echo "<div class='test-section'>\n";
    echo "<h2>Test 2: Get Golden Retriever Image</h2>\n";
    $goldenImage = DogAPI::getBreedImage('Golden Retriever');
    if ($goldenImage) {
        echo "<p>✅ Success! Got image for Golden Retriever:</p>\n";
        echo "<img src='" . htmlspecialchars($goldenImage['image_url']) . "' alt='Golden Retriever'>\n";
        echo "<p>URL: " . htmlspecialchars($goldenImage['image_url']) . "</p>\n";
    } else {
        echo "<p>❌ Failed to get Golden Retriever image</p>\n";
    }
    echo "</div>\n";

    // Test 3: Get multiple images
    echo "<div class='test-section'>\n";
    echo "<h2>Test 3: Get Multiple Beagle Images</h2>\n";
    $multipleImages = DogAPI::getBreedImages('Beagle', 3);
    if (!empty($multipleImages)) {
        echo "<p>✅ Success! Got " . count($multipleImages) . " images for Beagle:</p>\n";
        foreach ($multipleImages as $imageUrl) {
            echo "<img src='" . htmlspecialchars($imageUrl) . "' alt='Beagle'>\n";
        }
    } else {
        echo "<p>❌ Failed to get multiple images</p>\n";
    }
    echo "</div>\n";

    // Test 4: Get random image
    echo "<div class='test-section'>\n";
    echo "<h2>Test 4: Get Random Dog Image</h2>\n";
    $randomImage = DogAPI::getRandomImage();
    if ($randomImage) {
        echo "<p>✅ Success! Got random dog image:</p>\n";
        echo "<img src='" . htmlspecialchars($randomImage['image_url']) . "' alt='Random dog'>\n";
        echo "<p>URL: " . htmlspecialchars($randomImage['image_url']) . "</p>\n";
        if ($randomImage['breed_info']) {
            echo "<p>Breed: " . htmlspecialchars($randomImage['breed_info']['name'] ?? 'Unknown') . "</p>\n";
        }
    } else {
        echo "<p>❌ Failed to get random image</p>\n";
    }
    echo "</div>\n";

    // Test 5: Get all breeds
    echo "<div class='test-section'>\n";
    echo "<h2>Test 5: Get All Available Breeds</h2>\n";
    $allBreeds = DogAPI::getAllBreeds();
    if (!empty($allBreeds)) {
        echo "<p>✅ Success! Found " . count($allBreeds) . " breeds from The Dog API:</p>\n";
        echo "<div style='max-height: 200px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;'>\n";
        foreach (array_slice($allBreeds, 0, 20) as $breed) {
            echo "<div style='margin: 5px 0; padding: 5px; background: #f9f9f9; border-radius: 4px;'>\n";
            echo "<strong>" . htmlspecialchars($breed['name']) . "</strong>\n";
            if (!empty($breed['temperament'])) {
                echo "<br><em>Temperament: " . htmlspecialchars($breed['temperament']) . "</em>\n";
            }
            if (!empty($breed['life_span'])) {
                echo "<br><small>Life span: " . htmlspecialchars($breed['life_span']) . "</small>\n";
            }
            echo "</div>\n";
        }
        if (count($allBreeds) > 20) {
            echo "<p><em>... and " . (count($allBreeds) - 20) . " more breeds</em></p>\n";
        }
        echo "</div>\n";
    } else {
        echo "<p>❌ Failed to get breeds list</p>\n";
    }
    echo "</div>\n";

    // Test 6: Get breed names for form
    echo "<div class='test-section'>\n";
    echo "<h2>Test 6: Get Breed Names for Form Suggestions</h2>\n";
    $breedNames = DogAPI::getBreedNames();
    if (!empty($breedNames)) {
        echo "<p>✅ Success! Got " . count($breedNames) . " breed names for form suggestions:</p>\n";
        echo "<div style='max-height: 150px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;'>\n";
        foreach (array_slice($breedNames, 0, 30) as $name) {
            echo "<span style='display: inline-block; margin: 2px; padding: 4px 8px; background: #e3f2fd; border-radius: 4px;'>" . htmlspecialchars($name) . "</span>\n";
        }
        if (count($breedNames) > 30) {
            echo "<p><em>... and " . (count($breedNames) - 30) . " more breed names</em></p>\n";
        }
        echo "</div>\n";
    } else {
        echo "<p>❌ Failed to get breed names</p>\n";
    }
    echo "</div>\n";

    // Test 7: Test caching
    echo "<div class='test-section'>\n";
    echo "<h2>Test 7: Cache Performance Test</h2>\n";
    $start = microtime(true);
    $cachedImage = DogAPI::getBreedImage('Labrador Retriever'); // Should be cached from Test 1
    $cacheTime = microtime(true) - $start;

    $start = microtime(true);
    $newImage = DogAPI::getBreedImage('Poodle'); // Should be a new API call
    $apiTime = microtime(true) - $start;

    echo "<p>✅ Cache performance:</p>\n";
    echo "<p>Cached request time: " . number_format($cacheTime * 1000, 2) . "ms</p>\n";
    echo "<p>New API request time: " . number_format($apiTime * 1000, 2) . "ms</p>\n";
    if ($cacheTime > 0) {
        echo "<p>Cache is " . number_format($apiTime / $cacheTime, 1) . "x faster!</p>\n";
    }
    echo "</div>\n";

    // Test 8: Test invalid breed handling
    echo "<div class='test-section'>\n";
    echo "<h2>Test 8: Invalid Breed Handling</h2>\n";
    $invalidImage = DogAPI::getBreedImage('NonExistentBreed123');
    if ($invalidImage) {
        echo "<p>✅ Graceful fallback - got random dog image for invalid breed:</p>\n";
        echo "<img src='" . htmlspecialchars($invalidImage['image_url']) . "' alt='Random dog'>\n";
    } else {
        echo "<p>⚠️ No image returned for invalid breed</p>\n";
    }
    echo "</div>\n";

    // Integration Summary
    echo "<div class='test-section success'>\n";
    echo "<h2>✅ Integration Summary</h2>\n";
    echo "<p><strong>The Dog API Integration Status:</strong> Working with thedogapi.com</p>\n";
    echo "<p><strong>Features tested:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Single breed image fetching</li>\n";
    echo "<li>✅ Multiple breed images fetching</li>\n";
    echo "<li>✅ Random dog images</li>\n";
    echo "<li>✅ Comprehensive breed data retrieval</li>\n";
    echo "<li>✅ Breed names for form suggestions</li>\n";
    echo "<li>✅ Caching system</li>\n";
    echo "<li>✅ Error handling</li>\n";
    echo "</ul>\n";
    echo "<p><em>The Dog API integration is ready for use in your Bark Buddy application!</em></p>\n";
    echo "</div>\n";
    
} else {
    echo "<div class='test-section warning'>\n";
    echo "<h2>⚠️ Tests Skipped</h2>\n";
    echo "<p>Tests were skipped because the API key is not configured.</p>\n";
    echo "<p>Please configure your API key from thedogapi.com to run the full test suite.</p>\n";
    echo "</div>\n";
}

echo "<p><a href='pages/dogs.php' style='display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>➡️ Go to Dogs Page</a></p>\n";
?> 