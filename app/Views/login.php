<?php /** @var callable $e */ /** @var string $baseUrl */ /** @var string $csrf */ /** @var ?string $error */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#4f46e5">
  <title>Connexion — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $e($asset('/assets/style.css')) ?>">
  <?php include __DIR__ . '/layout/pwa_head.php'; ?>
</head>
<body class="min-h-dvh bg-gradient-to-b from-indigo-600 to-indigo-800 text-slate-900 antialiased">

<main class="mx-auto max-w-md min-h-dvh flex flex-col px-6 pt-safe pb-safe">
  <div class="flex-1 flex flex-col justify-end pb-10">
    <div class="flex items-center gap-3 text-white">
      <div class="h-12 w-12 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-2xl">💸</div>
      <div>
        <div class="text-xl font-semibold leading-tight">Money Manager EX</div>
        <div class="text-sm text-indigo-100">Saisie mobile</div>
      </div>
    </div>
    <h1 class="mt-8 text-white text-3xl font-semibold leading-tight">
      Ajoute une dépense<br>en 3 secondes.
    </h1>
    <p class="mt-3 text-indigo-100 text-sm leading-relaxed">
      Tes transactions sont mises en file et aspirées par MMEX desktop lors de la prochaine synchro.
    </p>
  </div>

  <form method="post" action="<?= $e($baseUrl) ?>/login" class="bg-white rounded-3xl shadow-xl p-6 space-y-4">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

    <?php if ($error): ?>
      <div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm">Identifiants invalides.</div>
    <?php endif; ?>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">Utilisateur</label>
      <input type="text" name="username" autocomplete="username" required
        class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-base">
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1.5">Mot de passe</label>
      <input type="password" name="password" autocomplete="current-password" required
        class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-base">
    </div>
    <button type="submit"
      class="w-full h-12 rounded-xl bg-indigo-600 text-white font-semibold text-base shadow-sm hover:bg-indigo-700 active:bg-indigo-800 transition">
      Se connecter
    </button>
  </form>

  <p class="mt-6 text-center text-xs text-indigo-100/80">MMEX Web · v2.0</p>
</main>
</body>
</html>
