document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cy-form').forEach(form => {
        const snapshot = () => JSON.stringify([...new FormData(form)].filter(([name]) => !['_token', '_method'].includes(name)));
        const saved = snapshot();
        form.querySelectorAll('[data-cytology-output]').forEach(link => link.addEventListener('click', event => {
            if (!form.reportValidity()) { event.preventDefault(); return; }
            if (snapshot() !== saved) {
                event.preventDefault();
                let notice = form.querySelector('[data-print-notice]');
                if (!notice) {
                    notice = document.createElement('p');
                    notice.dataset.printNotice = '';
                    notice.className = 'cy-alert error';
                    notice.setAttribute('role', 'alert');
                    form.prepend(notice);
                }
                notice.textContent = 'Tiene cambios sin guardar. Guarde y valide el informe antes de imprimirlo o descargarlo.';
                notice.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }));
    });
});
