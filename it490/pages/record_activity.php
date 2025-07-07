<?php 
include_once '../header.php'; 
include_once __DIR__ . '/../includes/mq_client_local.php'; // adjust if needed

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'type' => 'record_activity',
        'sitter' => $_POST['sitter'],
        'dog' => $_POST['dog'],
        'activity_type' => $_POST['activity_type'],
        'notes' => $_POST['notes']
    ];

    $response = sendMessage($payload);

    if ($response['status'] === 'success') {
        $success = "Activity recorded successfully.";
    } else {
        $error = "Error: " . $response['message'];
    }
}
?>

<h2 style="text-align:center; color:#4CAF50;">Record Dog Activity</h2>

<div class="form-container">
    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php elseif ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="sitter">Sitter Username:</label>
        <input type="text" id="sitter" name="sitter" placeholder="e.g. emily_r" required>

        <label for="dog">Dog Name:</label>
        <input type="text" id="dog" name="dog" placeholder="e.g. Buddy" required>

        <label for="activity_type">Activity Type:</label>
        <select id="activity_type" name="activity_type" required>
            <option value="Feeding">Feeding</option>
            <option value="Walking">Walking</option>
            <option value="Training">Training</option>
            <option value="Medication">Medication</option>
            <option value="Other">Other</option>
        </select>

        <label for="notes">Notes:</label>
        <textarea id="notes" name="notes" rows="4" placeholder="Describe what you did..." required></textarea>

        <button type="submit">Submit Activity</button>
    </form>
</div>

<style>
.form-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
form label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
}
form input, form textarea, form select {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
form button {
    margin-top: 15px;
    padding: 10px 20px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
</style>

<?php include_once '../footer.php'; ?>