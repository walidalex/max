'use strict';

window.AppSelect = {
    init(root = document) {
        root.querySelectorAll('.js-tom-select:not([data-tom-select-ready])').forEach((element) => {
            element.dataset.tomSelectReady = 'true';
            new window.TomSelect(element, { direction: 'rtl', create: false, allowEmptyOption: true });
        });
    },
};
