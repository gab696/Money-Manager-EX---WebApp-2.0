<?php
/** @var callable $e */
/** @var string $baseUrl */
/** @var string $csrf */
/** @var array $accounts */         // string[]
/** @var array $categories */       // [{CategoryName, SubCategoryName}, …]
/** @var array $payees */           // [{PayeeName, DefCateg, DefSubCateg}, …]
/** @var array $frequentAccounts */ // string[]
/** @var array $frequentPayees */   // string[]
/** @var array $frequentCategories */ // [{Category, SubCategory}, …]
/** @var string $defaultAccount */
/** @var string $defaultStatus */
/** @var bool $disablePayee */
/** @var bool $disableCategory */
/** @var int $pendingCount */
/** @var ?array $edit */

/** @var array $jsStrings */
/** @var string $jsLocale */
/** @var string $paypalUrl */
/** @var bool $donorHidden */

$bootData = [
    'accounts'           => array_values($accounts),
    'categories'         => array_values($categories),
    'payees'             => array_values($payees),
    'frequentAccounts'   => array_values($frequentAccounts),
    'frequentPayees'     => array_values($frequentPayees),
    'frequentCategories' => array_values($frequentCategories),
    'defaultAccount'     => $defaultAccount,
    'defaultStatus'      => $defaultStatus,
    'disablePayee'       => $disablePayee,
    'disableCategory'    => $disableCategory,
    'baseUrl'            => $baseUrl,
    'csrf'               => $csrf,
    'edit'               => $edit,
    'strings'            => $jsStrings ?? [],
    'locale'             => $jsLocale ?? 'fr-CH',
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#4f46e5">
  <title><?= $e($edit ? $t('tx.edit') : $t('tx.new') . ' ' . $t('tx.withdrawal')) ?> — MMEX Web</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="<?= $e($asset('/assets/style.css')) ?>">
  <script>window.MMEX_BOOT = <?= json_encode($bootData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="<?= $e($asset('/assets/app.js')) ?>"></script>
  <?php include __DIR__ . '/layout/pwa_head.php'; ?>
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased">

<div x-data="txForm()" x-init="init()" class="mx-auto max-w-md min-h-dvh flex flex-col pt-safe pb-32">

  <!-- Header : retour + titre dynamique + switcher type -->
  <header class="sticky top-0 z-20 bg-slate-50/95 backdrop-blur border-b border-slate-200">
    <div class="flex items-center justify-between h-14 px-4">
      <button type="button" @click="goBack()" class="p-2 -ml-2 text-slate-600" :aria-label="step === 1 ? 'Quitter' : 'Précédent'">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      </button>
      <h1 class="text-base font-semibold">
        <span x-text="(edit ? boot.strings.edit + ' ' : boot.strings.new + ' ') + typeMeta[type].label.toLowerCase()"></span>
      </h1>
      <div class="text-[11px] text-slate-400 tabular-nums" x-text="step + '/' + stepCount()"></div>
    </div>

    <div class="px-4 pb-3">
      <div class="grid grid-cols-3 bg-slate-200/60 rounded-xl p-1 text-sm font-medium" x-show="step === 1">
        <template x-for="t in ['Withdrawal','Deposit','Transfer']" :key="t">
          <button type="button" @click="setType(t)"
                  :class="type === t ? activeTypeClasses() : 'text-slate-600'"
                  class="relative z-10 py-2 rounded-lg transition"
                  x-text="typeMeta[t].label"></button>
        </template>
      </div>

      <!-- Barre de progression -->
      <div class="flex gap-1 mt-3" x-show="step > 1 || true">
        <template x-for="i in stepCount()" :key="i">
          <div class="h-1 flex-1 rounded-full" :class="i <= step ? typeMeta[type].chip : 'bg-slate-200'"></div>
        </template>
      </div>
    </div>
  </header>

  <!-- ================= STEP 1 : Montant ================= -->
  <section x-show="step === 1" x-transition.opacity class="flex-1 flex flex-col">
    <div class="flex-1 flex items-center justify-center px-4 py-4 text-center">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-400 mb-2">
          <span x-text="typeMeta[type].label"></span>
        </div>
        <div class="tabular-nums text-6xl font-semibold leading-none" :class="typeMeta[type].color">
          <span x-text="typeMeta[type].sign"></span>
          <span x-text="amountDisplay()"></span>
        </div>
      </div>
    </div>

    <div class="px-3 pt-2 pb-4">
      <div class="grid grid-cols-4 gap-2">
        <button type="button" @click="press('7')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">7</button>
        <button type="button" @click="press('8')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">8</button>
        <button type="button" @click="press('9')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">9</button>
        <button type="button" @click="press('⌫')" class="keypad-key h-14 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center" :aria-label="boot.strings.delete || 'Delete'">
          <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l-7-7 7-7h11a2 2 0 012 2v10a2 2 0 01-2 2H12zM15 9l-6 6m0-6l6 6"/></svg>
        </button>
        <button type="button" @click="press('4')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">4</button>
        <button type="button" @click="press('5')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">5</button>
        <button type="button" @click="press('6')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">6</button>
        <button type="button" @click="press('.')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">,</button>
        <button type="button" @click="press('1')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">1</button>
        <button type="button" @click="press('2')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">2</button>
        <button type="button" @click="press('3')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums">3</button>
        <button type="button" @click="press('00')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-base font-medium tabular-nums">00</button>
        <button type="button" @click="press('0')" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-xl font-medium tabular-nums col-span-2">0</button>
        <button type="button" @click="amountRaw='0'" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-slate-500 text-xs font-medium">CE</button>
        <button type="button" @click="pasteQuick()" class="keypad-key h-14 rounded-xl bg-white border border-slate-200 text-slate-500 text-xs font-medium">↩</button>
      </div>
    </div>
  </section>

  <!-- ================= STEP 2 : Date + Compte(s) ================= -->
  <section x-show="step === 2" x-transition.opacity class="flex-1 px-4 pt-6 space-y-2">

    <button type="button" @click="openSheet('date')"
            class="w-full flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-slate-200">
      <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">📅</span>
      <span class="flex-1 text-left">
        <span class="block text-[11px] uppercase text-slate-400 tracking-wider"><?= $e($t('tx.date')) ?></span>
        <span class="block text-sm font-medium" x-text="dateLabel()"></span>
      </span>
      <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    <button type="button" @click="openSheet('account')"
            class="w-full flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-slate-200">
      <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏦</span>
      <span class="flex-1 text-left">
        <span class="block text-[11px] uppercase text-slate-400 tracking-wider" x-text="type === 'Transfer' ? '<?= $e($t('tx.account_from')) ?>' : '<?= $e($t('tx.account')) ?>'"></span>
        <span class="block text-sm font-medium" :class="account ? '' : 'text-slate-400'" x-text="account || '<?= $e($t('tx.choose')) ?>'"></span>
      </span>
      <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    <template x-if="type === 'Transfer'">
      <button type="button" @click="openSheet('toAccount')"
              class="w-full flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-slate-200">
        <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏦</span>
        <span class="flex-1 text-left">
          <span class="block text-[11px] uppercase text-slate-400 tracking-wider"><?= $e($t('tx.account_to')) ?></span>
          <span class="block text-sm font-medium" :class="toAccount ? '' : 'text-slate-400'" x-text="toAccount || '<?= $e($t('tx.choose')) ?>'"></span>
        </span>
        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </button>
    </template>
  </section>

  <!-- ================= STEP 3 : Bénéficiaire + Catégorie (Withdrawal/Deposit, si pas tout désactivé) ================= -->
  <section x-show="step === 3 && stepCount() === 4" x-transition.opacity class="flex-1 px-4 pt-6 space-y-2">

    <?php if (!$disablePayee): ?>
    <button type="button" @click="openSheet('payee')"
            class="w-full flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-slate-200">
      <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🧾</span>
      <span class="flex-1 text-left">
        <span class="block text-[11px] uppercase text-slate-400 tracking-wider"><?= $e($t('tx.payee')) ?></span>
        <span class="block text-sm font-medium" :class="payee ? '' : 'text-slate-400'" x-text="payee?.PayeeName ?? '<?= $e($t('tx.optional')) ?>'"></span>
      </span>
      <template x-if="payee"><button @click.stop="clearPayee()" class="p-1 text-slate-400 hover:text-rose-500" :aria-label="boot.strings.delete || 'Delete'">✕</button></template>
      <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>
    <?php endif; ?>

    <?php if (!$disableCategory): ?>
    <button type="button" @click="openSheet('category')"
            class="w-full flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-slate-200">
      <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏷️</span>
      <span class="flex-1 text-left">
        <span class="block text-[11px] uppercase text-slate-400 tracking-wider"><?= $e($t('tx.category')) ?></span>
        <span class="block text-sm font-medium" :class="category ? '' : 'text-slate-400'" x-text="category ? categoryLabel(category) : '<?= $e($t('tx.choose')) ?>'"></span>
      </span>
      <template x-if="category"><button @click.stop="clearCategory()" class="p-1 text-slate-400 hover:text-rose-500" :aria-label="boot.strings.delete || 'Delete'">✕</button></template>
      <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>
    <?php endif; ?>
  </section>

  <!-- ================= STEP FINAL : Notes + Recap + Enregistrer ================= -->
  <section x-show="step === stepCount()" x-transition.opacity class="flex-1 px-4 pt-6 pb-4 space-y-4">

    <!-- Recap -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold"><?= $e($t('tx.recap')) ?></h3>
        <span class="text-[11px] uppercase tracking-wider" :class="typeMeta[type].color" x-text="typeMeta[type].label"></span>
      </div>

      <div class="px-4 py-4 text-center border-b border-slate-100">
        <div class="text-xs uppercase tracking-wider text-slate-400 mb-1"><?= $e($t('tx.amount', 'Montant')) ?></div>
        <div class="tabular-nums text-3xl font-semibold" :class="typeMeta[type].color">
          <span x-text="typeMeta[type].sign"></span><span x-text="amountDisplay()"></span>
        </div>
      </div>

      <dl class="divide-y divide-slate-100">
        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
          <dt class="text-slate-500"><?= $e($t('tx.date')) ?></dt>
          <dd class="font-medium" x-text="dateLabel()"></dd>
        </div>
        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
          <dt class="text-slate-500" x-text="type === 'Transfer' ? '<?= $e($t('tx.account_from')) ?>' : '<?= $e($t('tx.account')) ?>'"></dt>
          <dd class="font-medium truncate ml-2" x-text="account || '—'"></dd>
        </div>
        <template x-if="type === 'Transfer'">
          <div class="px-4 py-2.5 flex items-center justify-between text-sm">
            <dt class="text-slate-500"><?= $e($t('tx.account_to')) ?></dt>
            <dd class="font-medium truncate ml-2" x-text="toAccount || '—'"></dd>
          </div>
        </template>
        <template x-if="type !== 'Transfer'">
          <div class="px-4 py-2.5 flex items-center justify-between text-sm">
            <dt class="text-slate-500"><?= $e($t('tx.payee')) ?></dt>
            <dd class="font-medium truncate ml-2" x-text="payee?.PayeeName || '—'"></dd>
          </div>
        </template>
        <template x-if="type !== 'Transfer'">
          <div class="px-4 py-2.5 flex items-center justify-between text-sm">
            <dt class="text-slate-500"><?= $e($t('tx.category')) ?></dt>
            <dd class="font-medium truncate ml-2" x-text="category ? categoryLabel(category) : '—'"></dd>
          </div>
        </template>
      </dl>
    </div>

    <!-- Notes -->
    <label class="block">
      <span class="block text-[11px] uppercase text-slate-400 tracking-wider mb-1.5"><?= $e($t('tx.notes_optional')) ?></span>
      <textarea x-model="notes" rows="3" placeholder="<?= $e($t('tx.notes_placeholder')) ?>"
                class="w-full p-3 rounded-xl border border-slate-200 text-base bg-white resize-none"></textarea>
    </label>
  </section>

  <!-- Tip jar discret : visible dans toutes les étapes si PayPal configuré et non masqué -->
  <?php if ($paypalUrl !== '' && !$donorHidden): ?>
    <div class="px-4 pb-2 flex justify-center">
      <a href="<?= $e($paypalUrl) ?>" target="_blank" rel="noopener"
         class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-slate-200 text-[11px] text-slate-500 hover:text-[#0070ba] hover:border-[#0070ba]/30 transition">
        <svg class="h-3.5 w-3.5 text-[#0070ba]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.076 21.337H2.47a.5.5 0 0 1-.494-.577L4.806 2.85A.5.5 0 0 1 5.3 2.337h8.7c2.55 0 4.48 1.22 5.24 3.35.52 1.47.33 2.8-.28 4.01-.84 1.68-2.57 2.78-4.76 2.95-.37.03-.77.05-1.18.05H10.3c-.49 0-.9.35-.97.83l-.74 4.7-.28 1.81c-.07.41-.4.71-.82.71H7.08z"/></svg>
        <span><?= $e($t('about.tip_jar')) ?></span>
      </a>
    </div>
  <?php endif; ?>

  <!-- ================= Footer : navigation step (fixed pour éviter un bug de sticky + flex) ================= -->
  <footer class="fixed inset-x-0 bottom-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 pb-safe">
    <div class="mx-auto max-w-md px-4 py-3 grid grid-cols-[auto_1fr] gap-2">
      <button type="button" @click="goBack()"
              class="h-12 px-5 rounded-xl border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50">
        <span x-text="step === 1 ? boot.strings.cancel : boot.strings.prev"></span>
      </button>
      <button type="button" @click="step < stepCount() ? goNext() : save()"
              :disabled="!canGoNext() || saving"
              :class="typeMeta[type].chip"
              class="h-12 rounded-xl text-white font-semibold text-base shadow-sm active:opacity-90 transition disabled:opacity-40">
        <span x-show="step < stepCount()" x-text="boot.strings.next"></span>
        <span x-show="step === stepCount()" x-text="saving ? boot.strings.saving : (edit ? boot.strings.update : boot.strings.save)"></span>
      </button>
    </div>
  </footer>

  <!-- =========== BOTTOM SHEETS =========== -->
  <div x-show="sheet" x-transition.opacity class="fixed inset-0 bg-slate-900/40 z-40" @click="closeSheet()"></div>

  <!-- Date -->
  <section x-show="sheet === 'date'" x-transition.duration.200ms
           class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl pb-safe">
    <div class="mx-auto max-w-md p-5">
      <div class="h-1 w-10 bg-slate-200 rounded mx-auto mb-4"></div>
      <h3 class="text-lg font-semibold mb-4"><?= $e($t('tx.date')) ?></h3>
      <input type="date" x-model="date" class="w-full h-12 px-4 rounded-xl border border-slate-200 text-base">
      <div class="mt-3 flex gap-2">
        <button @click="setDate(0)"  class="flex-1 h-10 rounded-lg bg-slate-100 text-sm"><?= $e($t('tx.today')) ?></button>
        <button @click="setDate(-1)" class="flex-1 h-10 rounded-lg bg-slate-100 text-sm"><?= $e($t('tx.yesterday')) ?></button>
        <button @click="setDate(-2)" class="flex-1 h-10 rounded-lg bg-slate-100 text-sm"><?= $e($t('tx.yesterday')) ?> -1</button>
      </div>
      <button @click="closeSheet()" class="mt-5 w-full h-12 rounded-xl bg-indigo-600 text-white font-semibold"><?= $e($t('common.validate')) ?></button>
    </div>
  </section>

  <!-- Account / ToAccount -->
  <template x-for="field in ['account','toAccount']" :key="field">
    <section x-show="sheet === field" x-transition.duration.200ms
             class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[85vh] overflow-hidden pb-safe flex flex-col">
      <div class="mx-auto w-full max-w-md p-5 pb-2">
        <div class="h-1 w-10 bg-slate-200 rounded mx-auto mb-4"></div>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold" x-text="field === 'account' ? (type === 'Transfer' ? '<?= $e($t('tx.source_account')) ?>' : '<?= $e($t('tx.account')) ?>') : '<?= $e($t('tx.dest_account')) ?>'"></h3>
          <button @click="closeSheet()" class="p-2 text-slate-500">✕</button>
        </div>
        <input type="text" x-model="search" placeholder="<?= $e($t('tx.search_account')) ?>"
               class="w-full h-11 px-4 rounded-xl border border-slate-200 text-base bg-slate-50">
      </div>
      <div class="mx-auto w-full max-w-md flex-1 overflow-y-auto px-5 pb-5 no-scrollbar">
        <template x-if="search.length === 0 && frequent.accounts.length > 0">
          <div>
            <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-2 mb-1"><?= $e($t('common.frequent_m')) ?></div>
            <template x-for="a in frequent.accounts" :key="'fa'+a">
              <button @click="pickAccount(field, a)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
                <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏦</span>
                <span class="flex-1 text-sm font-medium" x-text="a"></span>
              </button>
            </template>
          </div>
        </template>
        <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-3 mb-1"><?= $e($t('common.all_m')) ?></div>
        <template x-for="a in filteredAccounts()" :key="'a'+a">
          <button @click="pickAccount(field, a)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
            <span class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg">🏦</span>
            <span class="flex-1 text-sm font-medium" x-text="a"></span>
          </button>
        </template>
        <template x-if="search.length > 0 && !hasExactAccount()">
          <button @click="createAccount(field)" class="w-full mt-2 px-3 py-3 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-medium">
            ＋ Créer « <span x-text="search"></span> »
          </button>
        </template>
      </div>
    </section>
  </template>

  <!-- Category -->
  <section x-show="sheet === 'category'" x-transition.duration.200ms
           class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[85vh] overflow-hidden pb-safe flex flex-col">
    <div class="mx-auto w-full max-w-md p-5 pb-2">
      <div class="h-1 w-10 bg-slate-200 rounded mx-auto mb-4"></div>
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold"><?= $e($t('tx.category')) ?></h3>
        <button @click="closeSheet()" class="p-2 text-slate-500">✕</button>
      </div>
      <input type="text" x-model="search" placeholder="<?= $e($t('tx.search_or_create')) ?>"
             class="w-full h-11 px-4 rounded-xl border border-slate-200 text-base bg-slate-50">
    </div>
    <div class="mx-auto w-full max-w-md flex-1 overflow-y-auto px-5 pb-5 no-scrollbar">

      <!-- Aucune (pour effacer la sélection courante) -->
      <template x-if="category && search.length === 0">
        <button @click="clearCategory(); closeSheet()" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl bg-rose-50 text-rose-700 text-left">
          <span class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center text-xs">✕</span>
          <span class="flex-1 text-sm font-medium"><?= $e($t('tx.none_category')) ?></span>
        </button>
      </template>

      <template x-if="search.length === 0 && frequent.categories.length > 0">
        <div>
          <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-3 mb-1"><?= $e($t('common.frequent_f')) ?></div>
          <template x-for="c in frequent.categories" :key="'fc'+c.Category+'/'+c.SubCategory">
            <button @click="pickCategory(c.Category, c.SubCategory)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
              <span class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-semibold">●</span>
              <span class="flex-1 text-sm" x-text="(c.Category || '') + (c.SubCategory && c.SubCategory !== 'None' ? ' › ' + c.SubCategory : '')"></span>
            </button>
          </template>
        </div>
      </template>

      <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-3 mb-1"><?= $e($t('common.all_f')) ?></div>
      <template x-for="c in filteredCategories()" :key="'c'+c.CategoryName+'/'+c.SubCategoryName">
        <button @click="pickCategory(c.CategoryName, c.SubCategoryName)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
          <span class="h-8 w-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs">›</span>
          <span class="flex-1 text-sm" x-text="c.CategoryName + (c.SubCategoryName && c.SubCategoryName !== 'None' ? ' › ' + c.SubCategoryName : '')"></span>
        </button>
      </template>

      <!-- Création de catégorie / sous-catégorie -->
      <div class="mt-4 pt-4 border-t border-slate-100">
        <div class="text-[11px] uppercase tracking-wider text-slate-400 mb-2"><?= $e($t('tx.new_category_section')) ?></div>
        <div class="space-y-2">
          <input type="text" x-model="newCat.category" placeholder="<?= $e($t('tx.parent_placeholder')) ?>"
                 class="w-full h-11 px-3 rounded-xl border border-slate-200 text-sm">
          <input type="text" x-model="newCat.subcategory" placeholder="<?= $e($t('tx.sub_placeholder')) ?>"
                 class="w-full h-11 px-3 rounded-xl border border-slate-200 text-sm">
          <button @click="createCategory()" :disabled="!newCat.category.trim()"
                  class="w-full h-11 rounded-xl bg-indigo-600 text-white text-sm font-semibold disabled:opacity-40">
            <?= $e($t('tx.create_and_select')) ?>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Payee -->
  <section x-show="sheet === 'payee'" x-transition.duration.200ms
           class="fixed inset-x-0 bottom-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[85vh] overflow-hidden pb-safe flex flex-col">
    <div class="mx-auto w-full max-w-md p-5 pb-2">
      <div class="h-1 w-10 bg-slate-200 rounded mx-auto mb-4"></div>
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold"><?= $e($t('tx.payee')) ?></h3>
        <button @click="closeSheet()" class="p-2 text-slate-500">✕</button>
      </div>
      <input type="text" x-model="search" placeholder="<?= $e($t('tx.search_or_create')) ?>"
             class="w-full h-11 px-4 rounded-xl border border-slate-200 text-base bg-slate-50">
    </div>
    <div class="mx-auto w-full max-w-md flex-1 overflow-y-auto px-5 pb-5 no-scrollbar">
      <template x-if="payee && search.length === 0">
        <button @click="clearPayee(); closeSheet()" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl bg-rose-50 text-rose-700 text-left">
          <span class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center text-xs">✕</span>
          <span class="flex-1 text-sm font-medium"><?= $e($t('tx.none_payee')) ?></span>
        </button>
      </template>

      <template x-if="search.length === 0 && frequent.payees.length > 0">
        <div>
          <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-3 mb-1"><?= $e($t('common.frequent_m')) ?></div>
          <template x-for="p in frequent.payees" :key="'fp'+p">
            <button @click="pickPayeeByName(p)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
              <span class="h-8 w-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-semibold" x-text="p?.[0]"></span>
              <span class="flex-1 text-sm" x-text="p"></span>
            </button>
          </template>
        </div>
      </template>

      <div class="text-[11px] uppercase tracking-wider text-slate-400 mt-3 mb-1"><?= $e($t('common.all_m')) ?> (A→Z)</div>
      <template x-for="p in filteredPayees()" :key="'p'+p.PayeeName">
        <button @click="pickPayee(p)" class="w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-slate-50 text-left">
          <span class="h-8 w-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-semibold" x-text="p.PayeeName[0]"></span>
          <span class="flex-1 text-sm" x-text="p.PayeeName"></span>
          <template x-if="p.DefCateg">
            <span class="text-[10px] text-slate-400 truncate max-w-[120px]" x-text="p.DefCateg + (p.DefSubCateg ? ' › ' + p.DefSubCateg : '')"></span>
          </template>
        </button>
      </template>

      <template x-if="search.length > 0 && !hasExactPayee()">
        <button @click="createPayee()" class="w-full mt-2 px-3 py-3 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-medium">
          ＋ Créer « <span x-text="search"></span> »
        </button>
      </template>
    </div>
  </section>

</div>

</body>
</html>
