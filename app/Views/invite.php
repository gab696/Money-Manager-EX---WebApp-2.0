<?php
/** @var callable $e */
/** @var callable $t */
/** @var string $baseUrl */
/** @var string $csrf */
/** @var string $token */
/** @var ?string $error */
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($t('invite.title', 'Invitation')) ?> — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/style.css">
</head>
<body class="min-h-dvh bg-gradient-to-b from-indigo-600 to-indigo-800 antialiased">

<main class="mx-auto max-w-md min-h-dvh flex flex-col px-6 pt-safe pb-safe">

  <div class="flex-1 flex flex-col justify-end pb-8">
    <div class="text-white">
      <div class="h-12 w-12 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-2xl mb-4">💌</div>
      <h1 class="text-2xl font-semibold"><?= $e($t('invite.title', 'Invitation')) ?></h1>
      <p class="mt-2 text-indigo-100 text-sm"><?= $e($t('invite.subtitle', "L'administrateur t'invite à créer ton compte MMEX Web. Choisis un identifiant et un mot de passe.")) ?></p>
    </div>
  </div>

  <?php if ($error === 'invalid' || $error === 'expired'): ?>
    <div class="bg-white rounded-3xl shadow-xl p-6 text-center">
      <div class="text-4xl mb-2">⏳</div>
      <h2 class="text-lg font-semibold mb-2"><?= $e($t('invite.expired_title', 'Lien invalide ou expiré')) ?></h2>
      <p class="text-sm text-slate-500"><?= $e($t('invite.expired_help', "Demande un nouveau lien d'invitation à l'administrateur.")) ?></p>
    </div>
  <?php else: ?>
    <form method="post" action="<?= $e($baseUrl) ?>/invite/<?= $e($token) ?>" class="bg-white rounded-3xl shadow-xl p-6 space-y-4">
      <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

      <?php if ($error === 'exists'): ?>
        <div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm"><?= $e($t('settings.users_exists')) ?></div>
      <?php elseif ($error): ?>
        <div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm"><?= $e($t('settings.users_invalid')) ?></div>
      <?php endif; ?>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= $e($t('login.username')) ?></label>
        <input type="text" name="username" required autocomplete="username"
          class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-base">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= $e($t('login.password')) ?></label>
        <input type="password" name="password" required minlength="4" autocomplete="new-password"
          class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-base">
      </div>
      <button type="submit"
        class="w-full h-12 rounded-xl bg-indigo-600 text-white font-semibold text-base shadow-sm hover:bg-indigo-700 transition">
        <?= $e($t('invite.submit', 'Créer mon compte')) ?>
      </button>
    </form>
  <?php endif; ?>

  <p class="mt-6 text-center text-xs text-indigo-100/80">MMEX Web</p>
</main>

</body>
</html>
