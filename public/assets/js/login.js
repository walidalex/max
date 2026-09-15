(() => {
    'use strict';
    const toggle = document.querySelector('.auth-password-toggle');
    const password = document.querySelector('#password');
    if (toggle && password) {
        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!visible));
            toggle.setAttribute('aria-label', visible ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
            const icon = toggle.querySelector('i');
            if (icon) icon.className = visible ? 'ti ti-eye' : 'ti ti-eye-off';
            password.focus();
        });
    }
    document.querySelector('.auth-form')?.addEventListener('submit', (event) => {
        const button = event.currentTarget.querySelector('.auth-submit');
        if (button && event.currentTarget.checkValidity()) {
            button.classList.add('is-loading');
            button.disabled = true;
        }
    });
})();
