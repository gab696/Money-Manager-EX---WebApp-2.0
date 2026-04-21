<?php /** @var string $baseUrl */ ?>
<link rel="manifest" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/icons/favicon-16.png">
<link rel="icon" type="image/svg+xml"           href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/icons/icon.svg">
<link rel="apple-touch-icon" sizes="180x180"    href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="MMEX">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="MMEX Web">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/sw.js').catch(function () {});
    });
  }
</script>
