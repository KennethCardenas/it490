<?php 
if (!defined('HEADER_INCLUDED')) define('HEADER_INCLUDED', true);

// Unified and corrected CSS paths
$cssPath = '/it490/styles/style.css';
$extraCssPath = '/it490/styles/profile-pages.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'MyApp') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssPath ?>">
    <link rel="stylesheet" href="<?= $extraCssPath ?>">
</head>
<body>
<?php include_once __DIR__ . '/navbar.php'; ?>
