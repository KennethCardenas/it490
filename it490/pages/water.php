<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth.php';
if (!function_exists('requireAuth')) {
    die('Authentication system not available');
}
requireAuth();

$mqClientPath = __DIR__ . '/../includes/mq_client.php';
if (!file_exists($mqClientPath)) {
    die('MQ client system not available');
}
require_once $mqClientPath;
require_once __DIR__ . '/../api/connect.php';

function addWaterToDatabase($conn, $dogId, $userId, $amount, $notes) {
    $stmt = $conn->prepare("INSERT INTO WATER_TRACKING (dog_id, user_id, amount_ml, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $dogId, $userId, $amount, $notes);
    return $stmt->execute();
}

function getWaterFromDatabase($conn, $dogId) {
    $stmt = $conn->prepare("SELECT * FROM WATER_TRACKING WHERE dog_id = ? ORDER BY timestamp DESC");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteWaterFromDatabase($conn, $waterId, $userId) {
    $stmt = $conn->prepare("DELETE FROM WATER_TRACKING WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $waterId, $userId);
    return $stmt->execute();
}

$dogId = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

$user = $_SESSION['user'] ?? null;
$waterResp = [];
$waterEntries = [];
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$dog = null;
$totalToday = 0;
$dailyGoal = 1000; 

// Handle delete request
if (isset($_GET['delete'])) {
    $waterId = (int)$_GET['delete'];
    try {
        $payload = [
            'type' => 'delete_water',
            'water_id' => $waterId,
            'user_id' => $user['id'] ?? 0
        ];
        
        $resp = @sendMessage($payload);
        
        if (empty($resp) || ($resp['status'] ?? '') !== 'success') {
            if (deleteWaterFromDatabase($conn, $waterId, $user['id'] ?? 0)) {
                $redirectMsg = urlencode('Water entry deleted successfully');
            } else {
                throw new Exception('Failed to delete water entry');
            }
        } else {
            $redirectMsg = urlencode($resp['message'] ?? 'Water entry deleted successfully');
        }
        
        header("Location: water.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $waterResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        if ($amount <= 0) {
            throw new Exception('Please enter a valid water amount');
        }

        $payload = [
            'type' => 'add_water',
            'dog_id' => $dogId,
            'user_id' => $user['id'] ?? 0,
            'amount' => $amount,
            'notes' => $notes
        ];

        $waterResp = @sendMessage($payload);
        if (empty($waterResp) || ($waterResp['status'] ?? '') !== 'success') {
            if (addWaterToDatabase($conn, $dogId, $user['id'] ?? 0, $amount, $notes)) {
                $redirectMsg = urlencode('Water entry added successfully');
            } else {
                throw new Exception('Failed to add water entry');
            }
        } else {
            $redirectMsg = urlencode($waterResp['message'] ?? 'Water entry added successfully');
        }
        header("Location: water.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $waterResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($msg) {
    $waterResp['message'] = urldecode($msg);
}

try {
    $dogResp = @sendMessage(['type' => 'get_dog', 'dog_id' => $dogId]);
    if (($dogResp['status'] ?? '') === 'success') {
        $dog = $dogResp['dog'] ?? null;
    } else {
        $stmt = $conn->prepare("SELECT * FROM DOGS WHERE id = ?");
        $stmt->bind_param("i", $dogId);
        $stmt->execute();
        $dog = $stmt->get_result()->fetch_assoc();
    }
} catch (Exception $e) {
    $stmt = $conn->prepare("SELECT * FROM DOGS WHERE id = ?");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    $dog = $stmt->get_result()->fetch_assoc();
}

try {
    $resp = @sendMessage(['type' => 'get_water', 'dog_id' => $dogId]);
    if (($resp['status'] ?? '') === 'success') {
        $waterEntries = $resp['entries'] ?? [];
    } else {
        $waterEntries = getWaterFromDatabase($conn, $dogId);
    }
} catch (Exception $e) {
    $waterEntries = getWaterFromDatabase($conn, $dogId);
}

$today = date('Y-m-d');
foreach ($waterEntries as $entry) {
    if (isset($entry['timestamp']) && strpos($entry['timestamp'], $today) === 0) {
        $totalToday += (int)($entry['amount_ml'] ?? 0);
    }
}

$title = "Water Tracking" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");

$headerPath = __DIR__ . '/../header.php';
if (file_exists($headerPath)) {
    include_once $headerPath;
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';
}
?>

<div class="water-app">
    <div class="water-header">
        <div class="header-content">
            <h1><i class="fas fa-tint water-icon"></i> Water Tracker</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($waterResp['message'])): ?>
            <div class="alert <?= strpos($waterResp['message'], 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <i class="fas <?= strpos($waterResp['message'], 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($waterResp['message']) ?>
            </div>
        <?php endif; ?>

        <div class="progress-section">
            <div class="progress-card">
                <h3><i class="fas fa-chart-line"></i> Today's Progress</h3>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= min(($totalToday / $dailyGoal) * 100, 100) ?>%">
                        <span class="progress-text"><?= $totalToday ?> ml / <?= $dailyGoal ?> ml</span>
                    </div>
                    <div class="paw-marks">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-paw <?= $totalToday >= ($i * 200) ? 'active' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Add Water Entry
                </div>
                <form method="POST" class="water-form">
                    <div class="form-group">
                        <label for="amount"><i class="fas fa-water"></i> Amount (ml)</label>
                        <input type="number" id="amount" name="amount" min="1" required placeholder="Enter amount in ml">
                    </div>
                    <div class="form-group">
                        <label for="notes"><i class="fas fa-comment-dots"></i> Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any notes about this entry"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Record Entry
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Water History
                </div>
                <?php if (empty($waterEntries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tint-slash"></i>
                        <p>No water entries recorded yet</p>
                    </div>
                <?php else: ?>
                    <div class="water-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-water"></i> Amount</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-comment"></i> Notes</th>
                                    <th><i class="fas fa-trash-alt"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waterEntries as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['amount_ml'] ?? '') ?> ml</td>
                                        <td><?= isset($entry['timestamp']) ? date('M j, g:i a', strtotime($entry['timestamp'])) : '' ?></td>
                                        <td><?= !empty($entry['notes']) ? htmlspecialchars($entry['notes']) : '<span class="no-notes">No notes</span>' ?></td>
                                        <td>
                                            <a href="#" class="delete-link" data-water-id="<?= $entry['id'] ?>" data-dog-id="<?= $dogId ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this water entry? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel">Cancel</button>
            <a id="confirmDelete" class="btn-delete">Delete Permanently</a>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #3498db;
    --secondary-color: #e74c3c;
    --success-color: #2ecc71;
    --error-color: #e74c3c;
    --text-color: #2c3e50;
    --light-gray: #ecf0f1;
    --medium-gray: #bdc3c7;
    --white: #ffffff;
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --border-radius: 12px;
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: #f5f7fa;
}

.water-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.water-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.water-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.water-header h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.water-icon {
    color: var(--primary-color);
}

.paw-icon {
    color: var(--secondary-color);
}

.main-container {
    max-width: 1100px;
    margin: 0 auto;
}

.alert {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin: 20px auto;
    max-width: 800px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow);
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: var(--success-color);
    border-left: 4px solid var(--success-color);
}

.alert-error {
    background-color: rgba(231, 76, 60, 0.1);
    color: var(--error-color);
    border-left: 4px solid var(--error-color);
}

.progress-section {
    margin-bottom: 30px;
}

.progress-card {
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow);
    max-width: 800px;
    margin: 0 auto;
}

.progress-card h3 {
    font-size: 1.3rem;
    margin-bottom: 20px;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-container {
    margin-top: 20px;
}

.progress-bar {
    height: 30px;
    background: linear-gradient(90deg, var(--primary-color), #2980b9);
    border-radius: 15px;
    position: relative;
    transition: var(--transition);
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progress-text {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    color: var(--white);
    font-weight: 600;
    font-size: 0.9rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.paw-marks {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    padding: 0 10px;
}

.paw-marks .fa-paw {
    font-size: 22px;
    color: var(--light-gray);
    transition: var(--transition);
}

.paw-marks .fa-paw.active {
    color: var(--secondary-color);
    transform: scale(1.1);
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-top: 20px;
}

.form-card, .history-card {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color), #2980b9);
    color: var(--white);
    padding: 18px 25px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.water-form {
    padding: 25px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
    font-size: 1rem;
}

.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 14px;
    border: 2px solid var(--light-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
}

.form-group input[type="number"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--success-color), #27ae60);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: var(--transition);
}

.btn-submit:hover {
    background: linear-gradient(135deg, #27ae60, #219653);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
}

.empty-state {
    padding: 50px 20px;
    text-align: center;
    color: var(--medium-gray);
}

.empty-state i {
    font-size: 3.5rem;
    color: var(--light-gray);
    margin-bottom: 20px;
}

.empty-state p {
    font-size: 1.3rem;
    margin: 0;
    color: var(--medium-gray);
}

.water-entries {
    padding: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--light-gray);
}

th {
    background-color: #f8f9fa;
    color: var(--text-color);
    font-weight: 600;
    font-size: 0.95rem;
}

tr:hover {
    background-color: #f8f9fa;
}

.no-notes {
    color: var(--medium-gray);
    font-style: italic;
}

.delete-link {
    color: var(--secondary-color);
    transition: color 0.3s;
}

.delete-link:hover {
    color: #c0392b;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}

.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from {transform: translateY(-50px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
}

.modal-header {
    padding: 20px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.4rem;
}

.close-modal {
    font-size: 1.8rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #ecf0f1;
}

.modal-body {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
}

.modal-body p {
    margin: 0 0 15px;
    color: #2c3e50;
    line-height: 1.5;
}

.modal-footer {
    padding: 15px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-cancel, .btn-delete {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    font-size: 0.9rem;
}

.btn-cancel {
    background-color: #ecf0f1;
    color: #7f8c8d;
}

.btn-cancel:hover {
    background-color: #d5dbdb;
}

.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    text-decoration: none;
    display: inline-block;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .water-header h1 {
        font-size: 2rem;
    }
    
    .water-header h2 {
        font-size: 1.3rem;
    }
    
    .progress-card, .alert {
        max-width: 100%;
    }
}

@media (max-width: 600px) {
    .water-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .water-header h2 {
        font-size: 1.1rem;
    }
    
    .card-header {
        font-size: 1.1rem;
        padding: 15px 20px;
    }
    
    .water-form {
        padding: 20px;
    }
    
    th, td {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deleteModal');
    const deleteLinks = document.querySelectorAll('.delete-link');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const closeModal = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.btn-cancel');
    
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const waterId = this.getAttribute('data-water-id');
            const dogId = this.getAttribute('data-dog-id');
            
            confirmDeleteBtn.href = `water.php?dog_id=${dogId}&delete=${waterId}`;
            modal.style.display = 'block';
        });
    });
    
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

<?php
$footerPath = __DIR__ . '/../footer.php';
if (file_exists($footerPath)) {
    include_once $footerPath;
} else {
    echo '</body></html>';
}
?>
