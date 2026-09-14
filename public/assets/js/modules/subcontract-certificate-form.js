'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-progress-row]').forEach(row => {
        const input = row.querySelector('[data-current-quantity]');
        if (!input) return;
        const update = () => {
            const previous = Number(row.dataset.previous || 0);
            const rate = Number(row.dataset.rate || 0);
            const current = Number(input.value || 0);
            row.querySelector('[data-cumulative]').textContent = (previous + current).toFixed(4);
            row.querySelector('[data-current-amount]').textContent = (current * rate).toFixed(2);
            row.querySelector('[data-cumulative-amount]').textContent = ((previous + current) * rate).toFixed(2);
        };
        input.addEventListener('input', update);
        update();
    });
});
