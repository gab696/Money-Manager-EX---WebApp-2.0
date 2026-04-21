<?php /** @var callable $e */ /** @var callable $t */ /** @var string $baseUrl */ ?>
<!doctype html>
<html lang="<?= $e($locale ?? 'fr') ?>"><head>
<meta charset="utf-8"><title><?= $e($t('error404.title')) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-dvh bg-slate-50 flex items-center justify-center text-center px-6">
<div>
  <div class="text-6xl mb-4">🤷</div>
  <h1 class="text-2xl font-semibold"><?= $e($t('error404.title')) ?></h1>
  <p class="mt-2 text-slate-500"><?= $e($t('error404.text')) ?></p>
  <a href="<?= $e($baseUrl ?? '') ?>/" class="mt-6 inline-flex items-center gap-2 px-5 h-11 rounded-xl bg-indigo-600 text-white font-semibold"><?= $e($t('common.back')) ?></a>
</div>
</body></html>
