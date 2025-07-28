<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
// connect directly to the database
require_once __DIR__ . '/../api/connect.php';
// include Dog API helper
require_once __DIR__ . '/../api/dog_api.php';

// handle add dog
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // owner_id column stores the user that owns the dog
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
        $addMessage = 'Dog added';
    } else {
        $addMessage = 'Failed to create dog: ' . $conn->error;
    }
    $stmt->close();
}

// fetch dogs
$dogs = [];
$stmt = $conn->prepare("SELECT * FROM DOGS WHERE owner_id = ?");

$stmt->bind_param("i", $user['id']);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    $dogs = $res->fetch_all(MYSQLI_ASSOC);
    
    // Fetch breed images for each dog
    foreach ($dogs as &$dog) {
        $breedImage = DogAPI::getBreedImage($dog['breed']);
        $dog['breed_image'] = $breedImage ? $breedImage['image_url'] : null;
    }
}
$stmt->close();

// Get available breeds for the form
$availableBreeds = DogAPI::getBreedNames();
?>
<?php
    $title = "My Dogs";
    $pageCss = '/it490/styles/dogs.css';
    include_once __DIR__ . '/../header.php';
?>
<div class="dogs-container">
    <h2>Your Dogs</h2>
    <?php if (!empty($addMessage)): ?>
        <p><?= htmlspecialchars($addMessage) ?></p>
    <?php endif; ?>
    <div class="dogs-grid">
        <?php foreach ($dogs as $d): ?>
            <div class="dog-card">
                <?php if (!empty($d['breed_image'])): ?>
                    <div class="dog-image">
                        <img src="<?= htmlspecialchars($d['breed_image']) ?>" 
                             alt="<?= htmlspecialchars($d['breed']) ?> image" 
                             loading="lazy"
                             onerror="handleImageError(this);">
                    </div>
                <?php endif; ?>
                <div class="dog-info">
                    <h4><?= htmlspecialchars($d['name']) ?></h4>
                    <p class="breed"><?= htmlspecialchars($d['breed']) ?></p>
                    <?php if (!empty($d['health_status'])): ?>
                        <p class="health-status">Health: <?= htmlspecialchars($d['health_status']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($d['notes'])): ?>
                        <p class="notes"><?= htmlspecialchars($d['notes']) ?></p>
                    <?php endif; ?>
                    <div class="dog-actions">
                        <a href="tasks.php?dog_id=<?= $d['id'] ?>" class="btn-tasks">View Tasks</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($dogs)): ?>
        <p class="no-dogs">You haven't added any dogs yet. Add your first dog below!</p>
    <?php endif; ?>

    <h3>Add Dog</h3>
    <form method="POST" class="add-dog-form">
        <div class="form-group">
            <label for="name">Dog Name *</label>
            <input type="text" id="name" name="name" placeholder="Enter your dog's name" required>
        </div>
        
        <div class="form-group">
            <label for="breed">Breed</label>
            <input type="text" id="breed" name="breed" placeholder="Start typing breed name..." list="breed-suggestions">
            <datalist id="breed-suggestions">
                <?php foreach ($availableBreeds as $breed): ?>
                    <option value="<?= htmlspecialchars($breed) ?>">
                <?php endforeach; ?>
            </datalist>
            <small class="form-help">Start typing to see breed suggestions from The Dog API</small>
        </div>
        
        <div class="form-group">
            <label for="health_status">Health Status</label>
            <input type="text" id="health_status" name="health_status" placeholder="e.g., Healthy, Needs medication">
        </div>
        
        <div class="form-group">
            <label for="notes">Care Instructions</label>
            <textarea id="notes" name="notes" placeholder="Special care instructions, dietary needs, etc." rows="4"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-add-dog">Add Dog</button>
        </div>
    </form>
</div>

<script>
// Enhanced breed input with live filtering
document.addEventListener('DOMContentLoaded', function() {
    const breedInput = document.getElementById('breed');
    const breedDatalist = document.getElementById('breed-suggestions');
    
    if (breedInput && breedDatalist) {
        // Store original options
        const allOptions = Array.from(breedDatalist.querySelectorAll('option')).map(option => option.value);
        
        breedInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            
            // Clear current options
            breedDatalist.innerHTML = '';
            
            // Filter and add matching options
            const filteredOptions = allOptions.filter(breed => 
                breed.toLowerCase().includes(query)
            ).slice(0, 10); // Limit to 10 results for performance
            
            filteredOptions.forEach(breed => {
                const option = document.createElement('option');
                option.value = breed;
                breedDatalist.appendChild(option);
            });
        });
        
        // Capitalize first letter when user selects or leaves field
        breedInput.addEventListener('blur', function() {
            if (this.value) {
                this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
            }
        });
    }
    
    // Add loading state to form submission
    const form = document.querySelector('.add-dog-form');
    const submitBtn = document.querySelector('.btn-add-dog');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding Dog...';
            submitBtn.style.opacity = '0.7';
        });
    }
    
    // Add fade-in animation to dog cards
    const dogCards = document.querySelectorAll('.dog-card');
    dogCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Add error handling for images
function handleImageError(img) {
    img.style.display = 'none';
    const placeholder = document.createElement('div');
    placeholder.className = 'image-placeholder';
    placeholder.innerHTML = '🐕<br><small>No image available</small>';
    placeholder.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #7f8c8d;
        font-size: 2em;
        background: #f8f9fa;
    `;
    img.parentNode.appendChild(placeholder);
}
</script>

<?php $conn->close(); ?>
<?php include_once __DIR__ . '/../footer.php'; ?>
