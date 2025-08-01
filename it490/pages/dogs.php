<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
require_once __DIR__ . '/../api/connect.php';

// Handle add dog
$addMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare(
        "INSERT INTO DOGS (owner_id, name, breed, health_status, notes) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "issss",
        $user['id'],
        $name,
        $breed,
        $health,
        $notes
    );

    $name = trim($_POST['name']);
    $breed = trim($_POST['breed']);
    $health = trim($_POST['health_status']);
    $notes = trim($_POST['notes']);

    if ($stmt->execute()) {
        $addMessage = 'Dog added successfully!';
    } else {
        $addMessage = 'Failed to create dog: ' . $conn->error;
    }
    $stmt->close();
}

// Fetch dogs
$dogs = [];
$stmt = $conn->prepare("SELECT * FROM DOGS WHERE owner_id = ?");
$stmt->bind_param("i", $user['id']);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    $dogs = $res->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

$title = "My Dogs";
$pageCss = '/it490/styles/dogs.css';
include_once __DIR__ . '/../header.php';
?>

<div class="dogs-app">
    <div class="dogs-header">
        <div class="header-content">
            <h1><i class="fas fa-paw"></i> My Dogs</h1>
            <p>Manage your dog profiles and access their care features</p>
        </div>
    </div>

    <?php if (!empty($addMessage)): ?>
        <div class="alert <?= strpos($addMessage, 'Failed') !== false ? 'alert-error' : 'alert-success' ?>">
            <i class="fas <?= strpos($addMessage, 'Failed') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
            <?= htmlspecialchars($addMessage) ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="dogs-grid">
            <?php foreach ($dogs as $d): ?>
                <div class="dog-card">
                    <div class="dog-avatar">
                        <i class="fas fa-dog"></i>
                    </div>
                    <div class="dog-info">
                        <h3><?= htmlspecialchars($d['name']) ?></h3>
                        <p class="breed"><?= !empty($d['breed']) ? htmlspecialchars($d['breed']) : 'No breed specified' ?></p>
                        <?php if (!empty($d['health_status'])): ?>
                            <p class="health-status"><i class="fas fa-heartbeat"></i> <?= htmlspecialchars($d['health_status']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="dog-actions">
                        <div class="action-links">
                            <a href="tasks.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Tasks">
                                <i class="fas fa-tasks"></i>
                            </a>
                            <a href="meal.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Meals">
                                <i class="fas fa-utensils"></i>
                            </a>
                            <a href="water.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Water">
                                <i class="fas fa-tint"></i>
                            </a>
                            <a href="care.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Care Logs">
                                <i class="fas fa-notes-medical"></i>
                            </a>
                            <a href="medications.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Medications">
                                <i class="fas fa-pills"></i>
                            </a>
                            <a href="behavior.php?dog_id=<?= $d['id'] ?>" class="action-link" title="Behavior">
                                <i class="fas fa-brain"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="add-dog-card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Add New Dog
            </div>
            <form method="POST" class="dog-form">
                <div class="form-group">
                    <label for="name"><i class="fas fa-dog"></i> Name</label>
                    <input type="text" id="name" name="name" placeholder="Dog's name" required>
                </div>
                <div class="form-group">
                    <label for="breed"><i class="fas fa-dna"></i> Breed</label>
                    <input type="text" id="breed" name="breed" placeholder="Dog's breed">
                </div>
                <div class="form-group">
                    <label for="health_status"><i class="fas fa-heartbeat"></i> Health Status</label>
                    <input type="text" id="health_status" name="health_status" placeholder="Current health status">
                </div>
                <div class="form-group">
                    <label for="notes"><i class="fas fa-sticky-note"></i> Care Instructions</label>
                    <textarea id="notes" name="notes" placeholder="Special care instructions"></textarea>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Add Dog
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.dogs-app {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dogs-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
}

.header-content {
    max-width: 600px;
    margin: 0 auto;
}

.dogs-header h1 {
    font-size: 2.5rem;
    color: #3498db;
    margin-bottom: 10px;
}

.dogs-header p {
    font-size: 1.1rem;
    color: #7f8c8d;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px auto;
    max-width: 800px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #27ae60;
    border-left: 4px solid #27ae60;
}

.alert-error {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

.main-container {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dogs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
    width: 100%;
}

.dog-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
}

.dog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.dog-avatar {
    width: 60px;
    height: 60px;
    background-color: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    color: white;
    font-size: 1.8rem;
}

.dog-info h3 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    color: #2c3e50;
}

.breed {
    color: #7f8c8d;
    margin-bottom: 10px;
}

.health-status {
    color: #e74c3c;
    font-size: 0.9rem;
}

.dog-actions {
    margin-top: auto;
    padding-top: 15px;
}

.action-links {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.action-link {
    color: #7f8c8d;
    font-size: 1.2rem;
    transition: color 0.3s ease;
    text-decoration: none;
}

.action-link:hover {
    color: #3498db;
}

.add-dog-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 600px;
    width: 100%;
    margin: 0 auto;
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

.dog-form {
    padding: 25px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    margin-bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Align label and input left */
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    margin-left: 2px; /* small alignment tweak */
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
    margin: 0;
    display: block;
    text-align: left;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: background 0.3s ease;
}

.btn-submit:hover {
    background: linear-gradient(135deg, #2980b9, #3498db);
}

@media (max-width: 768px) {
    .dogs-grid {
        grid-template-columns: 1fr;
    }

    .dogs-header h1 {
        font-size: 2rem;
    }
}

</style>

<?php $conn->close(); ?>
<?php include_once __DIR__ . '/../footer.php'; ?>
