<?php
$code = isset($_GET['code']) ? intval($_GET['code']) : 500;
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include_once __DIR__ . '/navbar.php'; ?>
    <div class="container">
        <h1>Oops! Something went wrong.</h1>
        <p>Error code: <?php echo htmlspecialchars($code); ?></p>
    </div>
</body>
</html>
