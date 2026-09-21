'use strict';
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-payment-form').forEach((form) => {
        const channel = form.querySelector('.js-payment-channel');
        const bankBlock = form.querySelector('.js-bank-method');
        const bankMethod = form.querySelector('[name="bank_payment_method"]');
        const account = form.querySelector('.js-payment-account');
        const reference = form.querySelector('.js-reference');
        const referenceLabel = form.querySelector('.js-reference-label');
        const chequeBlocks = form.querySelectorAll('.js-cheque');
        const options = [...account.options];
        const sync = () => {
            const isBank = channel.value === 'bank';
            bankBlock.hidden = !isBank;
            bankMethod.required = isBank;
            reference.required = isBank;
            referenceLabel.classList.toggle('required', isBank);
            options.forEach((option) => { if (option.value) option.hidden = option.dataset.channel !== channel.value; });
            if (account.selectedOptions[0]?.dataset.channel !== channel.value) account.value = '';
            const isCheque = isBank && bankMethod.value === 'cheque';
            chequeBlocks.forEach((block) => { block.hidden = !isCheque; });
            form.querySelector('[name="cheque_number"]').required = isCheque;
            form.querySelector('[name="cheque_date"]').required = isCheque;
        };
        channel.addEventListener('change', sync);
        bankMethod.addEventListener('change', sync);
        sync();
    });
});
