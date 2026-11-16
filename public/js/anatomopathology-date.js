document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ap-date-control').forEach((control) => {
        const input = control.querySelector('input[type="date"]');
        const button = control.querySelector('.ap-date-open');
        if (!input || !button || input.disabled || typeof input.showPicker !== 'function') return;

        const openCalendar = () => {
            try {
                input.showPicker();
            } catch {
                // Keep the native calendar available if the browser blocks the API.
                control.classList.remove('ap-date-enhanced');
                button.hidden = true;
                input.focus();
            }
        };
        control.classList.add('ap-date-enhanced');
        button.hidden = false;
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openCalendar();
        });
        input.addEventListener('click', openCalendar);
    });
});
