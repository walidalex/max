'use strict';

window.AppAlert = {
    show(options = {}) {
        return window.Swal.fire({
            icon: options.type || 'info',
            title: options.title || '',
            text: options.text || '',
            confirmButtonText: options.confirmButtonText || 'حسنًا',
            reverseButtons: true,
        });
    },
    confirm(options = {}) {
        return window.Swal.fire({
            icon: 'warning',
            title: options.title || 'هل أنت متأكد؟',
            text: options.text || '',
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'متابعة',
            cancelButtonText: options.cancelButtonText || 'إلغاء',
            reverseButtons: true,
        });
    },
};
