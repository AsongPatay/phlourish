<?php
/**
 * Shared <head> + open body.
 *
 * @var string $pageTitle
 */
$pageTitle = $pageTitle ?? 'PHLOURISH';
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | PHLOURISH</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=5">
  <?php foreach ((array) $extraCss as $href): ?>
    <?php $sep = strpos($href, '?') !== false ? '&' : '?'; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href . $sep . 'v=5', ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</head>
<body>
