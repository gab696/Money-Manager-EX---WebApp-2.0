<?php
/** @var callable $e */
/** @var callable $t */
/** @var string $baseUrl */
/** @var string $csrf */
/** @var string $locale */
/** @var ?string $username */
/** @var ?int $userId */
/** @var bool $isAdmin */
/** @var bool $donorHidden */
/** @var array $users */
/** @var array $invitations */
/** @var array $params */
/** @var int $accountCount */
/** @var int $payeeCount */
/** @var int $categoryCount */
/** @var int $pendingCount */
/** @var string $dbPath */

$active = 'settings';
$pwdStatus    = $_GET['pwd']    ?? null;
$guidStatus   = $_GET['guid']   ?? null;
$prefsStatus  = $_GET['prefs']  ?? null;
$userStatus   = $_GET['user']   ?? null;
$inviteStatus = $_GET['invite'] ?? null;
$desktopGuid  = $params['DesktopGuid'] ?? '';
$lastSyncAt   = $params['LastSyncAt']  ?? null;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$servicesUrl = $scheme . '://' . $host . $baseUrl . '/services.php';
$origin      = $scheme . '://' . $host;

$locales = \App\I18n::LOCALES;
$statuses = ['N', 'R', 'F', 'D', 'V'];
?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($t('settings.title')) ?> — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="<?= $e($asset('/assets/style.css')) ?>">
  <?php include __DIR__ . '/layout/pwa_head.php'; ?>
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased">

