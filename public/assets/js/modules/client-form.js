'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('client_type');
    if (!type) return;
    const update = () => {
        const company = type.value === 'company';
        document.querySelector('.js-company-field input')?.toggleAttribute('required', company);
        document.querySelector('.js-individual-field input')?.toggleAttribute('required', !company);
    };
    type.addEventListener('change', update);
    update();
});
