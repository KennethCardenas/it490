<?php
include_once '../includes/mq_client.php';
include_once '../header.php';

// Fetch sitters from MQ
$payload = [ "type" => "get_sitters" ];
$response = sendMessage($payload);
$sitters = $response['sitters'] ?? [];
?>

<div class="container mt-4">
    <h2 class="text-center text-success">Find a Dog Sitter</h2>

    <div class="row mt-3">
        <?php foreach ($sitters as $sitter): ?>
            <div class="col-md-6">
                <div class="card p-3 shadow-sm mb-4">
                    <h4><?= htmlspecialchars($sitter['username']) ?></h4>
                    <p><strong>Availability:</strong> <?= htmlspecialchars($sitter['availability']) ?></p>
                    <p><strong>Rate:</strong> $<?= htmlspecialchars($sitter['rate']) ?>/hr</p>
                    <p><strong>Experience:</strong> <?= htmlspecialchars($sitter['bio']) ?></p>
                    <a href="sitter-profile.php?id=<?= urlencode($sitter['sitter_id']) ?>" class="btn btn-primary">View Profile</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once '../footer.php'; ?>