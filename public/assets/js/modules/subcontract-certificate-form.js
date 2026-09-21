'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-progress-row]').forEach(row => {
        const input = row.querySelector('[data-current-quantity]');
        if (!input) return;
        const update = () => {
            const previous = Number(row.dataset.previous || 0);
            const rate = Number(row.dataset.rate || 0);
            const current = Number(input.value || 0);
            const lump = row.dataset.lump === '1';
            const cumulative = previous + current;
            input.max = String(Math.max(0, Number(row.dataset.limit || 0) - previous));
            row.querySelector('[data-cumulative]').textContent = cumulative.toFixed(4) + (lump ? '%' : '');
            row.querySelector('[data-current-amount]').textContent = (current * rate / (lump ? 100 : 1)).toFixed(2);
            row.querySelector('[data-cumulative-amount]').textContent = (cumulative * rate / (lump ? 100 : 1)).toFixed(2);
        };
        input.addEventListener('input', update);
        update();
    });
});
