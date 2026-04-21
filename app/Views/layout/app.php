<?php /** @var string $__body */ /** @var callable $e */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($title ?? 'MMEX Web') ?></title>
  <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/style.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased">
<?= $__body ?>
<script>window.MMEX_BASE = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= $e($baseUrl) ?>/assets/app.js"></script>
</body>
</html>
