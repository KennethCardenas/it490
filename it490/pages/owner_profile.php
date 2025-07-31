<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

if (!isOwner()) {
    header('Location: /it490/error.php?code=403');
    exit();
}

include_once __DIR__ . '/../includes/mq_client.php';

$user = $_SESSION['user'];
$success_message = '';
$error_message = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $payload = [
        'type' => 'update_profile',
        'user_id' => $user['id'],
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'password' => !empty($_POST['password']) ? $_POST['password'] : null
    ];

    $response = sendMessage($payload);
    
    if ($response['status'] === 'success') {
        $_SESSION['user'] = $response['user'];
        $user = $response['user'];
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Update failed: " . htmlspecialchars($response['message'] ?? "Unknown error.");
    }
}

// Fetch owner's dogs with proper action type
$dogs = [];
try {
    $dogs_response = sendMessage([
        'type' => 'list_owner_dogs',  // Changed from 'list_dogs' to match worker
        'owner_id' => $user['id']
    ]);
    
    if ($dogs_response['status'] === 'success') {
        $dogs = $dogs_response['dogs'] ?? [];
    } else {
        // Fallback to direct database query if MQ fails
        error_log("MQ failed, attempting direct DB query");
        $dogs = fetchDogsDirectly($user['id']);
        if (empty($dogs)) {
            $error_message = "Could not load your dogs. Please try again later.";
        }
    }
} catch (Exception $e) {
    error_log("Error fetching dogs: " . $e->getMessage());
    $dogs = fetchDogsDirectly($user['id']);
}

// Fallback function for direct database access
function fetchDogsDirectly($owner_id) {
    try {
        include __DIR__ . '/../api/connect.php';
        $stmt = $conn->prepare("SELECT id, name, breed, age, notes FROM DOGS WHERE owner_id = ?");
        $stmt->bind_param('i', $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Direct DB fetch failed: " . $e->getMessage());
        return [];
    }
}
?>

<?php $title = "Owner Profile"; include_once __DIR__ . '/../header.php'; ?>

<div class="profile-container">
    <!-- [Previous profile sections remain the same] -->

    <section class="profile-section">
        <h3><i class="fas fa-dog"></i> Your Dogs</h3>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <?= $error_message ?>
                <?php if ($dogs_response['message'] ?? false): ?>
                    <div class="debug-info">
                        <small>Technical Details: <?= htmlspecialchars($dogs_response['message']) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($dogs)): ?>
            <div class="no-dogs">
                <p>You haven't added any dogs yet.</p>
                <a href="dogs.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Dog
                </a>
            </div>
        <?php else: ?>
            <div class="dogs-grid">
                <?php foreach ($dogs as $dog): ?>
                    <div class="dog-card">
                        <div class="dog-info">
                            <h4><?= htmlspecialchars($dog['name']) ?></h4>
                            <p><strong>Breed:</strong> <?= htmlspecialchars($dog['breed']) ?></p>
                            <?php if (!empty($dog['age'])): ?>
                                <p><strong>Age:</strong> <?= $dog['age'] ?> years</p>
                            <?php endif; ?>
                        </div>
                        <div class="dog-actions">
                            <a href="dog_profile.php?id=<?= $dog['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.dogs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}
.dog-card {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    background: white;
}
.no-dogs {
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 0.25rem;
}
.debug-info {
    margin-top: 0.5rem;
    color: #6c757d;
    font-size: 0.85rem;
}
</style>

<?php include_once __DIR__ . '/../footer.php'; ?>