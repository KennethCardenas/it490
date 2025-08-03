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

function addBehaviorToDatabase($conn, $dogId, $userId, $behavior, $notes) {
    $stmt = $conn->prepare("INSERT INTO BEHAVIOR_LOGS (dog_id, user_id, behavior, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $dogId, $userId, $behavior, $notes);
    return $stmt->execute();
}

function getBehaviorsFromDatabase($conn, $dogId) {
    $stmt = $conn->prepare("SELECT * FROM BEHAVIOR_LOGS WHERE dog_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteBehaviorFromDatabase($conn, $behaviorId, $userId) {
    $stmt = $conn->prepare("DELETE FROM BEHAVIOR_LOGS WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $behaviorId, $userId);
    return $stmt->execute();
}

$dogId = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

$user = $_SESSION['user'] ?? null;
$behaviorResp = [];
$behaviorEntries = [];
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$dog = null;

// Handle behavior deletion
if (isset($_GET['delete_behavior'])) {
    $behaviorId = (int)$_GET['delete_behavior'];
    
    try {
        $payload = [
            'type' => 'delete_behavior',
            'behavior_id' => $behaviorId,
            'user_id' => $user['id'] ?? 0
        ];
        
        $response = sendMessage($payload);
        
        if (($response['status'] ?? '') === 'success') {
            $redirectMsg = 'Behavior entry deleted successfully';
        } else {
            // Fallback to direct database deletion if MQ fails
            if (deleteBehaviorFromDatabase($conn, $behaviorId, $user['id'] ?? 0)) {
                $redirectMsg = 'Behavior entry deleted successfully';
            } else {
                $redirectMsg = $response['message'] ?? 'Failed to delete behavior entry';
            }
        }
        
        header("Location: behavior.php?dog_id={$dogId}&msg=" . urlencode($redirectMsg));
        exit();
    } catch (Exception $e) {
        $behaviorResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $behavior = isset($_POST['behavior']) ? trim($_POST['behavior']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        if (empty($behavior)) {
            throw new Exception('Please enter a behavior description');
        }

        $payload = [
            'type' => 'add_behavior',
            'dog_id' => $dogId,
            'user_id' => $user['id'] ?? 0,
            'behavior' => $behavior,
            'notes' => $notes
        ];

        $behaviorResp = @sendMessage($payload);

        if (empty($behaviorResp) || ($behaviorResp['status'] ?? '') !== 'success') {
            if (addBehaviorToDatabase($conn, $dogId, $user['id'] ?? 0, $behavior, $notes)) {
                $redirectMsg = urlencode('Behavior entry added successfully');
            } else {
                throw new Exception('Failed to add behavior');
            }
        } else {
            $redirectMsg = urlencode($behaviorResp['message'] ?? 'Behavior entry added successfully');
        }

        header("Location: behavior.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $behaviorResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($msg) {
    $behaviorResp['message'] = urldecode($msg);
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
    $resp = @sendMessage(['type' => 'get_behaviors', 'dog_id' => $dogId]);
    if (($resp['status'] ?? '') === 'success') {
        $behaviorEntries = $resp['behaviors'] ?? [];
    } else {
        $behaviorEntries = getBehaviorsFromDatabase($conn, $dogId);
    }
} catch (Exception $e) {
    $behaviorEntries = getBehaviorsFromDatabase($conn, $dogId);
}

$title = "Behavior Tracking" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");

$headerPath = __DIR__ . '/../header.php';
if (file_exists($headerPath)) {
    include_once $headerPath;
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';
}
?>

<div class="behavior-app">
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this behavior entry? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel">Cancel</button>
                <a id="confirmDelete" class="btn-delete">Delete Permanently</a>
            </div>
        </div>
    </div>

    <div class="behavior-header">
        <div class="header-content">
            <h1><i class="fas fa-brain behavior-icon"></i> Behavior Tracker</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($behaviorResp['message'])): ?>
            <div class="alert <?= strpos($behaviorResp['message'], 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <i class="fas <?= strpos($behaviorResp['message'], 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($behaviorResp['message']) ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Add Behavior Entry
                </div>
                <form method="POST" class="behavior-form">
                    <div class="form-fields-container">
                        <div class="form-field-group">
                            <label for="behavior">
                                <i class="fas fa-comment-alt"></i> Behavior Description
                            </label>
                            <input type="text" id="behavior" name="behavior" required placeholder="Describe the behavior">
                        </div>
                        <div class="form-field-group">
                            <label for="notes">
                                <i class="fas fa-comment-dots"></i> Notes
                            </label>
                            <textarea id="notes" name="notes" placeholder="Any additional notes about this behavior"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Record Behavior
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Behavior History
                </div>
                <?php if (empty($behaviorEntries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-brain"></i>
                        <p>No behavior entries recorded yet</p>
                    </div>
                <?php else: ?>
                    <div class="behavior-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-comment-alt"></i> Behavior</th>
                                    <th><i class="fas fa-comment"></i> Notes</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($behaviorEntries as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['behavior'] ?? '') ?></td>
                                        <td><?= !empty($entry['notes']) ? htmlspecialchars($entry['notes']) : '<span class="no-notes">No notes</span>' ?></td>
                                        <td><?= isset($entry['created_at']) ? date('M j, g:i a', strtotime($entry['created_at'])) : '' ?></td>
                                        <td>
                                            <a href="#" class="delete-link" data-behavior-id="<?= $entry['id'] ?>" title="Delete">
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

.behavior-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.behavior-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.behavior-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.behavior-header h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.behavior-icon {
    color: #8e44ad;
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
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
    color: var(--white);
    padding: 18px 25px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.behavior-form {
    padding: 25px;
}

.form-fields-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-field-group {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.form-field-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-color);
    font-size: 1rem;
}

.form-field-group label i {
    min-width: 20px;
    text-align: center;
}

.form-field-group input[type="text"],
.form-field-group textarea {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid var(--light-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    margin: 0;
    box-sizing: border-box;
}

.form-field-group input[type="text"]:focus,
.form-field-group textarea:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.2);
}

.form-field-group textarea {
    min-height: 120px;
    resize: vertical;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #8e44ad, #9b59b6);
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
    background: linear-gradient(135deg, #7d3c98, #8e44ad);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(142, 68, 173, 0.3);
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

.behavior-entries {
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
    transition: color 0.3s ease;
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
    
    .behavior-header h1 {
        font-size: 2rem;
    }
    
    .behavior-header h2 {
        font-size: 1.3rem;
    }
}

@media (max-width: 600px) {
    .behavior-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .behavior-header h2 {
        font-size: 1.1rem;
    }
    
    .card-header {
        font-size: 1.1rem;
        padding: 15px 20px;
    }
    
    .behavior-form {
        padding: 20px;
    }
    
    .modal-content {
        margin: 20% auto;
        width: 95%;
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
            const behaviorId = this.getAttribute('data-behavior-id');
            
            confirmDeleteBtn.href = `behavior.php?dog_id=<?= $dogId ?>&delete_behavior=${behaviorId}`;
            
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
