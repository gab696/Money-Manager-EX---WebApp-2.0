<?php
/** @var callable $e */
/** @var callable $t */
/** @var string $baseUrl */
/** @var string $csrf */
/** @var string $locale */
/** @var array $pending */
/** @var array $totals */
/** @var ?string $lastSyncAt */

$active = 'queue';
$pendingCount = (int) ($totals['count'] ?? 0);
$intlLocale = $locale === 'fr' ? 'fr_CH' : 'en_GB';

if (!function_exists('mmex_fmt_amount')) {
    function mmex_fmt_amount(float $n): string {
        return number_format($n, 2, ',', "\u{202F}");
    }
}

$mmexDayLabel = function (string $iso) use ($t, $intlLocale, $locale): string {
    $today = date('Y-m-d');
    $yday  = date('Y-m-d', strtotime('-1 day'));
    if ($iso === $today) return $t('tx.today');
    if ($iso === $yday)  return $t('tx.yesterday');
    $ts = strtotime($iso);
    if (class_exists('IntlDateFormatter')) {
        $fmt = \IntlDateFormatter::create($intlLocale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
        if ($fmt) return $fmt->format($ts);
    }
    // Fallback : formats courts
    if ($locale === 'en') {
        return date('D j M', $ts);
    }
    static $days   = ['Dim.','Lun.','Mar.','Mer.','Jeu.','Ven.','Sam.'];
    static $months = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
    return $days[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
};

$typeMeta = [
    'Withdrawal' => ['label' => $t('tx.withdrawal'), 'sign' => '−', 'color' => 'text-rose-600',    'bg' => 'bg-rose-50 text-rose-600',       'icon' => '💳'],
    'Deposit'    => ['label' => $t('tx.deposit'),    'sign' => '+', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50 text-emerald-600', 'icon' => '💰'],
    'Transfer'   => ['label' => $t('tx.transfer'),   'sign' => '→', 'color' => 'text-sky-600',     'bg' => 'bg-sky-50 text-sky-600',         'icon' => '🔁'],
];

$groups = [];
foreach ($pending as $tx) $groups[$tx['Date']][] = $tx;
krsort($groups);
?>
<!doctype html>
<html lang="<?= $e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,interactive-widget=resizes-content">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($t('queue.title')) ?> — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="<?= $e($asset('/assets/style.css')) ?>">
  <?php include __DIR__ . '/layout/pwa_head.php'; ?>
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased">

<div class="mx-auto max-w-md min-h-dvh pt-safe pb-36">

  <header class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur border-b border-slate-200">
    <div class="flex items-center justify-between h-14 px-4">
      <a href="<?= $e($baseUrl) ?>/new" class="p-2 -ml-2 text-slate-600" aria-label="<?= $e($t('common.back')) ?>">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <h1 class="text-base font-semibold"><?= $e($t('queue.title')) ?></h1>
      <div class="w-6"></div>
    </div>
    <div class="px-4 pb-3">
      <div class="flex items-center gap-2 text-xs text-slate-500">
        <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="4"/></svg>
        <span><?= $e($t('queue.last_sync')) ?>
          <span class="font-medium text-slate-700"><?= $lastSyncAt ? $e(date('d M, H:i', strtotime($lastSyncAt))) : $e($t('queue.never')) ?></span>
        </span>
      </div>
      <div class="mt-1 text-[11px] text-slate-400"><?= $e($t('queue.explanation')) ?></div>
    </div>
  </header>

  <section class="px-4 pt-4">
    <div class="grid grid-cols-3 gap-2">
      <div class="rounded-xl bg-white border border-slate-200 p-3">
        <div class="text-[11px] uppercase tracking-wider text-slate-400"><?= $e($t('queue.total')) ?></div>
        <div class="text-lg font-semibold tabular-nums"><?= (int) $totals['count'] ?></div>
      </div>
      <div class="rounded-xl bg-rose-50 border border-rose-100 p-3">
        <div class="text-[11px] uppercase tracking-wider text-rose-500"><?= $e($t('queue.expenses')) ?></div>
        <div class="text-lg font-semibold tabular-nums text-rose-700"><?= $e(mmex_fmt_amount((float) $totals['Withdrawal'])) ?></div>
      </div>
      <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
        <div class="text-[11px] uppercase tracking-wider text-emerald-600"><?= $e($t('queue.incomes')) ?></div>
        <div class="text-lg font-semibold tabular-nums text-emerald-700"><?= $e(mmex_fmt_amount((float) $totals['Deposit'])) ?></div>
      </div>
    </div>
  </section>

  <section class="px-4 mt-5 space-y-5" x-data="{actionTx:null}">

    <?php if (!$pending): ?>
      <div class="text-center py-16 px-6">
        <svg class="h-16 w-16 mx-auto text-slate-300 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z"/>
        </svg>
        <h3 class="text-lg font-semibold"><?= $e($t('queue.empty_title')) ?></h3>
        <p class="mt-1 text-sm text-slate-500"><?= $e($t('queue.empty_text')) ?></p>
        <a href="<?= $e($baseUrl) ?>/new" class="mt-6 inline-flex items-center gap-2 px-5 h-11 rounded-xl bg-indigo-600 text-white text-sm font-semibold"><?= $e($t('queue.empty_cta')) ?></a>
      </div>
    <?php else: foreach ($groups as $date => $items): ?>
      <div>
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-[11px] uppercase tracking-wider text-slate-400"><?= $e($mmexDayLabel($date)) ?></h2>
          <span class="text-[11px] text-slate-400"><?= count($items) ?> <?= $e($t('queue.tx_abbrev')) ?></span>
        </div>
        <ul class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
          <?php foreach ($items as $tx):
            $meta = $typeMeta[$tx['Type']] ?? $typeMeta['Withdrawal'];
            $catLabel = $tx['Category'] && $tx['Category'] !== 'None'
                ? ($tx['Category'] . ($tx['SubCategory'] && $tx['SubCategory'] !== 'None' ? ' › ' . $tx['SubCategory'] : ''))
                : '';
            if ($tx['Type'] === 'Transfer') {
              $primary   = $tx['Account'] . ' → ' . ($tx['ToAccount'] ?: '—');
              $secondary = $tx['Notes'] ?: $t('queue.internal_transfer');
            } else {
              $primary   = $tx['Payee'] ?: ($catLabel ?: '—');
              $secondary = trim(($catLabel ? $catLabel : '—') . ' · ' . $tx['Account']);
            }
            $actionPayload = json_encode([
                'id' => (int) $tx['ID'],
                'primary' => $primary,
                'secondary' => $secondary,
                'amount' => mmex_fmt_amount((float) $tx['Amount']),
                'sign' => $meta['sign'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'iconBg' => $meta['bg'],
                'date' => $mmexDayLabel($tx['Date']),
            ], JSON_UNESCAPED_UNICODE);
          ?>
          <li>
            <button type="button" @click='actionTx = <?= $e($actionPayload) ?>'
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 text-left">
              <span class="h-10 w-10 rounded-full flex items-center justify-center text-base <?= $meta['bg'] ?>"><?= $meta['icon'] ?></span>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium truncate"><?= $e($primary) ?></div>
                <div class="text-xs text-slate-500 truncate"><?= $e($secondary) ?></div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold tabular-nums <?= $meta['color'] ?>"><?= $meta['sign'] ?><?= $e(mmex_fmt_amount((float) $tx['Amount'])) ?></div>
                <div class="text-[10px] uppercase tracking-wider text-slate-400 mt-0.5"><?= $e($meta['label']) ?></div>
              </div>
            </button>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; endif; ?>

    <div x-show="actionTx" x-transition.opacity class="fixed inset-0 bg-slate-900/40 z-40" @click="actionTx = null"></div>
    <section x-show="actionTx" x-transition.duration.200ms
             class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl pb-safe">
      <div class="mx-auto max-w-md p-5">
        <div class="h-1 w-10 bg-slate-200 rounded mx-auto mb-4"></div>
        <template x-if="actionTx">
          <div>
            <div class="flex items-center gap-3 mb-4">
              <span class="h-10 w-10 rounded-full flex items-center justify-center text-base" :class="actionTx.iconBg" x-text="actionTx.icon"></span>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium truncate" x-text="actionTx.primary"></div>
                <div class="text-xs text-slate-500" x-text="actionTx.secondary + ' · ' + actionTx.date"></div>
              </div>
              <div class="text-sm font-semibold tabular-nums" :class="actionTx.color">
                <span x-text="actionTx.sign"></span><span x-text="actionTx.amount"></span>
              </div>
            </div>
            <div class="grid grid-cols-1 gap-2">
              <a :href="'<?= $e($baseUrl) ?>/transaction/' + actionTx.id + '/edit'"
                 class="h-12 rounded-xl bg-indigo-600 text-white font-semibold flex items-center justify-center gap-2">
                <?= $e($t('common.edit')) ?>
              </a>
              <form method="post" :action="'<?= $e($baseUrl) ?>/transaction/' + actionTx.id + '/delete'"
                    @submit="if (!confirm('<?= $e($t('queue.confirm_delete')) ?>')) $event.preventDefault();">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button type="submit" class="w-full h-12 rounded-xl bg-rose-50 text-rose-700 font-medium hover:bg-rose-100"><?= $e($t('common.delete')) ?></button>
              </form>
              <button @click="actionTx = null" class="h-12 rounded-xl text-slate-600 font-medium hover:bg-slate-50"><?= $e($t('common.cancel')) ?></button>
            </div>
          </div>
        </template>
      </div>
    </section>

  </section>

  <a href="<?= $e($baseUrl) ?>/new"
     class="fixed bottom-24 right-5 z-30 h-14 w-14 rounded-full bg-indigo-600 text-white shadow-lg shadow-indigo-500/30 flex items-center justify-center">
    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
  </a>
</div>

<?php include __DIR__ . '/layout/nav.php'; ?>
</body>
</html>
