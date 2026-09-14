'use strict';

document.addEventListener('DOMContentLoaded', () => {
    window.AppSelect?.init();
    const flash = document.getElementById('flash-alert');
    if (flash) {
        try { window.AppAlert?.show(JSON.parse(flash.textContent)); } catch (error) { console.error('Invalid flash alert payload.', error); }
    }
    document.querySelectorAll('.js-confirm-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const result = await window.AppAlert.confirm({ text: form.dataset.confirm || '' });
            if (result.isConfirmed) { form.submit(); }
        });
    });
});
