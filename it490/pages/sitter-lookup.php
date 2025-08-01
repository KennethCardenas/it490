<?php
include_once __DIR__ . '/../auth.php';
requireAuth();

$user = $_SESSION['user'];
require_once __DIR__ . '/../api/connect.php';

// fetch sitters
$sitters = [];
$stmt = $conn->prepare("SELECT * FROM SITTERS");

if ($stmt->execute()) {
    $res = $stmt->get_result();
    $sitters = $res->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

?>
<?php $title = "Tasks"; include_once __DIR__ . '/../header.php'; ?>
<html>
    <link rel="stylesheet" href="../styles/sitter-lookup.css">
    <h2>Sitter Lookup</h2>
    <div>
        <form id="sitterSearchForm">
            <input name="name" placeholder="Name">
            <input name="email" placeholder="Email">
            <input name="phone" placeholder="Phone">
            <input name="rating" type="number" step="0.1" min="0" max="5" placeholder="Rating">
            <input name="experience" type="number" placeholder="Years of Experience">
            <br>
            <button type="submit">Search</button>
        </form>
    </div>
    <div>
        <ul>
            <?php foreach ($sitters as $sitter): ?>
                <li>
                    <p><?=$sitter?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</html>
<?php include_once __DIR__ . '/../footer.php'; ?>