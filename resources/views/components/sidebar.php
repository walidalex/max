<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="فتح القائمة"><span class="navbar-toggler-icon"></span></button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="/" class="app-brand text-decoration-none">
                <?php if (is_array($companyBrand ?? null) && !empty($companyBrand['logo_path'])): ?>
                    <img class="app-brand-logo" src="/settings/company/logo?v=<?= urlencode((string) ($companyBrand['updated_at'] ?? '')) ?>" alt="شعار <?= e($companyBrand['name'] ?? 'الشركة') ?>">
                <?php else: ?>
                    <i class="ti ti-building-community app-brand-icon" aria-hidden="true"></i>
                <?php endif; ?>
                <span class="app-brand-name"><?= e($companyBrand['name'] ?? 'نظام المقاولات والديكور') ?></span>
            </a>
        </h1>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item"><a class="nav-link active" href="/"><span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-home"></i></span><span class="nav-link-title">البداية</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/clients"><span class="nav-link-icon"><i class="ti ti-address-book"></i></span><span class="nav-link-title">العملاء</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/vendors"><span class="nav-link-icon"><i class="ti ti-truck-delivery"></i></span><span class="nav-link-title">الموردون ومقاولو الباطن</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/project-costs"><span class="nav-link-icon"><i class="ti ti-cash"></i></span><span class="nav-link-title">التكاليف الفعلية</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/projects"><span class="nav-link-icon"><i class="ti ti-briefcase"></i></span><span class="nav-link-title">المشاريع</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/access/users"><span class="nav-link-icon"><i class="ti ti-users"></i></span><span class="nav-link-title">المستخدمون</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/access/roles"><span class="nav-link-icon"><i class="ti ti-user-shield"></i></span><span class="nav-link-title">الأدوار</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/access/permissions"><span class="nav-link-icon"><i class="ti ti-key"></i></span><span class="nav-link-title">الصلاحيات</span></a></li>
                <li class="nav-item mt-3"><span class="nav-link disabled"><span class="nav-link-title text-uppercase text-secondary small">الإعدادات</span></span></li>
                <li class="nav-item"><a class="nav-link" href="/settings/company"><span class="nav-link-icon"><i class="ti ti-building"></i></span><span class="nav-link-title">ملف الشركة</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/settings/cost-structure"><span class="nav-link-icon"><i class="ti ti-hierarchy-2"></i></span><span class="nav-link-title">هيكل التكاليف</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/contracts"><span class="nav-link-icon"><i class="ti ti-file-certificate"></i></span><span class="nav-link-title">عقود العملاء</span></a></li>
                <li class="nav-item"><a class="nav-link" href="/subcontracts"><span class="nav-link-icon"><i class="ti ti-file-invoice"></i></span><span class="nav-link-title">عقود مقاولي الباطن</span></a></li>
            </ul>
        </div>
    </div>
</aside>
