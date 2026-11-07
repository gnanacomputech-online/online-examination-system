(function () {
    'use strict';
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.display = 'none';
        }, 6000);
    });
})();
