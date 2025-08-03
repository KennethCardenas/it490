<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth.php';
requireAuth();

require_once __DIR__ . '/../includes/mq_client.php';
require_once __DIR__ . '/../api/connect.php'; 

$user = $_SESSION['user'];
$dogId = (int)($_GET['dog_id'] ?? 0);
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

function addMealToDatabase($conn, $dogId, $userId, $mealType, $amount, $notes) {
    $stmt = $conn->prepare("INSERT INTO MEAL_TRACKING (dog_id, user_id, meal_type, amount, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $dogId, $userId, $mealType, $amount, $notes);
    return $stmt->execute();
}

function getMealsFromDatabase($conn, $dogId) {
    $stmt = $conn->prepare("SELECT * FROM MEAL_TRACKING WHERE dog_id = ? ORDER BY timestamp DESC");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mealType = trim($_POST['meal_type'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($mealType) || empty($amount)) {
        $_SESSION['meal_message'] = 'Meal type and amount are required';
    } else {
        try {
            $payload = [
                'type' => 'add_meal',
                'dog_id' => $dogId,
                'user_id' => $user['id'],
                'meal_type' => $mealType,
                'amount' => $amount,
                'notes' => $notes
            ];
            
            $mqResponse = @sendMessage($payload); 
            
            if (empty($mqResponse) || ($mqResponse['status'] ?? '') !== 'success') {
                if (addMealToDatabase($conn, $dogId, $user['id'], $mealType, $amount, $notes)) {
                    $_SESSION['meal_message'] = 'Meal added successfully';
                } else {
                    throw new Exception('Failed to add meal');
                }
            } else {
                $_SESSION['meal_message'] = $mqResponse['message'] ?? 'Meal added successfully';
            }
            
        } catch (Exception $e) {
            $_SESSION['meal_message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    header("Location: meal.php?dog_id=$dogId");
    exit();
}

$message = $_SESSION['meal_message'] ?? '';
unset($_SESSION['meal_message']); 

try {
    $mqResponse = @sendMessage(['type' => 'get_meals', 'dog_id' => $dogId]);
    $meals = ($mqResponse['status'] ?? '') === 'success' ? ($mqResponse['entries'] ?? []) : getMealsFromDatabase($conn, $dogId);
} catch (Exception $e) {
    $meals = getMealsFromDatabase($conn, $dogId);
}

$dog = null;
try {
    $stmt = $conn->prepare("SELECT * FROM DOGS WHERE id = ?");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    $dog = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
}

$totalToday = 0;
$today = date('Y-m-d');
foreach ($meals as $meal) {
    if (isset($meal['timestamp']) && strpos($meal['timestamp'], $today) === 0) {
        $totalToday++;
    }
}

$title = "Meal Tracking" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");
include_once __DIR__ . '/../header.php';
?>

<div class="meal-app">
    <div class="meal-header">
        <div class="header-content">
            <h1><i class="fas fa-utensils meal-icon"></i> Meal Tracker</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="progress-section">
            <div class="progress-card">
                <h3><i class="fas fa-chart-line"></i> Today's Progress</h3>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= min(($totalToday / 3) * 100, 100) ?>%">
                        <span class="progress-text"><?= $totalToday ?> / 3 meals</span>
                    </div>
                </div>
                <div class="progress-percent"><?= round(min(($totalToday / 3) * 100, 100)) ?>%</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Add Meal Entry
                </div>
                <form method="POST" class="meal-form">
                    <div class="form-group">
                        <label for="meal_type"><i class="fas fa-utensils"></i> Meal Type</label>
                        <select id="meal_type" name="meal_type" required>
                            <option value="">Select meal type</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount"><i class="fas fa-weight"></i> Amount (cups)</label>
                        <input type="number" id="amount" name="amount" step="0.25" min="0.25" max="10" placeholder="Enter amount in cups" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><i class="fas fa-comment-dots"></i> Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any special notes about the meal"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Record Meal
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Meal History
                </div>
                <?php if (empty($meals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p>No meal entries recorded yet</p>
                    </div>
                <?php else: ?>
                    <div class="meal-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-utensils"></i> Meal Type</th>
                                    <th><i class="fas fa-weight"></i> Amount</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-comment"></i> Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meals as $meal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($meal['meal_type'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($meal['amount'] ?? '') ?> cups</td>
                                        <td><?= isset($meal['timestamp']) ? date('M j, g:i a', strtotime($meal['timestamp'])) : '' ?></td>
                                        <td><?= !empty($meal['notes']) ? htmlspecialchars($meal['notes']) : '<span class="no-notes">No notes</span>' ?></td>
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
.meal-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.meal-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.meal-header h1 {
    font-size: 2.5rem;
    color: #3498db;
    margin-bottom: 10px;
}

.meal-header h2 {
    font-size: 1.5rem;
    color: #2c3e50;
}

.meal-icon {
    color: #e67e22;
}

.paw-icon {
    color: #e74c3c;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin: 20px auto;
    max-width: 800px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
}

.progress-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 20px;
    max-width: 600px;
    margin: 0 auto 30px;
}

.progress-container {
    width: 100%;
    background-color: #ecf0f1;
    border-radius: 20px;
    height: 30px;
    position: relative;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #e67e22, #d35400);
    transition: width 0.5s ease;
    position: relative;
}

.progress-text {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

.progress-percent {
    text-align: right;
    margin-top: 5px;
    font-weight: bold;
    color: #e67e22;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.form-card, .history-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #e67e22, #d35400);
    color: white;
    padding: 18px 25px;
    font-size: 1.2rem;
}

.meal-form {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 1rem;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #e67e22, #d35400);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
}

.empty-state {
    padding: 50px 20px;
    text-align: center;
    color: #bdc3c7;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

.no-notes {
    color: #bdc3c7;
    font-style: italic;
}

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_once __DIR__ . '/../footer.php'; ?>


