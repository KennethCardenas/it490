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

$dogId = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

$user = $_SESSION['user'] ?? null;
$careResp = [];
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$dog = null;
$logs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        
        if (empty($note)) {
            throw new Exception('Note is required');
        }

        $payload = [
            'type' => 'add_care_log',
            'dog_id' => $dogId,
            'user_id' => $user['id'] ?? 0,
            'note' => $note
        ];

        $careResp = sendMessage($payload);
        $redirectMsg = urlencode($careResp['message'] ?? 'Care log added successfully');
        header("Location: care.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $careResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if ($msg) {
    $careResp['message'] = urldecode($msg);
}

try {
    $dogResp = sendMessage(['type' => 'get_dog', 'dog_id' => $dogId]);
    if (($dogResp['status'] ?? '') === 'success') {
        $dog = $dogResp['dog'] ?? null;
    }
} catch (Exception $e) {
}

try {
    $resp = sendMessage(['type' => 'get_care_logs', 'dog_id' => $dogId]);
    if (($resp['status'] ?? '') === 'success') {
        $logs = $resp['logs'] ?? [];
    }
} catch (Exception $e) {
}

$title = "Care Logs" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");

$headerPath = __DIR__ . '/../header.php';
if (file_exists($headerPath)) {
    include_once $headerPath;
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';
}
?>

<div class="care-app">
    <div class="care-header">
        <div class="header-content">
            <h1><i class="fas fa-notes-medical care-icon"></i> Care Logs</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($careResp['message'])): ?>
            <div class="alert <?= strpos($careResp['message'], 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <i class="fas <?= strpos($careResp['message'], 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($careResp['message']) ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Add Care Log
                </div>
                <form method="POST" class="care-form">
                    <div class="form-group">
                        <label for="note"><i class="fas fa-sticky-note"></i> Note</label>
                        <textarea id="note" name="note" required placeholder="Enter care notes (feeding, grooming, etc.)"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Add Log
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Care History
                </div>
                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No care logs found</p>
                    </div>
                <?php else: ?>
                    <div class="care-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-sticky-note"></i> Note</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['note'] ?? '') ?></td>
                                        <td><?= isset($log['created_at']) ? date('M j, g:i a', strtotime($log['created_at'])) : '' ?></td>
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

.care-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.care-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.care-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.care-header h2 {
    font-size: 1.5rem;
    color: var(--text-color);
    font-weight: 400;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.care-icon {
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

.care-form {
    padding: 25px;
}

.form-group {
    margin-bottom: 25px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
    font-size: 1rem;
}

.form-group textarea {
    width: calc(100% - 28px);
    padding: 14px;
    border: 2px solid var(--light-gray);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    margin: 0;
    box-sizing: border-box;
    min-height: 150px;
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

.care-entries {
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

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .care-header h1 {
        font-size: 2rem;
    }
    
    .care-header h2 {
        font-size: 1.3rem;
    }
}

@media (max-width: 600px) {
    .care-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .care-header h2 {
        font-size: 1.1rem;
    }
    
    .card-header {
        font-size: 1.1rem;
        padding: 15px 20px;
    }
    
    .care-form {
        padding: 20px;
    }
    
    th, td {
        padding: 10px 12px;
        font-size: 0.9rem;
    }
}
</style>

<?php
$footerPath = __DIR__ . '/../footer.php';
if (file_exists($footerPath)) {
    include_once $footerPath;
} else {
    echo '</body></html>';
}
?>
