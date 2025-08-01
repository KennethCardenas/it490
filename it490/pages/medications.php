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

function scheduleMedicationInDatabase($conn, $dogId, $userId, $medication, $dosage, $scheduleTime, $notes) {
    $stmt = $conn->prepare("INSERT INTO MEDICATION_SCHEDULES (dog_id, user_id, medication, dosage, schedule_time, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $dogId, $userId, $medication, $dosage, $scheduleTime, $notes);
    return $stmt->execute();
}

function completeMedicationInDatabase($conn, $medId) {
    $stmt = $conn->prepare("UPDATE MEDICATION_SCHEDULES SET completed = 1 WHERE id = ?");
    $stmt->bind_param("i", $medId);
    return $stmt->execute();
}

function getMedicationsFromDatabase($conn, $dogId) {
    $stmt = $conn->prepare("SELECT * FROM MEDICATION_SCHEDULES WHERE dog_id = ? ORDER BY schedule_time");
    $stmt->bind_param("i", $dogId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$dogId = isset($_GET['dog_id']) ? (int)$_GET['dog_id'] : 0;
if ($dogId <= 0) {
    header("Location: dogs.php");
    exit();
}

$user = $_SESSION['user'] ?? null;
$medResp = [];
$medEntries = [];
$msg = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$dog = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $medication = trim($_POST['medication'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $scheduleTime = trim($_POST['schedule_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($medication)) throw new Exception('Medication name is required');
        if (empty($scheduleTime)) throw new Exception('Schedule time is required');

        $payload = [
            'type' => 'schedule_medication',
            'dog_id' => $dogId,
            'user_id' => $user['id'] ?? 0,
            'medication' => $medication,
            'dosage' => $dosage,
            'schedule_time' => $scheduleTime,
            'notes' => $notes
        ];

        $medResp = @sendMessage($payload);

        if (empty($medResp) || ($medResp['status'] ?? '') !== 'success') {
            if (scheduleMedicationInDatabase($conn, $dogId, $user['id'] ?? 0, $medication, $dosage, $scheduleTime, $notes)) {
                $redirectMsg = urlencode('Medication scheduled');
            } else {
                throw new Exception('Failed to schedule medication');
            }
        } else {
            $redirectMsg = urlencode($medResp['message'] ?? 'Medication scheduled');
        }

        header("Location: medications.php?dog_id={$dogId}&msg={$redirectMsg}");
        exit();
    } catch (Exception $e) {
        $medResp['message'] = 'Error: ' . $e->getMessage();
    }
}

if (isset($_GET['complete'])) {
    try {
        $medId = (int)$_GET['complete'];
        if ($medId > 0) {
            $resp = @sendMessage(['type' => 'complete_medication', 'med_id' => $medId]);
            if (empty($resp) || ($resp['status'] ?? '') !== 'success') {
                completeMedicationInDatabase($conn, $medId);
            }
            $redirectMsg = urlencode('Medication marked as completed');
            header("Location: medications.php?dog_id={$dogId}&msg={$redirectMsg}");
            exit();
        }
    } catch (Exception $e) {
        $medResp['message'] = 'Error completing medication: ' . $e->getMessage();
    }
}

if ($msg) {
    $medResp['message'] = urldecode($msg);
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
    $resp = @sendMessage(['type' => 'get_medications', 'dog_id' => $dogId]);
    if (($resp['status'] ?? '') === 'success') {
        $medEntries = $resp['medications'] ?? [];
    } else {
        $medEntries = getMedicationsFromDatabase($conn, $dogId);
    }
} catch (Exception $e) {
    $medEntries = getMedicationsFromDatabase($conn, $dogId);
}

$title = "Medications" . ($dog ? " - " . htmlspecialchars($dog['name']) : "");
$headerPath = __DIR__ . '/../header.php';
if (file_exists($headerPath)) {
    include_once $headerPath;
} else {
    echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></head><body>';
}
?>

<div class="medications-app">
    <div class="medications-header">
        <div class="header-content">
            <h1><i class="fas fa-pills medication-icon"></i> Medication Tracker</h1>
            <?php if ($dog): ?>
                <h2>For <?= htmlspecialchars($dog['name']) ?> <i class="fas fa-paw paw-icon"></i></h2>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($medResp['message'])): ?>
            <div class="alert <?= strpos($medResp['message'], 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <i class="fas <?= strpos($medResp['message'], 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($medResp['message']) ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Schedule Medication
                </div>
                <form method="POST" class="medication-form">
                    <div class="form-group">
                        <label for="medication"><i class="fas fa-pills"></i> Medication Name</label>
                        <input type="text" id="medication" name="medication" required placeholder="Enter medication name">
                    </div>
                    <div class="form-group">
                        <label for="dosage"><i class="fas fa-prescription-bottle-alt"></i> Dosage</label>
                        <input type="text" id="dosage" name="dosage" placeholder="Enter dosage (e.g., 5mg)">
                    </div>
                    <div class="form-group">
                        <label for="schedule_time"><i class="fas fa-clock"></i> Schedule Time</label>
                        <input type="datetime-local" id="schedule_time" name="schedule_time" required>
                    </div>
                    <div class="form-group">
                        <label for="notes"><i class="fas fa-comment-dots"></i> Notes</label>
                        <textarea id="notes" name="notes" placeholder="Any special instructions"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Schedule Medication
                    </button>
                </form>
            </div>

            <div class="history-card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Medication Schedule
                </div>
                <?php if (empty($medEntries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-prescription-bottle"></i>
                        <p>No medications scheduled yet</p>
                    </div>
                <?php else: ?>
                    <div class="medication-entries">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-pills"></i> Medication</th>
                                    <th><i class="fas fa-prescription-bottle-alt"></i> Dosage</th>
                                    <th><i class="fas fa-clock"></i> Time</th>
                                    <th><i class="fas fa-check-circle"></i> Status</th>
                                    <th><i class="fas fa-edit"></i> Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medEntries as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['medication'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($entry['dosage'] ?? '') ?></td>
                                        <td><?= isset($entry['schedule_time']) ? date('M j, g:i a', strtotime($entry['schedule_time'])) : '' ?></td>
                                        <td><?= ($entry['completed'] ?? 0) ? '<span class="completed">Completed</span>' : '<span class="pending">Pending</span>' ?></td>
                                        <td>
                                            <?php if (!($entry['completed'] ?? 0)): ?>
                                                <a href="?dog_id=<?= $dogId ?>&complete=<?= $entry['id'] ?>" class="complete-btn">
                                                    <i class="fas fa-check"></i> Mark Complete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($entry['notes'])): ?>
                                        <tr class="med-description-row">
                                            <td colspan="5">
                                                <div class="description-content">
                                                    <strong>Notes:</strong> <?= htmlspecialchars($entry['notes']) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$footerPath = __DIR__ . '/../footer.php';
if (file_exists($footerPath)) {
    include_once $footerPath;
} else {
    echo '</body></html>';
}
?>

