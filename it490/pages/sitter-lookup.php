<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
require_once __DIR__ . '/../api/connect.php';

$sitters = [];
$stmt = $conn->prepare("SELECT * FROM SITTERS");

if ($stmt->execute()) {
    $res = $stmt->get_result();
    $sitters = $res->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

$id = $_GET['id'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$bio = $_GET['bio'] ?? '';
$experience = $_GET['experience'] ?? '';
$rating = $_GET['rating'] ?? '';

$sql = "SELECT * FROM SITTERS WHERE 1=1";
$params = [];
$types = '';

if (!empty($id)) {
    $sql .= " AND id = ?";
    $params[] = (int)$id;
    $types .= 'i';
}
if (!empty($user_id)) {
    $sql .= " AND user_id = ?";
    $params[] = (int)$user_id;
    $types .= 'i';
}
if (!empty($bio)) {
    $sql .= " AND bio LIKE ?";
    $params[] = "%$bio%";
    $types .= 's';
}
if (!empty($experience)) {
    $sql .= " AND experience_years >= ?";
    $params[] = (int)$experience;
    $types .= 'i';
}
if (!empty($rating)) {
    $sql .= " AND rating >= ?";
    $params[] = (float)$rating;
    $types .= 'd';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sitters = [];
while ($row = $result->fetch_assoc()) {
    $sitters[] = $row;
}
?>
<?php $title = "Tasks"; include_once __DIR__ . '/../header.php'; ?>
<html>
    <link rel="stylesheet" href="../styles/sitter-lookup.css">
    <h2>Sitter Lookup</h2>
    <div>
        <form id="sitterSearchForm" method="GET">
        <input name="id" placeholder="Sitter ID" type="number">
        <input name="user_id" placeholder="User ID" type="number">
        <input name="bio" placeholder="Bio">
        <input name="experience" type="number" placeholder="Years of Experience">
        <input name="rating" type="number" step="0.1" min="0" max="5" placeholder="Rating">
        <br>
        <button type="submit">Search</button>
        </form>
    </div>
    <div>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>USER ID</th>
                <th>BIO</th>
                <th>Years of Experience</th>
                <th>Rating</th>
            </tr>

            <?php foreach ($sitters as $sitter): ?>
                <tr>
                    <td><?= htmlspecialchars($sitter['id']) ?></td>
                    <td><?= htmlspecialchars($sitter['user_id']) ?></td>
                    <td><?= htmlspecialchars($sitter['bio']) ?></td>
                    <td><?= htmlspecialchars($sitter['experience_years']) ?></td>
                    <td><?= htmlspecialchars($sitter['rating']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</html>
<?php include_once __DIR__ . '/../footer.php'; ?>