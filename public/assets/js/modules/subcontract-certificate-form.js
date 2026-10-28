'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-progress-row]').forEach(row => {
        const input = row.querySelector('[data-current-quantity]');
        if (!input) return;
        const mode = row.querySelector('[data-quantity-mode]');
        const unit = row.querySelector('[data-current-unit]');
        const unitLabel = unit.textContent;
        if (mode?.value === 'percentage') {
            const limit = Number(row.dataset.limit || 0);
            const total = Number(row.dataset.previous || 0) + Number(input.value || 0);
            input.value = (limit > 0 ? total / limit * 100 : 0).toFixed(4);
        }
        const update = () => {
            const previous = Number(row.dataset.previous || 0);
            const rate = Number(row.dataset.rate || 0);
            const limit = Number(row.dataset.limit || 0);
            const percentage = mode?.value === 'percentage';
            const entered = Number(input.value || 0);
            const current = percentage ? Math.round(entered * limit * 100) / 10000 - previous : entered;
            const lump = row.dataset.lump === '1';
            const cumulative = previous + current;
            input.max = String(percentage ? 100 : Math.max(0, limit - previous));
            input.setCustomValidity(percentage && current < -0.000001 ? 'النسبة الإجمالية أقل من التنفيذ المعتمد سابقًا.' : '');
            unit.textContent = percentage ? '%' : unitLabel;
            const converted = row.querySelector('[data-converted-quantity]');
            if (converted) converted.textContent = percentage ? 'كمية هذا المستخلص: ' + current.toFixed(4) + ' ' + unitLabel : 'النسبة الإجمالية: ' + (limit > 0 ? (cumulative / limit * 100).toFixed(4) : '0.0000') + '%';
            row.querySelector('[data-cumulative]').textContent = cumulative.toFixed(4) + (lump ? '%' : '');
            row.querySelector('[data-current-amount]').textContent = (current * rate / (lump ? 100 : 1)).toFixed(2);
            row.querySelector('[data-cumulative-amount]').textContent = (cumulative * rate / (lump ? 100 : 1)).toFixed(2);
        };
        input.addEventListener('input', update);
        mode?.addEventListener('change', () => {
            const limit = Number(row.dataset.limit || 0);
            const entered = Number(input.value || 0);
            const previous = Number(row.dataset.previous || 0);
            input.value = (mode.value === 'percentage' ? (limit > 0 ? (previous + entered) / limit * 100 : 0) : Math.round(entered * limit * 100) / 10000 - previous).toFixed(4);
            update();
        });
        update();
    });
});
