<div class="modal modal-blur fade" id="expense-code-modal" tabindex="-1" aria-hidden="true" aria-labelledby="expense-code-modal-title">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" id="expense-code-form" method="post" action="/settings/cost-structure/codes">
            <div class="modal-header">
                <h3 class="modal-title" id="expense-code-modal-title">إضافة بند فرعي</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="_token" value="<?=e($csrfToken)?>">
                <input type="hidden" name="work_section_id">
                <div class="mb-3"><label class="form-label fw-bold">البند الرئيسي</label><input class="form-control" id="expense-code-section-name" readonly></div>
                <div class="mb-3"><label class="form-label fw-bold required" for="expense-code-value">كود البند الفرعي</label><input class="form-control" id="expense-code-value" name="cost_code" maxlength="30" required autocomplete="off"><div class="invalid-feedback" data-code-error="cost_code"></div></div>
                <div class="mb-3"><label class="form-label fw-bold required" for="expense-code-name">اسم البند الفرعي</label><input class="form-control" id="expense-code-name" name="name" maxlength="255" required><div class="invalid-feedback" data-code-error="name"></div></div>
                <div class="alert alert-danger d-none mb-0" role="alert" id="expense-code-error"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-link" type="button" data-bs-dismiss="modal">إلغاء</button>
                <button class="btn btn-primary" id="save-expense-code" type="submit">حفظ واختيار البند</button>
            </div>
        </form>
    </div>
</div>
