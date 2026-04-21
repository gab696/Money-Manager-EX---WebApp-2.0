<?php /** @var callable $e */ /** @var callable $t */ /** @var string $baseUrl */ /** @var string $csrf */ /** @var string $locale */ ?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($t('setup.title')) ?> — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $e($asset('/assets/style.css')) ?>">
  <?php include __DIR__ . '/layout/pwa_head.php'; ?>
</head>
<body class="min-h-dvh bg-gradient-to-b from-indigo-600 to-indigo-800 antialiased">

<main class="mx-auto max-w-md min-h-dvh flex flex-col px-6 pt-safe pb-safe">
  <div class="flex-1 flex flex-col justify-end pb-8">
    <div class="text-white">
      <div class="h-12 w-12 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-2xl mb-4">💸</div>
      <h1 class="text-2xl font-semibold"><?= $e($t('setup.title')) ?></h1>
      <p class="mt-2 text-indigo-100 text-sm"><?= $e($t('setup.description')) ?></p>
    </div>
  </div>

  <form method="post" action="<?= $e($baseUrl) ?>/setup" class="bg-white rounded-3xl shadow-xl p-6 space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <?php if (!empty($_GET['error'])): ?>
      <div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm"><?= $e($t('setup.error')) ?></div>
    <?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= $e($t('setup.username')) ?></label>
      <input type="text" name="username" required autocomplete="username" value="admin"
        class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= $e($t('setup.password')) ?></label>
      <input type="password" name="password" required minlength="4" autocomplete="new-password"
        class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
    </div>
    <button type="submit" class="w-full h-12 rounded-xl bg-indigo-600 text-white font-semibold"><?= $e($t('setup.submit')) ?></button>
  </form>
</main>

</body>
</html>
