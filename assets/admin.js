// Forms with data-confirm ask first, with the admin's styled dialog when the core has it (5.2+).
document.addEventListener('submit', function (e) {
    "use strict";
    var form = e.target;
    if (!form.hasAttribute || !form.hasAttribute('data-confirm') || form.ipConfirmed) {
        return;
    }
    e.preventDefault();
    var text = form.getAttribute('data-confirm');
    var ask = typeof (window.ipAdminConfirm) === 'function'
        ? window.ipAdminConfirm({text: text, confirmLabel: form.getAttribute('data-confirm-button') || undefined, danger: true})
        : Promise.resolve(window.confirm(text));
    ask.then(function (ok) {
        if (ok) {
            form.ipConfirmed = true;
            form.submit();
        }
    });
}, true);
