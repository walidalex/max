'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('client_type');
    if (!type) return;
    const update = () => {
        const company = type.value === 'company';
        document.querySelectorAll('.js-company-field').forEach(field => {
            field.classList.toggle('d-none', !company);
            field.querySelectorAll('input').forEach(input => { input.disabled = !company; });
        });
        document.querySelectorAll('.js-individual-field').forEach(field => {
            field.classList.toggle('d-none', company);
            field.querySelectorAll('input').forEach(input => { input.disabled = company; });
        });
        document.querySelector('.js-company-field [name="company_name"]')?.toggleAttribute('required', company);
        document.querySelector('.js-individual-field [name="name"]')?.toggleAttribute('required', !company);
    };
    type.addEventListener('change', update);
    update();
});
