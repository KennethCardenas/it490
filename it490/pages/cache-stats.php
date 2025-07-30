<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

// Check if user is admin (you may need to adjust this based on your user role system)
$user = $_SESSION['user'];
// For now, allow all authenticated users to view cache stats

require_once __DIR__ . '/../api/dog_api.php';
require_once __DIR__ . '/../api/dog_cache.php';

// Handle cache cleanup action
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cleanup_expired':
                $deletedCount = DogCache::cleanupExpiredCache();
                $message = "Cleaned up $deletedCount expired cache entries.";
                break;
            case 'clear_all':
                DogAPI::clearCache();
                $message = "All cache data cleared.";
                break;
        }
    }
}

// Get cache statistics
$stats = DogAPI::getCacheStats();
?>
<?php
    $title = "Cache Statistics";
    $pageCss = '/it490/styles/admin.css';
    include_once __DIR__ . '/../header.php';
?>

<div class="admin-container">
    <h2>Dog API Cache Statistics</h2>
    
    <?php if (!empty($message)): ?>
        <div class="message success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Database Cache</h3>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-label">Total Entries:</span>
                    <span class="stat-value"><?= $stats['database_cache']['total_entries'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Active Entries:</span>
                    <span class="stat-value active"><?= $stats['database_cache']['active_entries'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Expired Entries:</span>
                    <span class="stat-value expired"><?= $stats['database_cache']['expired_entries'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Cached Breeds:</span>
                    <span class="stat-value"><?= $stats['database_cache']['cached_breeds'] ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <h3>File Cache</h3>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-label">Total Files:</span>
                    <span class="stat-value"><?= $stats['file_cache']['total_files'] ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="cache-actions">
        <h3>Cache Management</h3>
        <form method="POST" style="display: inline-block; margin-right: 10px;">
            <input type="hidden" name="action" value="cleanup_expired">
            <button type="submit" class="btn btn-warning" onclick="return confirm('Clean up expired cache entries?')">
                🧹 Cleanup Expired
            </button>
        </form>
        
        <form method="POST" style="display: inline-block;">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear ALL cache data? This will slow down the next few requests.')">
                🗑️ Clear All Cache
            </button>
        </form>
    </div>
    
    <div class="cache-info">
        <h3>How Caching Works</h3>
        <ul>
            <li><strong>Database Cache:</strong> Primary cache stored in MySQL for fast retrieval</li>
            <li><strong>File Cache:</strong> Backup cache stored as JSON files</li>
            <li><strong>Cache Duration:</strong> Images are cached for 24 hours by default</li>
            <li><strong>Performance:</strong> Database cache is checked first, then file cache, then API</li>
        </ul>
    </div>
</div>

<style>
.admin-container {
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
}

.message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.message.success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
}

.stat-card h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.stats {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-weight: 500;
    color: #6c757d;
}

.stat-value {
    font-weight: bold;
    font-size: 1.1em;
    color: #495057;
}

.stat-value.active {
    color: #28a745;
}

.stat-value.expired {
    color: #dc3545;
}

.cache-actions {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.cache-actions h3 {
    margin-top: 0;
    color: #495057;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.cache-info {
    background: #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.cache-info h3 {
    margin-top: 0;
    color: #495057;
}

.cache-info ul {
    margin: 0;
    padding-left: 20px;
}

.cache-info li {
    margin-bottom: 8px;
    color: #6c757d;
}
</style>

<?php include_once __DIR__ . '/../footer.php'; ?>