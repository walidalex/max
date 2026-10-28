'use strict';

window.AppSelect = {
    init(root = document) {
        root.querySelectorAll('select.js-tom-select:not([data-tom-select-ready]), input.js-tom-select:not([data-tom-select-ready])').forEach((element) => {
            if (element.tomselect) { return; }
            element.dataset.tomSelectReady = 'true';
            new window.TomSelect(element, {
                direction: 'rtl',
                create: false,
                allowEmptyOption: element.dataset.hideEmptyOption !== 'true',
                placeholder: element.dataset.placeholder || undefined,
            });
        });
    },
};
