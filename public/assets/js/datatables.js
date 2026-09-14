'use strict';

window.AppDataTable = {
    create(selector, options = {}) {
        const defaults = {
            processing: true,
            serverSide: true,
            searchDelay: 350,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            language: {
                processing: 'جارٍ التحميل...',
                search: 'بحث:',
                lengthMenu: 'عرض _MENU_ سجلات',
                info: 'عرض _START_ إلى _END_ من أصل _TOTAL_ سجل',
                infoEmpty: 'لا توجد سجلات',
                infoFiltered: '(منتقاة من _MAX_ سجل)',
                zeroRecords: 'لم يتم العثور على نتائج',
                emptyTable: 'لا توجد بيانات متاحة',
                paginate: { first: 'الأول', previous: 'السابق', next: 'التالي', last: 'الأخير' },
            },
        };
        return new window.DataTable(selector, { ...defaults, ...options });
    },
    escape(value) {
        const element = document.createElement('div');
        element.textContent = value == null ? '' : String(value);
        return element.innerHTML;
    },
};
