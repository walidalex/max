<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="نظام إدارة المقاولات والتصميم الداخلي">
    <title><?= e($title ?? $appName) ?> | <?= e($companyBrand['name'] ?? $appName) ?></title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="/assets/vendor/tabler/tabler.rtl.min.css">
    <link rel="stylesheet" href="/assets/vendor/tabler-icons/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="/assets/vendor/tom-select/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/vendor/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="page">
    <?= $this->component('sidebar', ['companyBrand' => $companyBrand ?? null, 'canView' => $canView ?? null]) ?>
    <div class="page-wrapper">
        <?= $this->component('navbar', ['title' => $title ?? $appName, 'currentUser' => $currentUser ?? null, 'csrfToken' => $csrfToken]) ?>
        <main class="page-body">
            <div class="container-xl"><?= $content ?></div>
        </main>
        <footer class="footer footer-transparent py-3"><div class="container-xl text-secondary small">أساس النظام — <?= date('Y') ?></div></footer>
    </div>
</div>
<script src="/assets/vendor/tabler/tabler.min.js" defer></script>
<script src="/assets/vendor/sweetalert2/sweetalert2.all.min.js" defer></script>
<script src="/assets/vendor/tom-select/tom-select.complete.min.js" defer></script>
<script src="/assets/vendor/datatables/dataTables.min.js" defer></script>
<script src="/assets/vendor/datatables/dataTables.bootstrap5.min.js" defer></script>
<script src="/assets/js/alerts.js" defer></script>
<script src="/assets/js/tom-select.js" defer></script>
<script src="/assets/js/datatables.js" defer></script>
<script src="/assets/js/app.js" defer></script>
<?php if (is_array($flash ?? null)): ?>
<script type="application/json" id="flash-alert"><?= json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
</body>
</html>
