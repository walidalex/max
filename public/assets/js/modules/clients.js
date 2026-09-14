'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const tableElement = document.getElementById('clients-table');
    const card = document.getElementById('clients-table-card');
    if (!tableElement || !card || !window.AppDataTable) return;

    const escape = window.AppDataTable.escape;
    const canEdit = card.dataset.canEdit === '1';
    const canActivate = card.dataset.canActivate === '1';
    const csrf = card.dataset.csrf || '';
    const table = window.AppDataTable.create(tableElement, {
        ajax: {
            url: '/api/clients',
            data(data) {
                data.status = document.getElementById('client-status-filter')?.value || '';
                data.client_type = document.getElementById('client-type-filter')?.value || '';
            },
            error() {
                window.AppAlert?.show({ type: 'error', title: 'تعذر التحميل', text: 'لم نتمكن من تحميل قائمة العملاء.' });
            },
        },
        columns: [
            { data: 'client_code' },
            { data: null, render: (row) => escape(row.client_type === 'company' ? row.company_name : row.name) },
            { data: 'client_type', render: (value) => value === 'company' ? 'شركة' : 'فرد' },
            { data: null, render: (row) => escape(row.mobile || row.phone || '—') },
            { data: 'primary_contact', render: (value) => escape(value || '—') },
            { data: 'is_active', render: (value) => Number(value) === 1 ? '<span class="badge bg-green-lt">نشط</span>' : '<span class="badge bg-secondary-lt">غير نشط</span>' },
            { data: null, orderable: false, searchable: false, render: actions },
        ],
        columnDefs: [{ targets: 0, render(value, type, row) { return type === 'display' ? `<a href="/clients/${Number(row.id)}">${escape(value)}</a>` : value; } }],
    });

    function actions(row) {
        const id = Number(row.id);
        const buttons = [`<a class="btn btn-sm" href="/clients/${id}">عرض</a>`];
        if (canEdit) buttons.push(`<a class="btn btn-sm" href="/clients/${id}/edit">تعديل</a>`);
        if (canActivate) {
            const active = Number(row.is_active) === 1;
            buttons.push(`<form class="d-inline js-client-status" data-confirm="هل تريد ${active ? 'تعطيل' : 'تفعيل'} هذا العميل؟" method="post" action="/clients/${id}/activate"><input type="hidden" name="_token" value="${escape(csrf)}"><input type="hidden" name="active" value="${active ? 0 : 1}"><button class="btn btn-sm ${active ? 'btn-outline-danger' : 'btn-outline-success'}" type="submit">${active ? 'تعطيل' : 'تفعيل'}</button></form>`);
        }
        return `<div class="btn-list flex-nowrap">${buttons.join('')}</div>`;
    }

    tableElement.addEventListener('submit', async (event) => {
        const form = event.target.closest('.js-client-status');
        if (!form) return;
        event.preventDefault();
        const result = await window.AppAlert.confirm({ text: form.dataset.confirm || '' });
        if (result.isConfirmed) form.submit();
    });
    ['client-status-filter', 'client-type-filter'].forEach((id) => document.getElementById(id)?.addEventListener('change', () => table.ajax.reload()));
});
