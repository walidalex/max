<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <div class="navbar-nav flex-row order-md-last">
            <?php if (is_array($currentUser ?? null)): ?>
            <div class="nav-item d-flex align-items-center px-2"><?= e($currentUser['name'] ?? $currentUser['username']) ?></div>
            <form class="nav-item" action="/logout" method="post"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><button class="nav-link btn btn-link" type="submit"><i class="ti ti-logout ms-1"></i>خروج</button></form>
            <?php endif; ?>
            <div class="nav-item"><span class="nav-link"><i class="ti ti-language ms-1"></i>العربية</span></div>
        </div>
        <div class="navbar-nav"><div class="nav-item"><span class="nav-link fw-semibold"><?= e($title) ?></span></div></div>
    </div>
</header>
