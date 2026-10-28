'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('cps-form');
    if (!form) return;
    const isBoq = form.dataset.method === 'boq';
    const money = value => (Math.round((Number(value) + Number.EPSILON) * 100) / 100).toFixed(2);
    const rate = Number(form.dataset.markup || 0);
    const date = form.elements.namedItem('statement_date');
    const status = document.getElementById('cps-preview-status');
    let requestId = 0, pending = false, previewFailed = false;

    function update() {
        let cost = 0, variation = 0;
        if (isBoq) {
            form.querySelectorAll('.cps-boq-quantity').forEach(input => {
                const row = input.closest('tr');
                const current = Number(input.value || 0);
                const previous = Number(row.querySelector('.cps-previous-qty')?.textContent || 0);
                const amount = Number(money(current * Number(input.dataset.rate || 0)));
                row.querySelector('.cps-cumulative-qty').textContent = (previous + current).toFixed(4);
                row.querySelector('.cps-current-amount').textContent = money(amount);
                cost += amount;
            });
        } else {
            const amounts = {}, groups = {};
            form.querySelectorAll('.cps-cost:checked').forEach(input => {
                const amount = Number(input.dataset.amount || 0);
                const key = input.dataset.scopeItem || '0';
                cost += amount;
                amounts[key] = (amounts[key] || 0) + amount;
            });
            form.querySelectorAll('[data-scope-row]').forEach(row => {
                const earned = Number(row.dataset.subcontractCost || 0);
                cost += earned;
                const previous = Number(row.dataset.previousCost || 0);
                const current = earned + (amounts[row.dataset.scopeRow] || 0);
                const values = {previous_cost: previous, current_cost: current, cumulative_cost: previous + current};
                Object.entries(values).forEach(([key, value]) => { row.querySelector(`[data-scope-cost="${key}"]`).textContent = money(value); });
                const group = groups[row.dataset.scopeGroup] ||= {previous_cost: 0, current_cost: 0, cumulative_cost: 0};
                Object.entries(values).forEach(([key, value]) => { group[key] += value; });
            });
            form.querySelectorAll('[data-scope-group-total]').forEach(row => {
                const totals = groups[row.dataset.scopeGroupTotal];
                if (totals) Object.entries(totals).forEach(([key, value]) => { row.querySelector(`[data-scope-total="${key}"]`).textContent = money(value); });
            });
            form.querySelectorAll('[data-scope-group-billable]').forEach(row => {
                const totals = groups[row.dataset.scopeGroupBillable];
                if (totals) Object.entries(totals).forEach(([key, value]) => { row.querySelector(`[data-scope-billable="${key}"]`).textContent = money(value + Number(money(value * rate / 100))); });
            });
        }
        form.querySelectorAll('.cps-variation').forEach(input => { variation += Number(input.value || 0) * (input.dataset.effect === 'decrease' ? -1 : 1); });
        const markup = isBoq ? 0 : Number(money(cost * rate / 100));
        Object.entries({cost, markup, variations: variation, total: cost + markup + variation}).forEach(([key, value]) => {
            const element = document.getElementById('sum-' + key);
            if (element) element.textContent = money(value);
        });
    }

    async function refresh() {
        if (isBoq || !date.checkValidity()) return;
        const currentRequest = ++requestId;
        pending = true;
        previewFailed = false;
        status.textContent = 'جارٍ تحديث التنفيذ والمصروفات حسب تاريخ المستخلص…';
        const selected = new Set([...form.querySelectorAll('.cps-cost:checked')].map(input => input.value));
        const query = new URLSearchParams({statement_date: date.value});
        if (form.dataset.statementId) query.set('statement_id', form.dataset.statementId);
        try {
            const response = await fetch(`/api/client-contracts/${encodeURIComponent(form.dataset.contractId)}/progress-statements/preview?${query}`, {headers: {Accept: 'application/json'}});
            const data = await response.json();
            if (currentRequest !== requestId) return;
            if (!response.ok) throw new Error(data.message || 'تعذر تحديث المعاينة.');
            document.getElementById('cps-scope-preview').innerHTML = data.scopeHtml;
            document.getElementById('cps-costs-preview').innerHTML = data.costsHtml;
            form.querySelectorAll('.cps-cost').forEach(input => { input.checked = selected.has(input.value); });
            update();
            status.textContent = 'تم تحديث التنفيذ والمصروفات حسب تاريخ المستخلص.';
        } catch {
            if (currentRequest !== requestId) return;
            previewFailed = true;
            status.textContent = 'تعذر تحديث المعاينة. اضغط «تحديث التنفيذ والمصروفات» للمحاولة مجددًا قبل الحفظ.';
        } finally {
            if (currentRequest === requestId) pending = false;
        }
    }
    form.addEventListener('input', update);
    form.addEventListener('change', event => { update(); if (event.target === date) refresh(); });
    form.addEventListener('click', event => {
        if (event.target.closest('#select-all-costs')) { form.querySelectorAll('.cps-cost').forEach(input => { input.checked = true; }); update(); }
        if (event.target.closest('#cps-refresh-preview')) refresh();
    });
    form.addEventListener('submit', event => {
        if (!pending && !previewFailed) return;
        event.preventDefault();
        window.AppAlert?.show({type: 'error', title: 'راجع المعاينة', text: pending ? 'انتظر اكتمال تحديث التنفيذ والمصروفات.' : 'حدّث التنفيذ والمصروفات قبل حفظ المسودة.'});
    });
    update();
});
