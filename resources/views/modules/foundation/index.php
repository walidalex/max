<div class="page-header d-print-none mb-4">
    <div class="row align-items-center"><div class="col"><div class="page-pretitle">Contracting & Interior Design ERP</div><h2 class="page-title">أساس نظيف وجاهز للتوسع</h2></div></div>
</div>
<div class="row row-cards">
    <div class="col-md-8">
        <div class="card"><div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4"><span class="avatar bg-green-lt"><i class="ti ti-check"></i></span><div><h3 class="m-0">التطبيق يعمل</h3><div class="text-secondary">الموجّه، الجلسات، العروض، ومعالجة الأخطاء جاهزة.</div></div></div>
            <form action="/foundation/check" method="post" class="row g-3">
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <div class="col-md-8"><label class="form-label" for="label">قيمة اختبار الاتصال</label><select class="form-select js-tom-select" id="label" name="label" required><option value="Foundation ready">Foundation ready</option><option value="Database connected">Database connected</option></select></div>
                <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><i class="ti ti-database-check ms-2"></i>اختبار MySQLi</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h3 class="card-title">نطاق المرحلة</h3><p class="text-secondary">بنية تحتية فقط، دون وحدات ERP تشغيلية.</p><a class="btn btn-outline-secondary" href="/api/health"><i class="ti ti-heartbeat ms-2"></i>API Health</a></div></div></div>
</div>
