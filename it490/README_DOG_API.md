# The Dog API Integration Setup

This document explains how to set up **The Dog API** (thedogapi.com) integration for the Bark Buddy project.

## Quick Setup

### 1. Get Your API Key
1. Go to [https://thedogapi.com/](https://thedogapi.com/)
2. Sign up for a free account
3. Get your API key from your dashboard

### 2. Configure the API Key
1. Open `api/dog_api.php`
2. Find this line:
   ```php
   private const API_KEY = 'live_YOUR_API_KEY_HERE';
   ```
3. Replace `'live_YOUR_API_KEY_HERE'` with your actual API key:
   ```php
   private const API_KEY = 'live_abc123your_actual_key_here';
   ```

### 3. Test the Integration
1. Run the test script: `/test_dog_api.php`
2. Verify all tests pass
3. Go to `/pages/dogs.php` to see it in action

## Features

✅ **Breed-Specific Images**: Automatically fetches images matching your dog's breed  
✅ **Breed Information**: Rich breed data including temperament, lifespan, origin  
✅ **Smart Caching**: 1-hour cache for optimal performance  
✅ **Form Suggestions**: Auto-complete breed names from 200+ breeds  
✅ **Fallback System**: Shows random dog images if breed-specific ones aren't available  
✅ **Error Handling**: Graceful error handling with logging  

## API Endpoints Used

- `GET /v1/breeds` - Get all breed information
- `GET /v1/images/search` - Search for breed-specific images
- `GET /v1/images/search?breed_ids={id}` - Get images for specific breed

## Implementation Details

### Core Functions

- `DogAPI::getBreedImage($breed)` - Get single image for a breed
- `DogAPI::getBreedImages($breed, $count)` - Get multiple images
- `DogAPI::getAllBreeds()` - Get complete breed data
- `DogAPI::getBreedNames()` - Get breed names for form suggestions
- `DogAPI::getRandomImage()` - Get random dog image

### Cache System

- Location: `/cache/dog_api/`
- Duration: 1 hour for images, 24 hours for breed data
- Automatic cleanup of expired cache files

### Error Handling

- Logs errors to PHP error log
- Graceful fallback to random images
- User-friendly error messages
- Connection timeout handling

## Troubleshooting

### "API key not configured" Error
- Make sure you replaced `'live_YOUR_API_KEY_HERE'` with your actual API key
- Check that your API key is valid on thedogapi.com

### "Unable to connect" Error
- Check your internet connection
- Verify your API key is correct
- Check PHP error logs for detailed error messages

### Images Not Loading
- Run `/test_dog_api.php` to diagnose issues
- Check browser developer tools for image loading errors
- Verify cache directory permissions

## Performance Tips

1. **Use Caching**: The integration includes automatic caching - don't disable it
2. **Batch Requests**: Use `getBreedImages()` instead of multiple `getBreedImage()` calls
3. **Monitor API Usage**: Free tier has rate limits
4. **Clear Cache**: Use `DogAPI::clearCache()` if needed

## API Limits

- **Free Tier**: 1000 requests per month
- **Rate Limit**: 10 requests per minute
- **Image Limit**: 25 images per request maximum

## Integration with Your Project

The Dog API is now integrated into:
- `pages/dogs.php` - Displays breed images for user's dogs
- Form autocomplete for breed selection
- Responsive image display with error handling

## Support

- The Dog API Documentation: [https://docs.thedogapi.com/](https://docs.thedogapi.com/)
- Issues with this integration? Check the test script first
- PHP error logs contain detailed debugging information 