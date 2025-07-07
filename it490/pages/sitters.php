<?php
include_once __DIR__ . '/../auth.php';
requireAuth();
include_once __DIR__ . '/../includes/mq_client.php';

$resp = sendMessage(['type' => 'list_sitters']);
$sitters = ($resp['status'] ?? '') === 'success' ? ($resp['sitters'] ?? []) : [];
?>
<?php $title = 'Sitter Lookup'; include_once __DIR__ . '/../header.php'; ?>
<div class="profile-container">
    <h2>Available Sitters</h2>
    <ul>
        <?php foreach ($sitters as $s): ?>
            <li>
                <?= htmlspecialchars($s['username']) ?> - <?= htmlspecialchars($s['bio'] ?? '') ?>
                <?php if (isOwner()): ?>
                    <a href="grant_access.php?sitter_id=<?= $s['id'] ?>">Grant Access</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
