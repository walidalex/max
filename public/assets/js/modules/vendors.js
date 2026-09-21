'use strict';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.js-vendor-form').forEach(initializeVendorForm);
  initializeCreateModal();
  initializeTable();
});

function initializeVendorForm(form) {
  const type = form.querySelector('.js-vendor-type');
  const checkboxes = [...form.querySelectorAll('.js-work-section-checkbox')];
  const refresh = () => {
    checkboxes.forEach((checkbox) => checkbox.closest('.vendor-work-section')?.classList.toggle('is-selected', checkbox.checked));
    const count = checkboxes.filter((checkbox) => checkbox.checked).length;
    const badge = form.querySelector('.js-selected-count');
    if (badge) badge.textContent = `${count} محدد`;
    const required = ['subcontractor', 'both'].includes(type?.value || '');
    const hint = form.querySelector('.js-work-sections-hint');
    if (hint) hint.textContent = required ? 'اختر مجال عمل واحداً على الأقل لمقاول الباطن.' : 'يمكنك اختيار المجالات التي يعمل بها المورد إن وجدت.';
  };
  checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refresh));
  type?.addEventListener('change', refresh);
  refresh();
}

function initializeCreateModal() {
  const modalElement = document.getElementById('create-vendor-modal');
  const form = document.getElementById('create-vendor-form');
  if (!modalElement || !form || !window.tabler?.Modal) return;
  const modal = window.tabler.Modal.getOrCreateInstance(modalElement);
  if (modalElement.dataset.openOnLoad === '1') modal.show();
  modalElement.addEventListener('hidden.bs.modal', () => {
    form.reset();
    clearErrors(form);
    form.querySelectorAll('.js-work-section-checkbox').forEach((checkbox) => checkbox.dispatchEvent(new Event('change')));
    history.replaceState({}, '', '/vendors');
  });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const workSectionSelect = modalElement.dataset.vendorTarget ? document.getElementById('subcontract-work-section-id') : null;
    const selectedWorkSection = workSectionSelect?.value;
    if (selectedWorkSection) {
      const linked = form.querySelector(`.js-work-section-checkbox[value="${selectedWorkSection}"]`);
      if (linked && !linked.checked) { linked.checked = true; linked.dispatchEvent(new Event('change')); }
    }
    clearErrors(form);
    const button = form.querySelector('.js-save-vendor');
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)});
      const payload = await response.json();
      if (!response.ok || !payload.ok) {
        showErrors(form, payload.errors || {}, payload.message || 'تعذر حفظ المورد.');
        return;
      }
      selectCreatedVendor(modalElement, payload.vendor);
      modal.hide();
      window.AppAlert?.show({type: 'success', title: 'تم الحفظ', text: payload.message});
      window.vendorDataTable?.ajax.reload(null, false);
    } catch (_) {
      showErrors(form, {}, 'تعذر الاتصال بالخادم. حاول مرة أخرى.');
    } finally {
      if (button) button.disabled = false;
    }
  });
}

function selectCreatedVendor(modalElement, vendor) {
  const selector = modalElement.dataset.vendorTarget;
  if (!selector || !vendor) return;
  const select = document.querySelector(selector);
  if (!select) return;
  const value = String(vendor.id);
  const label = `${vendor.vendor_code} - ${vendor.name}`;
  if (select.tomselect) {
    select.tomselect.addOption({value, text: label});
    select.tomselect.setValue(value);
  } else {
    select.add(new Option(label, value, true, true));
    select.dispatchEvent(new Event('change', {bubbles: true}));
  }
  const saveButton = document.getElementById('save-subcontract');
  if (saveButton && document.querySelector('[name="project_id"]')?.value) saveButton.disabled = false;
}

function clearErrors(form) {
  form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
  form.querySelectorAll('[data-error-for]').forEach((element) => { element.textContent = ''; });
  const alert = form.querySelector('.js-form-error');
  if (alert) { alert.textContent = ''; alert.classList.add('d-none'); }
}

function showErrors(form, errors, message) {
  Object.entries(errors).forEach(([field, messages]) => {
    form.querySelector(`[name="${field}"]`)?.classList.add('is-invalid');
    const target = form.querySelector(`[data-error-for="${field}"]`);
    if (target) target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
  });
  const alert = form.querySelector('.js-form-error');
  if (alert) { alert.textContent = message; alert.classList.remove('d-none'); }
}

function initializeTable() {
  const tableElement = document.getElementById('vendors-table');
  const card = document.getElementById('vendors-table-card');
  if (!tableElement || !card || !window.AppDataTable) return;
  const escape = window.AppDataTable.escape;
  const canEdit = card.dataset.canEdit === '1';
  const canActivate = card.dataset.canActivate === '1';
  const csrf = card.dataset.csrf || '';
  const labels = {supplier: 'مورد', subcontractor: 'مقاول باطن', both: 'كلاهما'};
  const table = window.AppDataTable.create(tableElement, {
    ajax: {url: '/api/vendors', data(data) { data.status = document.getElementById('vendor-status-filter')?.value || ''; data.vendor_type = document.getElementById('vendor-type-filter')?.value || ''; }, error() { window.AppAlert?.show({type: 'error', title: 'تعذر التحميل', text: 'لم نتمكن من تحميل القائمة.'}); }},
    columns: [
      {data: 'vendor_code'}, {data: 'name', render: (value) => escape(value)}, {data: 'vendor_type', render: (value) => labels[value] || '—'},
      {data: null, render: (row) => escape(row.mobile || row.phone || '—')}, {data: 'primary_contact', render: (value) => escape(value || '—')},
      {data: 'work_sections_count', render: (value) => `<span class="badge bg-blue-lt">${Number(value)} مجال</span>`},
      {data: 'is_active', render: (value) => Number(value) === 1 ? '<span class="badge bg-green-lt">نشط</span>' : '<span class="badge bg-secondary-lt">غير نشط</span>'},
      {data: null, orderable: false, searchable: false, render: actions},
    ],
    columnDefs: [{targets: 0, render(value, type, row) { return type === 'display' ? `<a href="/vendors/${Number(row.id)}">${escape(value)}</a>` : value; }}],
  });
  window.vendorDataTable = table;
  function actions(row) {
    const id = Number(row.id), buttons = [`<a class="btn btn-sm" href="/vendors/${id}">عرض</a>`];
    if (canEdit) buttons.push(`<a class="btn btn-sm" href="/vendors/${id}/edit">تعديل</a>`);
    if (canActivate) { const active = Number(row.is_active) === 1; buttons.push(`<form class="d-inline js-vendor-status" data-confirm="هل تريد ${active ? 'تعطيل' : 'تفعيل'} هذا المورد؟" method="post" action="/vendors/${id}/activate"><input type="hidden" name="_token" value="${escape(csrf)}"><input type="hidden" name="active" value="${active ? 0 : 1}"><button class="btn btn-sm ${active ? 'btn-outline-danger' : 'btn-outline-success'}" type="submit">${active ? 'تعطيل' : 'تفعيل'}</button></form>`); }
    return `<div class="btn-list flex-nowrap">${buttons.join('')}</div>`;
  }
  tableElement.addEventListener('submit', async (event) => { const form = event.target.closest('.js-vendor-status'); if (!form) return; event.preventDefault(); const result = await window.AppAlert.confirm({text: form.dataset.confirm || ''}); if (result.isConfirmed) form.submit(); });
  ['vendor-status-filter', 'vendor-type-filter'].forEach((id) => document.getElementById(id)?.addEventListener('change', () => table.ajax.reload()));
}
