<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
require_once __DIR__ . '/../api/connect.php';
require_once __DIR__ . '/../api/dog_api.php';

// Handle cache management actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'cleanup':
                $deleted = DogCache::cleanupExpiredCache();
                $message = "Cleaned up {$deleted} expired cache entries.";
                break;
            case 'clear_all':
                DogAPI::clearCache();
                $message = "All cache data cleared successfully.";
                break;
            case 'clear_breed':
                if (!empty($_POST['breed_name'])) {
                    $deleted = DogCache::clearBreedCache($_POST['breed_name']);
                    $message = "Cleared {$deleted} cache entries for breed: " . htmlspecialchars($_POST['breed_name']);
                }
                break;
        }
    }
}

// Get cache statistics
$cacheStats = DogAPI::getCacheStats();
$apiStatus = DogAPI::getApiStatus();

// Get cached breeds list
$stmt = $conn->prepare("
    SELECT breed_name, COUNT(*) as image_count, 
           MAX(created_at) as last_cached,
           MIN(expires_at) as next_expiry,
           SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_count
    FROM DOG_IMAGE_CACHE 
    GROUP BY breed_name 
    ORDER BY last_cached DESC
");
$stmt->execute();
$cachedBreeds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$title = "Cache Administration";
$pageCss = '/it490/styles/cache-admin.css';
include_once __DIR__ . '/../header.php';
?>

<div class="cache-admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-database"></i> Dog API Cache Administration</h1>
        <p>Monitor and manage the Dog API caching system</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- API Status Section -->
    <div class="dashboard-grid">
        <div class="dashboard-card api-status-card">
            <div class="card-header">
                <h2><i class="fas fa-link"></i> API Status</h2>
            </div>
            <div class="card-content">
                <div class="status-indicator <?= $apiStatus['status'] === 'success' ? 'status-success' : 'status-error' ?>">
                    <i class="fas <?= $apiStatus['status'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                    <span><?= htmlspecialchars($apiStatus['message']) ?></span>
                </div>
                <div class="api-details">
                    <p><strong>Configured:</strong> <?= $apiStatus['configured'] ? 'Yes' : 'No' ?></p>
                    <?php if (isset($apiStatus['base_url'])): ?>
                        <p><strong>Base URL:</strong> <?= htmlspecialchars($apiStatus['base_url']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cache Statistics -->
        <div class="dashboard-card stats-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Cache Statistics</h2>
            </div>
            <div class="card-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $cacheStats['database_cache']['total_entries'] ?></div>
                        <div class="stat-label">Total Entries</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $cacheStats['database_cache']['active_entries'] ?></div>
                        <div class="stat-label">Active Entries</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $cacheStats['database_cache']['expired_entries'] ?></div>
                        <div class="stat-label">Expired Entries</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $cacheStats['database_cache']['cached_breeds'] ?></div>
                        <div class="stat-label">Unique Breeds</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $cacheStats['file_cache']['total_files'] ?></div>
                        <div class="stat-label">File Cache Files</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cache Management Controls -->
    <div class="dashboard-card management-card">
        <div class="card-header">
            <h2><i class="fas fa-cogs"></i> Cache Management</h2>
        </div>
        <div class="card-content">
            <div class="management-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Remove all expired cache entries?')">
                        <i class="fas fa-broom"></i> Cleanup Expired Entries
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('This will clear ALL cache data. Are you sure?')">
                        <i class="fas fa-trash"></i> Clear All Cache
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cached Breeds List -->
    <div class="dashboard-card breeds-card">
        <div class="card-header">
            <h2><i class="fas fa-dog"></i> Cached Breeds (<?= count($cachedBreeds) ?>)</h2>
        </div>
        <div class="card-content">
            <?php if (empty($cachedBreeds)): ?>
                <p class="no-data">No breeds cached yet. Dogs will be cached as users add them.</p>
            <?php else: ?>
                <div class="breeds-table-container">
                    <table class="breeds-table">
                        <thead>
                            <tr>
                                <th>Breed Name</th>
                                <th>Images Cached</th>
                                <th>Active/Total</th>
                                <th>Last Cached</th>
                                <th>Next Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cachedBreeds as $breed): ?>
                                <tr>
                                    <td class="breed-name"><?= htmlspecialchars(ucwords($breed['breed_name'])) ?></td>
                                    <td><?= $breed['image_count'] ?></td>
                                    <td>
                                        <span class="count-badge"><?= $breed['active_count'] ?>/<?= $breed['image_count'] ?></span>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($breed['last_cached'])) ?></td>
                                    <td class="expiry-cell">
                                        <?php 
                                        $expiryTime = strtotime($breed['next_expiry']);
                                        $isExpired = $expiryTime < time();
                                        $expiryClass = $isExpired ? 'expired' : 'active';
                                        ?>
                                        <span class="expiry-badge <?= $expiryClass ?>">
                                            <?= date('M j, Y g:i A', $expiryTime) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="clear_breed">
                                            <input type="hidden" name="breed_name" value="<?= htmlspecialchars($breed['breed_name']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline" 
                                                    onclick="return confirm('Clear cache for <?= htmlspecialchars($breed['breed_name']) ?>?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.cache-admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.admin-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 10px;
}

.admin-header p {
    font-size: 1.1rem;
    color: #7f8c8d;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #27ae60;
    border-left: 4px solid #27ae60;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 18px 25px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-content {
    padding: 25px;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 500;
}

.status-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #27ae60;
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.status-error {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}

.api-details {
    font-size: 0.9rem;
    color: #7f8c8d;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.85rem;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.management-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-warning {
    background-color: #f39c12;
    color: white;
}

.btn-warning:hover {
    background-color: #e67e22;
}

.btn-danger {
    background-color: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.btn-outline {
    background-color: transparent;
    color: #7f8c8d;
    border: 1px solid #bdc3c7;
}

.btn-outline:hover {
    background-color: #f8f9fa;
    color: #e74c3c;
    border-color: #e74c3c;
}

.breeds-table-container {
    overflow-x: auto;
}

.breeds-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.breeds-table th,
.breeds-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

.breeds-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.breeds-table tr:hover {
    background-color: #f8f9fa;
}

.breed-name {
    font-weight: 500;
    color: #2c3e50;
}

.count-badge {
    background-color: #3498db;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.expiry-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.expiry-badge.active {
    background-color: rgba(46, 204, 113, 0.1);
    color: #27ae60;
}

.expiry-badge.expired {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.no-data {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 40px 20px;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .cache-admin-container {
        padding: 10px;
    }
    
    .admin-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .management-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php $conn->close(); ?>
<?php include_once __DIR__ . '/../footer.php'; ?>