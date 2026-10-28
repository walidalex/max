'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('project-contract-wizard');
    if (!form) return;
    window.AppSelect?.init(form);
    form.noValidate = true;
    const find = id => document.getElementById(id);
    const sections = [...form.querySelectorAll('[data-wizard-step]')];
    const previous = find('wizard-previous'), next = find('wizard-next'), save = find('wizard-save');
    const client = find('client_id');
    const method = find('wizard-pricing'), markup = find('wizard-markup');
    const path = [1, 2, 3, 4];
    let current = 1, saving = false, contactsLoading = false, contactRequest = 0;
    const disable = (element, disabled) => {
        if (!element) return;
        if (element.tomselect) disabled ? element.tomselect.disable() : element.tomselect.enable();
        else element.disabled = disabled;
    };
    const label = element => element?.selectedOptions?.[0]?.textContent?.trim() || '';
    const updatePricing = () => {
        if (!method) return;
        const costPlus = method.value === 'cost_plus';
        find('wizard-markup-field').hidden = !costPlus;
        disable(markup, !costPlus);
        markup.required = costPlus;
        find('wizard-contract-value').required = method.value === 'lump_sum';
        form.querySelector('label[for="wizard-contract-value"]').classList.toggle('required', method.value === 'lump_sum');
    };
    function updateSource() {
        find('wizard-contract-client').value = client?.value ? label(client) : '';
    }
    function dateRange(section) {
        for (const prefix of ['project', 'contract']) {
            const start = section.querySelector(`[name="${prefix}[start_date]"]`);
            const end = section.querySelector(`[name="${prefix}[expected_end_date]"]`);
            if (start && end) end.setCustomValidity(start.value && end.value && end.value < start.value ? 'تاريخ الانتهاء المتوقع لا يمكن أن يسبق تاريخ البدء.' : '');
        }
    }
    function validate(index) {
        const section = sections.find(item => Number(item.dataset.wizardStep) === index);
        if (!section) return true;
        dateRange(section);
        const invalid = [...section.querySelectorAll('input, select, textarea')].find(element => !element.disabled && !element.checkValidity());
        if (!invalid) return true;
        show(index);
        if (invalid.tomselect) {
            invalid.tomselect.focus();
            window.AppAlert?.show({type: 'error', title: 'راجع البيانات', text: 'اختر قيمة للحقل المطلوب قبل المتابعة.'});
        } else invalid.reportValidity();
        return false;
    }
    function summary() {
        const container = find('wizard-summary');
        container.replaceChildren();
        const add = (title, step, values) => {
            const column = document.createElement('div'); column.className = 'col-lg-6';
            const card = document.createElement('div'); card.className = 'card h-100';
            const body = document.createElement('div'); body.className = 'card-body';
            const heading = document.createElement('h4'); heading.textContent = title;
            const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'btn btn-sm btn-outline-primary mb-3'; edit.textContent = 'تعديل'; edit.addEventListener('click', () => show(step));
            body.append(heading, edit);
            const list = document.createElement('dl'); list.className = 'row mb-0';
            values.forEach(([key, value]) => {
                const term = document.createElement('dt'); term.className = 'col-sm-5'; term.textContent = key;
                const description = document.createElement('dd'); description.className = 'col-sm-7 text-break'; description.style.whiteSpace = 'pre-wrap'; description.textContent = value || '—';
                list.append(term, description);
            });
            body.append(list); card.append(body); column.append(card); container.append(column);
        };
        const fields = index => {
            const section = sections.find(item => Number(item.dataset.wizardStep) === index);
            return [...(section?.querySelectorAll('input[name], select[name], textarea[name]') || [])]
                .filter(element => !element.disabled && element.type !== 'hidden')
                .map(element => [form.querySelector(`label[for="${element.tomselect?.control_input?.id || element.id}"]`)?.textContent || '', element.tagName === 'SELECT' ? (element.value ? label(element) : '') : element.value]);
        };
        add(find('wizard-title-1').textContent, 1, fields(1));
        add(find('wizard-title-2').textContent, 2, fields(2));
        add(find('wizard-title-3').textContent, 3, fields(3));
        find('wizard-save-note').textContent = 'سيُحفظ المشروع بحالة تخطيط والعقد كمسودة معًا. إذا فشل الحفظ فلن يُحفظ أي منهما.';
    }
    function show(index, focus = true) {
        current = index;
        sections.forEach(section => { section.hidden = Number(section.dataset.wizardStep) !== index; });
        form.querySelectorAll('[data-step-indicator]').forEach(indicator => {
            const step = Number(indicator.dataset.stepIndicator);
            indicator.hidden = !path.includes(step);
            indicator.classList.toggle('active', step === index);
            const button = indicator.querySelector('button');
            if (step === index) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
        });
        previous.hidden = index === 1;
        next.hidden = index === 4;
        save.hidden = index !== 4;
        next.disabled = contactsLoading;
        save.disabled = contactsLoading || saving;
        save.textContent = 'حفظ المشروع والعقد';
        find('wizard-progress').textContent = `الخطوة ${path.indexOf(index) + 1} من ${path.length}`;
        if (index === 4) summary();
        if (focus) {
            const heading = find(`wizard-title-${index}`); heading.tabIndex = -1; heading.focus();
        }
    }
    function go(target) {
        if (saving || contactsLoading || !path.includes(target)) return;
        if (path.indexOf(target) > path.indexOf(current)) {
            for (const index of path.slice(0, path.indexOf(target))) if (!validate(index)) return;
        }
        show(target);
    }
    async function loadContacts() {
        const request = ++contactRequest;
        const id = client?.value;
        const controls = [find('primary_contact_id'), find('wizard-contract-contact')].filter(Boolean);
        const reset = (element, items, selected = '') => {
            const options = [{value: '', text: 'بدون'}, ...items.map(item => ({value: String(item.id), text: item.name + (item.job_title ? ' - ' + item.job_title : '')}))];
            if (element.tomselect) {
                element.tomselect.clear(true); element.tomselect.clearOptions(); element.tomselect.addOptions(options);
                element.tomselect.setValue(selected, true);
            } else {
                element.replaceChildren(...options.map(option => new Option(option.text, option.value, false, option.value === selected)));
            }
        };
        controls.forEach(element => reset(element, []));
        contactsLoading = !!id;
        show(current, false);
        if (!id) return;
        try {
            const response = await fetch(`/api/project-contracts/contacts?id=${encodeURIComponent(id)}`, {headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error('تعذر تحميل جهات الاتصال. أعد اختيار العميل أو المشروع للمحاولة مجددًا.');
            const body = await response.json();
            if (request !== contactRequest) return;
            controls.forEach(element => {
                reset(element, body.data || [], element.dataset.selected || '');
                delete element.dataset.selected;
            });
        } catch {
            if (request === contactRequest) window.AppAlert?.show({type: 'error', title: 'تعذر التحميل', text: 'تعذر تحميل جهات الاتصال. أعد اختيار العميل أو المشروع للمحاولة مجددًا.'});
        } finally {
            if (request === contactRequest) { contactsLoading = false; show(current, false); }
        }
    }
    next.addEventListener('click', () => go(path[path.indexOf(current) + 1]));
    previous.addEventListener('click', () => go(path[path.indexOf(current) - 1]));
    form.querySelectorAll('[data-go-step]').forEach(button => button.addEventListener('click', () => go(Number(button.dataset.goStep))));
    client?.addEventListener('change', () => { updateSource(); loadContacts(); });
    method?.addEventListener('change', updatePricing);
    form.addEventListener('submit', event => {
        if (saving || contactsLoading) { event.preventDefault(); return; }
        if (current !== 4) { event.preventDefault(); go(path[path.indexOf(current) + 1]); return; }
        for (const index of path) if (!validate(index)) { event.preventDefault(); return; }
        saving = true; save.disabled = true; previous.disabled = true; save.textContent = 'جارٍ الحفظ…';
    });
    updatePricing();
    updateSource();
    show(1, false);
    loadContacts();
});
