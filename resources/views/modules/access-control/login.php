<?php $brandName=trim((string)($companyBrand['name']??''))?:'نظام المقاولات والتصميم الداخلي'; ?>
<section class="auth-visual" aria-label="منصة إدارة المقاولات والتصميم الداخلي">
    <div class="auth-glow auth-glow-one"></div><div class="auth-glow auth-glow-two"></div>
    <div class="auth-visual-content">
        <div class="auth-brand auth-reveal">
            <?php if(!empty($companyLogoDataUri)): ?><img src="<?= e($companyLogoDataUri) ?>" alt="شعار <?= e($brandName) ?>" class="auth-brand-logo">
            <?php else: ?><span class="auth-brand-mark"><i class="ti ti-building-skyscraper"></i></span><?php endif; ?>
            <div><strong><?= e($brandName) ?></strong><span>إدارة متكاملة للمقاولات والتصميم</span></div>
        </div>
        <div class="auth-message auth-reveal">
            <span class="auth-eyebrow"><i class="ti ti-sparkles"></i> مساحة عمل واحدة</span>
            <h1>نحوّل تفاصيل المشروع<br><em>إلى إنجاز واضح.</em></h1>
            <p>تابع أعمالك وتكاليفك وقراراتك من منصة صُممت لتمنح فريقك رؤية أدق وتحكّمًا أفضل.</p>
        </div>
        <div class="construction-scene" aria-hidden="true">
            <svg viewBox="0 0 900 380" role="img">
                <defs><linearGradient id="tower" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#fff" stop-opacity=".22"/><stop offset="1" stop-color="#fff" stop-opacity=".04"/></linearGradient></defs>
                <path class="scene-grid" d="M0 335H900M40 295H860M100 255H800M160 215H740M220 175H680"/>
                <g class="scene-buildings"><path d="M95 335V205h115v130M235 335V118h170v217M430 335V175h120v160M575 335V80h205v255" fill="url(#tower)" stroke="currentColor"/><path d="M265 155h105M265 195h105M265 235h105M265 275h105M610 125h135M610 170h135M610 215h135M610 260h135"/></g>
                <g class="scene-crane"><path d="M73 335V58M43 335h60M54 58h325M73 88h275M73 58l275 30M134 58l-61 55M348 88v95"/><path class="scene-hook" d="M348 183v58c0 22 31 22 31 0v-5"/></g>
                <g class="scene-plan"><path d="M470 310l74-42 92 42-74 42zM544 268v84M636 310v18"/><path class="scene-pulse" d="M562 307v-52M548 269l14-14 14 14"/></g>
            </svg>
        </div>
        <div class="auth-visual-footer"><span><i class="ti ti-shield-check"></i> وصول آمن ومحمي</span><span>© <?= date('Y') ?> <?= e($brandName) ?></span></div>
    </div>
</section>
<section class="auth-form-panel">
    <div class="auth-form-wrap auth-reveal">
        <div class="auth-mobile-brand"><?php if(!empty($companyLogoDataUri)): ?><img src="<?= e($companyLogoDataUri) ?>" alt="شعار الشركة"><?php else: ?><i class="ti ti-building-skyscraper"></i><?php endif; ?><strong><?= e($brandName) ?></strong></div>
        <div class="auth-form-heading"><span class="auth-form-icon"><i class="ti ti-login-2"></i></span><h2>مرحبًا بعودتك</h2><p>أدخل بيانات حسابك للمتابعة إلى لوحة العمل.</p></div>
        <form method="post" action="/login" class="auth-form" novalidate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <div class="mb-4"><label class="form-label" for="login">اسم المستخدم أو البريد الإلكتروني</label><div class="auth-input"><i class="ti ti-user"></i><input id="login" class="form-control form-control-lg" name="login" autocomplete="username" placeholder="أدخل اسم المستخدم" required autofocus></div></div>
            <div class="mb-2"><label class="form-label" for="password">كلمة المرور</label><div class="auth-input"><i class="ti ti-lock"></i><input id="password" class="form-control form-control-lg" type="password" name="password" autocomplete="current-password" placeholder="أدخل كلمة المرور" required><button class="auth-password-toggle" type="button" aria-label="إظهار كلمة المرور" aria-pressed="false"><i class="ti ti-eye"></i></button></div></div>
            <div class="auth-security-note"><i class="ti ti-lock-check"></i><span>بيانات دخولك مشفّرة ومحمية</span></div>
            <button class="btn btn-primary btn-lg w-100 auth-submit" type="submit"><span>تسجيل الدخول</span><i class="ti ti-arrow-left"></i></button>
        </form>
        <p class="auth-help">تواجه مشكلة في الدخول؟ تواصل مع مسؤول النظام.</p>
    </div>
</section>
