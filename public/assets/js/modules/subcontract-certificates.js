'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const tableElement = document.getElementById('certificates-table');
    const card = document.getElementById('certificates-card');
    if (!tableElement || !card || !window.AppDataTable) return;
    const escape = window.AppDataTable.escape;
    const statuses = {draft: 'مسودة', approved: 'معتمد', cancelled: 'ملغي'};
    const subcontractId = Number(card.dataset.subcontractId);
    const table = window.AppDataTable.create(tableElement, {
        order: [[2, 'desc']],
        ajax: {
            url: `/api/subcontracts/${subcontractId}/certificates`,
            data(data) { data.status = document.getElementById('certificate-status-filter').value; },
        },
        columns: [
            {data: 'certificate_code', render: (value, type, row) => type === 'display' ? `<a href="/subcontract-certificates/${Number(row.id)}">${escape(value)}</a>` : value},
            {data: 'certificate_number', render: value => escape(value || '—')},
            {data: 'certificate_date', render: escape},
            {data: 'period_from', render: (value, type, row) => escape(`${value || '—'} إلى ${row.period_to || '—'}`)},
            {data: 'previous_earned_value', render: value => escape(value ?? '—')},
            {data: 'current_earned_value', render: value => escape(value ?? '—')},
            {data: 'cumulative_earned_value', render: value => escape(value ?? '—')},
            {data: 'progress_percentage', render: value => `${escape(value ?? '0')}%`},
            {data: 'status', render: value => `<span class="badge bg-blue-lt">${statuses[value] || escape(value)}</span>`},
            {data: null, orderable: false, searchable: false, render: row => `<a class="btn btn-sm" href="/subcontract-certificates/${Number(row.id)}">عرض</a>`},
        ],
    });
    document.getElementById('certificate-status-filter').addEventListener('change', () => table.ajax.reload());
});