<div class="mx-auto max-w-md min-h-dvh pt-safe pb-24">

  <header class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur border-b border-slate-200">
    <div class="flex items-center justify-between h-14 px-4">
      <a href="<?= $e($baseUrl) ?>/new" class="p-2 -ml-2 text-slate-600" aria-label="Retour">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <h1 class="text-base font-semibold"><?= $e($t('settings.title')) ?></h1>
      <div class="w-6"></div>
    </div>
  </header>

  <div class="px-4 pt-5 space-y-5">

    <!-- Profil -->
    <section class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-4">
      <div class="h-12 w-12 rounded-full bg-indigo-600 text-white flex items-center justify-center text-lg font-semibold">
        <?= $e(strtoupper(substr($username ?? 'U', 0, 2))) ?>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-semibold flex items-center gap-2">
          <?= $e($username ?? '—') ?>
          <?php if ($isAdmin): ?><span class="text-[10px] uppercase tracking-wider bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full"><?= $e($t('settings.users_admin_badge')) ?></span><?php endif; ?>
        </div>
        <div class="text-xs text-slate-500"><?= $e($t('login.username')) ?></div>
      </div>
    </section>

    <?php if ($isAdmin): ?>
    <!-- Sync desktop (admin) -->
    <section>
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.sync_section')) ?></h2>
      <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">

        <div class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">✓</span>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium"><?= $e($t('settings.last_sync')) ?></div>
            <div class="text-xs text-slate-500"><?= $lastSyncAt ? $e(date('d M Y, H:i', strtotime($lastSyncAt))) : $e($t('queue.never')) ?></div>
          </div>
          <div class="text-right">
            <div class="text-xs text-slate-400"><?= $e($t('settings.in_queue')) ?></div>
            <div class="text-sm font-semibold text-rose-600 tabular-nums"><?= (int) $pendingCount ?></div>
          </div>
        </div>

        <div class="px-4 py-3" x-data="{copied:false}">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.guid')) ?></span>
          </div>
          <div class="flex items-center gap-2">
            <code class="flex-1 text-xs font-mono truncate bg-slate-50 rounded-lg px-3 py-2"><?= $e($desktopGuid) ?></code>
            <button type="button"
                    @click="navigator.clipboard.writeText('<?= $e($desktopGuid) ?>'); copied=true; setTimeout(()=>copied=false,1500)"
                    class="h-9 px-3 rounded-lg bg-slate-100 text-xs font-medium">
              <span x-show="!copied"><?= $e($t('settings.copy')) ?></span><span x-show="copied"><?= $e($t('settings.copied')) ?> ✓</span>
            </button>
          </div>
        </div>

        <div class="px-4 py-3">
          <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-1"><?= $e($t('settings.services_url')) ?></div>
          <code class="block text-xs font-mono break-all bg-slate-50 rounded-lg px-3 py-2"><?= $e($servicesUrl) ?></code>
          <p class="mt-2 text-[11px] text-slate-500"><?= $e($t('settings.services_help')) ?></p>
        </div>

        <details class="px-4 py-3">
          <summary class="list-none flex items-center gap-2 cursor-pointer text-sm text-slate-600 hover:text-slate-900">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            <?= $e($t('settings.regen_guid')) ?>
          </summary>
          <form method="post" action="<?= $e($baseUrl) ?>/settings/guid" class="mt-3"
                @submit="if (!confirm('<?= $e($t('settings.regen_confirm')) ?>')) $event.preventDefault();">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <button type="submit" class="h-10 px-4 rounded-lg bg-rose-50 text-rose-700 text-sm font-medium"><?= $e($t('settings.regen_button')) ?></button>
          </form>
        </details>
      </div>
    </section>
    <?php endif; ?>

    <!-- Préférences -->
    <section>
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.pref_section')) ?></h2>
      <form method="post" action="<?= $e($baseUrl) ?>/settings/preferences" class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <?php if ($prefsStatus === 'ok'): ?>
          <div class="px-4 py-3 bg-emerald-50 text-emerald-700 text-sm"><?= $e($t('settings.prefs_saved')) ?></div>
        <?php endif; ?>

        <!-- Langue : disponible pour tous -->
        <label class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🌐</span>
          <span class="flex-1 text-sm font-medium"><?= $e($t('settings.language')) ?></span>
          <select name="Language" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-sm">
            <?php foreach ($locales as $code => $label): ?>
              <option value="<?= $e($code) ?>" <?= ($params['Language'] ?? 'fr') === $code ? 'selected' : '' ?>><?= $e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <?php if ($isAdmin): ?>
        <!-- Ces préférences sont globales — admin uniquement -->
        <label class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏷️</span>
          <span class="flex-1 text-sm font-medium"><?= $e($t('settings.default_status')) ?></span>
          <select name="DefaultStatus" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-sm">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $e($s) ?>" <?= ($params['DefaultStatus'] ?? 'N') === $s ? 'selected' : '' ?>>
                <?= $e($t("settings.status_$s")) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="px-4 py-3 flex items-start gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🧾</span>
          <div class="flex-1">
            <label class="flex items-center justify-between gap-3">
              <span class="text-sm font-medium"><?= $e($t('settings.disable_payee')) ?></span>
              <input type="checkbox" name="DisablePayee" value="1" <?= ($params['DisablePayee'] ?? 'False') === 'True' ? 'checked' : '' ?>
                     class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            </label>
            <p class="text-xs text-slate-500 mt-1"><?= $e($t('settings.disable_payee_help')) ?></p>
          </div>
        </div>

        <div class="px-4 py-3 flex items-start gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏷️</span>
          <div class="flex-1">
            <label class="flex items-center justify-between gap-3">
              <span class="text-sm font-medium"><?= $e($t('settings.disable_category')) ?></span>
              <input type="checkbox" name="DisableCategory" value="1" <?= ($params['DisableCategory'] ?? 'False') === 'True' ? 'checked' : '' ?>
                     class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            </label>
            <p class="text-xs text-slate-500 mt-1"><?= $e($t('settings.disable_category_help')) ?></p>
          </div>
        </div>
        <?php endif; ?>

        <div class="px-4 py-3">
          <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 text-white font-semibold text-sm"><?= $e($t('settings.save_prefs')) ?></button>
        </div>
      </form>
    </section>

    <?php if ($isAdmin): ?>
    <!-- Champs personnalisés (admin) -->
    <section x-data="{enabled: <?= ($params['CustomFieldsEnabled'] ?? 'False') === 'True' ? 'true' : 'false' ?>}">
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.custom_section')) ?></h2>
      <form method="post" action="<?= $e($baseUrl) ?>/settings/preferences" class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="Language"       value="<?= $e($params['Language']       ?? 'fr') ?>">
        <input type="hidden" name="DefaultStatus"  value="<?= $e($params['DefaultStatus']  ?? 'N') ?>">
        <?php if (($params['DisablePayee'] ?? 'False') === 'True'): ?><input type="hidden" name="DisablePayee" value="1"><?php endif; ?>
        <?php if (($params['DisableCategory'] ?? 'False') === 'True'): ?><input type="hidden" name="DisableCategory" value="1"><?php endif; ?>

        <div class="px-4 py-3 flex items-start gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">👤</span>
          <div class="flex-1">
            <label class="flex items-center justify-between gap-3">
              <span class="text-sm font-medium"><?= $e($t('settings.custom_enabled')) ?></span>
              <input type="checkbox" name="CustomFieldsEnabled" value="1" x-model="enabled"
                     class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            </label>
            <p class="text-xs text-slate-500 mt-1"><?= $e($t('settings.custom_help')) ?></p>
          </div>
        </div>

        <div x-show="enabled" class="px-4 py-3 space-y-3">
          <label class="block">
            <span class="text-xs text-slate-600 mb-1 block"><?= $e($t('settings.custom_label')) ?></span>
            <input type="text" name="CustomFieldsLabel" value="<?= $e($params['CustomFieldsLabel'] ?? 'Par') ?>"
                   class="w-full h-11 px-3 rounded-xl border border-slate-200 text-sm">
          </label>
          <label class="block">
            <span class="text-xs text-slate-600 mb-1 block"><?= $e($t('settings.custom_name')) ?></span>
            <input type="text" name="CustomFieldsName" value="<?= $e($params['CustomFieldsName'] ?? '') ?>"
                   placeholder="<?= $e($username ?? '') ?>"
                   class="w-full h-11 px-3 rounded-xl border border-slate-200 text-sm">
          </label>
        </div>

        <div class="px-4 py-3">
          <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 text-white font-semibold text-sm"><?= $e($t('settings.save_prefs')) ?></button>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <!-- Données locales -->
    <section>
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.data_section')) ?></h2>
      <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <div class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏦</span>
          <div class="flex-1"><div class="text-sm font-medium"><?= $e($t('settings.accounts')) ?></div><div class="text-xs text-slate-500"><?= (int) $accountCount ?></div></div>
        </div>
        <div class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏷️</span>
          <div class="flex-1"><div class="text-sm font-medium"><?= $e($t('settings.categories')) ?></div><div class="text-xs text-slate-500"><?= (int) $categoryCount ?></div></div>
        </div>
        <div class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🧾</span>
          <div class="flex-1"><div class="text-sm font-medium"><?= $e($t('settings.payees')) ?></div><div class="text-xs text-slate-500"><?= (int) $payeeCount ?></div></div>
        </div>
        <?php if ($isAdmin): ?>
        <div class="px-4 py-3 flex items-center gap-3">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">💾</span>
          <div class="flex-1 min-w-0"><div class="text-sm font-medium"><?= $e($t('settings.db_file')) ?></div><div class="text-xs text-slate-500 font-mono truncate"><?= $e($dbPath) ?></div></div>
        </div>
        <?php endif; ?>
      </div>
      <p class="mt-2 px-1 text-[11px] text-slate-400"><?= $e($t('settings.data_note')) ?></p>
    </section>

    <?php if ($isAdmin): ?>
    <!-- Utilisateurs -->
    <section x-data="{resetFor:null}">
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.users_section')) ?></h2>
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <?php if ($userStatus):
            $userMsg = match ($userStatus) {
                'deleted'   => ['emerald', $t('settings.users_deleted')],
                'pwdreset'  => ['emerald', $t('settings.users_pwdreset')],
                'self'      => ['rose',    $t('settings.users_self')],
                'last'      => ['rose',    $t('settings.users_last')],
                'lastadmin' => ['rose',    $t('settings.users_lastadmin')],
                'short'     => ['rose',    $t('settings.password_short')],
                default     => null,
            };
        ?>
          <?php if ($userMsg): ?>
            <div class="px-4 py-2.5 bg-<?= $userMsg[0] ?>-50 text-<?= $userMsg[0] ?>-700 text-xs"><?= $e($userMsg[1]) ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <ul class="divide-y divide-slate-100">
          <?php foreach ($users as $u): $isSelf = (int) $u['id'] === (int) $userId; $userIsAdmin = (int) $u['is_admin'] === 1; ?>
            <li class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full <?= $userIsAdmin ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' ?> flex items-center justify-center text-sm font-semibold"><?= $e(strtoupper(substr($u['username'], 0, 2))) ?></div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium truncate flex items-center gap-2 flex-wrap">
                    <?= $e($u['username']) ?>
                    <?php if ($userIsAdmin): ?><span class="text-[10px] uppercase tracking-wider bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full"><?= $e($t('settings.users_admin_badge')) ?></span><?php endif; ?>
                    <?php if ($isSelf): ?><span class="text-xs text-slate-400"><?= $e($t('settings.users_you')) ?></span><?php endif; ?>
                  </div>
                  <div class="text-[11px] text-slate-500"><?= $e(date('d M Y', strtotime($u['created_at']))) ?></div>
                </div>
                <button type="button" @click="resetFor = <?= (int) $u['id'] ?>" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" :aria-label="'<?= $e($t('settings.users_reset_pwd')) ?>'">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09A1.65 1.65 0 0015 4.6a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                </button>
                <?php if (!$isSelf): ?>
                  <form method="post" action="<?= $e($baseUrl) ?>/settings/users/<?= (int) $u['id'] ?>/delete"
                        @submit="if (!confirm('<?= $e($t('settings.users_confirm_del')) ?>')) $event.preventDefault();">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50" :aria-label="'<?= $e($t('settings.users_delete')) ?>'">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
              <form method="post" action="<?= $e($baseUrl) ?>/settings/users/<?= (int) $u['id'] ?>/password"
                    x-show="resetFor === <?= (int) $u['id'] ?>" x-transition
                    class="mt-2 flex gap-2">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input type="password" name="new" required minlength="4" placeholder="<?= $e($t('settings.password_new')) ?>"
                       class="flex-1 h-9 px-3 rounded-lg border border-slate-200 text-sm">
                <button type="submit" class="h-9 px-3 rounded-lg bg-indigo-600 text-white text-xs font-semibold">OK</button>
                <button type="button" @click="resetFor=null" class="h-9 px-2 text-slate-500">✕</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <p class="mt-2 px-1 text-[11px] text-slate-400"><?= $e($t('settings.users_help')) ?></p>
    </section>

    <!-- Invitations -->
    <section id="invitations">
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.invitations_section')) ?></h2>
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <?php if ($inviteStatus === 'created'): ?>
          <div class="px-4 py-2.5 bg-emerald-50 text-emerald-700 text-xs"><?= $e($t('settings.invitations_created')) ?></div>
        <?php elseif ($inviteStatus === 'revoked'): ?>
          <div class="px-4 py-2.5 bg-amber-50 text-amber-700 text-xs"><?= $e($t('settings.invitations_revoked')) ?></div>
        <?php endif; ?>

        <?php if (empty($invitations)): ?>
          <div class="px-4 py-6 text-center text-sm text-slate-500"><?= $e($t('settings.invitations_none')) ?></div>
        <?php else: ?>
          <ul class="divide-y divide-slate-100">
            <?php foreach ($invitations as $inv):
              $url = $origin . $baseUrl . '/invite/' . $inv['token'];
              $used = !empty($inv['used_at']);
              $expired = !$used && strtotime($inv['expires_at'] . ' UTC') < time();
            ?>
              <li class="px-4 py-3" x-data="{copied:false}">
                <div class="flex items-center gap-2 mb-1.5">
                  <span class="h-7 w-7 rounded-lg <?= $used ? 'bg-slate-100 text-slate-500' : ($expired ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') ?> flex items-center justify-center text-xs">
                    <?= $used ? '✓' : ($expired ? '⏳' : '✉') ?>
                  </span>
                  <div class="flex-1 min-w-0 text-xs text-slate-600">
                    <?php if ($used): ?>
                      <?= $e($t('settings.invitations_used')) ?> <strong><?= $e($inv['used_by_name'] ?? '—') ?></strong> · <?= $e(date('d M', strtotime($inv['used_at']))) ?>
                    <?php elseif ($expired): ?>
                      expiré le <?= $e(date('d M', strtotime($inv['expires_at']))) ?>
                    <?php else: ?>
                      <?= $e($t('settings.invitations_expires')) ?> <?= $e(date('d M Y, H:i', strtotime($inv['expires_at']))) ?>
                    <?php endif; ?>
                  </div>
                  <?php if (!$used): ?>
                    <form method="post" action="<?= $e($baseUrl) ?>/settings/invitations/<?= (int) $inv['id'] ?>/revoke"
                          @submit="if (!confirm('<?= $e($t('settings.invitations_revoke_confirm')) ?>')) $event.preventDefault();">
                      <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                      <button type="submit" class="text-[11px] text-rose-500 hover:text-rose-700"><?= $e($t('settings.invitations_revoke')) ?></button>
                    </form>
                  <?php endif; ?>
                </div>
                <?php if (!$used && !$expired): ?>
                  <div class="flex items-center gap-2">
                    <code class="flex-1 text-[10px] font-mono truncate bg-slate-50 rounded px-2 py-1.5"><?= $e($url) ?></code>
                    <button type="button"
                            @click="navigator.clipboard.writeText('<?= $e($url) ?>'); copied=true; setTimeout(()=>copied=false,1500)"
                            class="h-8 px-2 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-medium">
                      <span x-show="!copied"><?= $e($t('settings.invitations_copy')) ?></span><span x-show="copied">✓</span>
                    </button>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form method="post" action="<?= $e($baseUrl) ?>/settings/invitations" class="px-4 py-3 border-t border-slate-100">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 text-white font-semibold text-sm flex items-center justify-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <?= $e($t('settings.invitations_create')) ?>
          </button>
        </form>
      </div>
      <p class="mt-2 px-1 text-[11px] text-slate-400"><?= $e($t('settings.invitations_help')) ?></p>
    </section>
    <?php endif; ?>

    <!-- Sécurité : changement de son propre mot de passe (tous) -->
    <section>
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('settings.security_section')) ?></h2>
      <details class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <summary class="px-4 py-3 flex items-center gap-3 cursor-pointer hover:bg-slate-50 list-none">
          <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🔑</span>
          <span class="flex-1 text-sm font-medium"><?= $e($t('settings.change_password')) ?></span>
          <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <form method="post" action="<?= $e($baseUrl) ?>/settings/password" class="px-4 py-3 space-y-3 border-t border-slate-100">
          <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
          <?php if ($pwdStatus === 'ok'):    ?><div class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-xs"><?= $e($t('settings.password_ok')) ?></div><?php endif; ?>
          <?php if ($pwdStatus === 'wrong'): ?><div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-xs"><?= $e($t('settings.password_wrong')) ?></div><?php endif; ?>
          <?php if ($pwdStatus === 'short'): ?><div class="px-3 py-2 rounded-lg bg-rose-50 text-rose-700 text-xs"><?= $e($t('settings.password_short')) ?></div><?php endif; ?>
          <input type="password" name="current" required placeholder="<?= $e($t('settings.password_current')) ?>" class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm">
          <input type="password" name="new"     required placeholder="<?= $e($t('settings.password_new')) ?>" minlength="4" class="w-full h-11 px-4 rounded-xl border border-slate-200 text-sm">
          <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 text-white font-semibold text-sm"><?= $e($t('settings.password_update')) ?></button>
        </form>
      </details>
    </section>

    <!-- À propos + Don -->
    <?php $paypalUrl = \App\Db::PAYPAL_URL; ?>
    <section>
      <h2 class="px-1 mb-2 text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('about.section')) ?></h2>
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <!-- Crédits (toujours visible) -->
        <div class="px-4 py-3 space-y-1.5 text-xs text-slate-600">
          <div class="flex items-center justify-between">
            <span class="font-medium text-slate-900"><?= $e($t('about.version_line')) ?> v<?= $e(\App\Db::APP_VERSION) ?></span>
            <span class="text-[10px] text-slate-400">API <?= $e(\App\Db::API_VERSION) ?> · PHP <?= PHP_VERSION ?></span>
          </div>
          <div class="leading-relaxed">
            <?= $e($t('about.credits_fork')) ?> —
            <a href="https://github.com/gab696/Money-Manager-EX---WebApp-2.0" target="_blank" rel="noopener"
               class="text-indigo-600 hover:underline">github.com/gab696/Money-Manager-EX---WebApp-2.0</a>
          </div>
          <div class="leading-relaxed">
            <?= $e($t('about.credits_webapp')) ?>
            <a href="https://github.com/moneymanagerex/web-money-manager-ex" target="_blank" rel="noopener"
               class="text-indigo-600 hover:underline">
              <?= $e($t('about.credits_webapp_repo')) ?>
            </a>.
          </div>
          <div class="leading-relaxed">
            <?= $e($t('about.credits_mmex')) ?>
            <a href="https://github.com/moneymanagerex/moneymanagerex" target="_blank" rel="noopener"
               class="text-indigo-600 hover:underline">
              <?= $e($t('about.credits_mmex_team')) ?>
            </a>.
          </div>
          <div class="text-[11px] text-slate-400 pt-1"><?= $e($t('about.license')) ?></div>
        </div>

        <?php if ($paypalUrl !== ''): ?>
          <!-- Bouton don (toujours visible en settings) -->
          <div class="px-4 py-4 border-t border-slate-100 bg-gradient-to-br from-amber-50/60 to-white">
            <p class="text-[11px] text-slate-500 mb-3 leading-relaxed"><?= $e($t('about.donation_help')) ?></p>
            <a href="<?= $e($paypalUrl) ?>" target="_blank" rel="noopener"
               class="w-full h-11 rounded-xl bg-[#0070ba] hover:bg-[#003087] text-white font-semibold text-sm flex items-center justify-center gap-2 transition">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.076 21.337H2.47a.5.5 0 0 1-.494-.577L4.806 2.85A.5.5 0 0 1 5.3 2.337h8.7c2.55 0 4.48 1.22 5.24 3.35.52 1.47.33 2.8-.28 4.01-.84 1.68-2.57 2.78-4.76 2.95-.37.03-.77.05-1.18.05H10.3c-.49 0-.9.35-.97.83l-.74 4.7-.28 1.81c-.07.41-.4.71-.82.71H7.08l-.004.59z"/></svg>
              <?= $e($t('about.donation_button')) ?>
            </a>
          </div>
        <?php endif; ?>

      </div>
    </section>

    <section class="space-y-2">
      <form method="post" action="<?= $e($baseUrl) ?>/logout">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <button type="submit" class="w-full h-12 rounded-xl bg-white border border-slate-200 text-rose-600 font-medium hover:bg-rose-50"><?= $e($t('settings.logout')) ?></button>
      </form>
    </section>
  </div>
</div>

<?php include __DIR__ . '/layout/nav.php'; ?>
</body>
</html>
