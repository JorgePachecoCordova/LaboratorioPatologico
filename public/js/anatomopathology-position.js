document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.ap-card > form, .access-result-form');
    const stateInput = form?.querySelector('[name="_viewport"]');
    const scroller = document.querySelector('.page-scroll');
    if (!form || !stateInput || !scroller) return;

    form.addEventListener('submit', (event) => {
        if (event.submitter?.value === 'print') { stateInput.value = ''; return; }
        stateInput.value = JSON.stringify({
            top: form.getBoundingClientRect().top,
            fields: [...form.querySelectorAll('textarea[name]')].map(field => ({
                name: field.name, top: field.scrollTop, height: field.getBoundingClientRect().height,
            })),
        });
    });

    let saved;
    try { saved = JSON.parse(stateInput.value || 'null'); } catch { return; }
    stateInput.value = '';
    if (!saved || !Number.isFinite(saved.top)) return;
    const restore = () => {
        for (const field of form.querySelectorAll('textarea[name]')) {
            const previous = saved.fields?.find(item => item.name === field.name);
            if (!previous) continue;
            if (Number.isFinite(previous.height) && previous.height > 0 && previous.height < 10000) {
                field.style.setProperty('height', `${previous.height}px`, 'important');
            }
            if (Number.isFinite(previous.top)) field.scrollTop = previous.top;
        }
        scroller.scrollTop += form.getBoundingClientRect().top - saved.top;
    };
    const ready = () => requestAnimationFrame(() => requestAnimationFrame(restore));
    if (document.readyState === 'complete') ready();
    else window.addEventListener('load', ready, { once: true });
});
