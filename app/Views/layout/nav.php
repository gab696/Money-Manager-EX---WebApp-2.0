<?php /** @var string $active */ /** @var int $pendingCount */ /** @var string $baseUrl */ ?>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/90 backdrop-blur pb-safe">
  <div class="mx-auto max-w-md grid grid-cols-3">
    <a href="<?= $baseUrl ?>/new" class="flex flex-col items-center justify-center py-2.5 <?= $active === 'new' ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900' ?>">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      <span class="text-xs mt-0.5 <?= $active === 'new' ? 'font-semibold' : '' ?>"><?= htmlspecialchars(\App\I18n::t('nav.new'), ENT_QUOTES, 'UTF-8') ?></span>
    </a>
    <a href="<?= $baseUrl ?>/queue" class="relative flex flex-col items-center justify-center py-2.5 <?= $active === 'queue' ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900' ?>">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
      <span class="text-xs mt-0.5 <?= $active === 'queue' ? 'font-semibold' : '' ?>"><?= htmlspecialchars(\App\I18n::t('nav.queue'), ENT_QUOTES, 'UTF-8') ?></span>
      <?php if (!empty($pendingCount)): ?>
        <span class="absolute top-1.5 right-[calc(50%-22px)] min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-semibold flex items-center justify-center"><?= (int) $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= $baseUrl ?>/settings" class="flex flex-col items-center justify-center py-2.5 <?= $active === 'settings' ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900' ?>">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span class="text-xs mt-0.5 <?= $active === 'settings' ? 'font-semibold' : '' ?>"><?= htmlspecialchars(\App\I18n::t('nav.settings'), ENT_QUOTES, 'UTF-8') ?></span>
    </a>
  </div>
</nav>
