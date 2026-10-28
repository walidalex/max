'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('expense-type');
    const section = document.getElementById('cost-section');
    const code = document.getElementById('project-cost-code');
    if (!type || !section || !code) return;
    const choices = [...code.options].filter(option => option.value).map(option => ({
        value: option.value,
        text: option.textContent,
        section: option.dataset.section,
        sectionName: option.dataset.sectionName,
    }));
    const refresh = () => {
        const general = type.value === 'general';
        const current = code.value;
        const filtered = choices.filter(option => general || option.section === section.value);
        const addButton = document.getElementById('add-expense-code');
        if (addButton) addButton.disabled = general || !section.value;
        document.getElementById('cost-section-field').hidden = general;
        document.getElementById('expense-code-label').textContent = general ? 'تصنيف المصروف العام' : 'البند الفرعي';
        section.required = !general;
        if (section.tomselect) general ? section.tomselect.disable() : section.tomselect.enable();
        else section.disabled = general;
        const control = code.tomselect;
        if (control) {
            control.clear(true);
            control.clearOptions();
            control.addOptions(filtered.map(option => ({...option, text: general ? option.sectionName + ' — ' + option.text : option.text})));
            if (filtered.some(option => option.value === current)) control.setValue(current, true);
            general || section.value ? control.enable() : control.disable();
        } else {
            code.replaceChildren(new Option(general ? 'اختر تصنيف المصروف' : 'اختر البند الفرعي', ''));
            filtered.forEach(option => code.add(new Option(general ? option.sectionName + ' — ' + option.text : option.text, option.value)));
            code.disabled = !general && !section.value;
            if (filtered.some(option => option.value === current)) code.value = current;
        }
    };
    type.addEventListener('change', refresh);
    section.addEventListener('change', refresh);
    // Initialize after the shared Tom Select DOMContentLoaded listener.
    setTimeout(refresh, 0);

    const modalElement = document.getElementById('expense-code-modal');
    const modalForm = document.getElementById('expense-code-form');
    const addButton = document.getElementById('add-expense-code');
    if (!modalElement || !modalForm || !addButton || !window.tabler?.Modal) return;
    const modal = window.tabler.Modal.getOrCreateInstance(modalElement);
    const errorBox = document.getElementById('expense-code-error');
    const saveButton = document.getElementById('save-expense-code');
    const clearErrors = () => {
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
        modalForm.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
        modalForm.querySelectorAll('[data-code-error]').forEach(element => { element.textContent = ''; });
    };
    addButton.addEventListener('click', () => {
        if (type.value !== 'work' || !section.value) return;
        modalForm.reset();
        clearErrors();
        modalForm.elements.work_section_id.value = section.value;
        document.getElementById('expense-code-section-name').value = section.selectedOptions[0]?.textContent || '';
        modal.show();
    });
    modalElement.addEventListener('shown.bs.modal', () => document.getElementById('expense-code-name').focus());
    modalForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (saveButton.disabled) return;
        clearErrors();
        const data = new FormData(modalForm);
        const sectionId = String(data.get('work_section_id'));
        const sectionName = document.getElementById('expense-code-section-name').value;
        saveButton.disabled = true;
        try {
            const response = await fetch(modalForm.action, {method: 'POST', headers: {Accept: 'application/json'}, body: data});
            const payload = await response.json();
            if (!response.ok || !Number.isSafeInteger(Number(payload.id)) || Number(payload.id) < 1) {
                Object.entries(payload.errors || {}).forEach(([field, messages]) => {
                    modalForm.elements.namedItem(field)?.classList.add('is-invalid');
                    const target = modalForm.querySelector('[data-code-error="' + field + '"]');
                    if (target) target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
                });
                throw new Error(payload.message || 'تعذر إضافة البند الفرعي.');
            }
            const value = String(payload.id);
            choices.push({value, text: String(data.get('cost_code')).trim() + ' — ' + String(data.get('name')).trim(), section: sectionId, sectionName});
            type.value = 'work';
            type.tomselect?.setValue('work', true);
            if (section.tomselect) section.tomselect.setValue(sectionId, true);
            else section.value = sectionId;
            refresh();
            if (code.tomselect) code.tomselect.setValue(value);
            else { code.value = value; code.dispatchEvent(new Event('change', {bubbles: true})); }
            modal.hide();
            window.AppAlert?.show({type: 'success', title: 'تمت الإضافة', text: 'تم حفظ البند الفرعي واختياره للمصروف.', timer: 2500});
        } catch (error) {
            errorBox.textContent = error.message || 'تعذر الاتصال بالخادم.';
            errorBox.classList.remove('d-none');
        } finally { saveButton.disabled = false; }
    });
});
