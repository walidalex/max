'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('cps-contract-selection');
    if (!form) return;
    window.AppSelect?.init(form);
    const project = document.getElementById('cps-create-project');
    const contract = document.getElementById('cps-create-contract');
    const options = [...contract.options].filter(option => option.value).map(option => ({
        value: option.value, text: option.textContent,
        projectId: option.dataset.projectId, clientName: option.dataset.clientName,
        pricingLabel: option.dataset.pricingLabel,
    }));
    const details = () => {
        const selected = options.find(option => option.value === contract.value);
        document.getElementById('cps-create-client').value = selected?.clientName || '';
        document.getElementById('cps-create-pricing').value = selected?.pricingLabel || '';
    };
    function updateContracts() {
        const selected = contract.value;
        const available = options.filter(option => option.projectId === project.value);
        const keep = available.some(option => option.value === selected) ? selected : '';
        if (contract.tomselect) {
            contract.tomselect.clear(true);
            contract.tomselect.clearOptions();
            contract.tomselect.addOptions([{value: '', text: 'اختر العقد'}, ...available]);
            contract.tomselect.setValue(keep || (available.length === 1 ? available[0].value : ''), true);
            project.value ? contract.tomselect.enable() : contract.tomselect.disable();
        } else {
            contract.replaceChildren(new Option('اختر العقد', ''), ...available.map(option => new Option(option.text, option.value)));
            contract.value = keep || (available.length === 1 ? available[0].value : '');
            contract.disabled = !project.value;
        }
        details();
    }
    project.addEventListener('change', updateContracts);
    contract.addEventListener('change', details);
    updateContracts();
});
