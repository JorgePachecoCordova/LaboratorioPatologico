document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('patient_register_form');
    if (!form) return;
    const name = form.elements.namedItem('full_name');
    const phone = form.elements.namedItem('phone');
    const ci = form.elements.namedItem('identity_document');
    const validate = () => {
        const value = name.value.trim();
        const letters = value.match(/\p{L}/gu) || [];
        name.setCustomValidity(value && (!/^[\p{L}\p{M} .'’\-]+$/u.test(value) || letters.length < 2)
            ? 'Escriba los nombres y apellidos sin números. Se permiten tildes, ñ, apóstrofos y guiones.' : '');
        const number = phone.value.trim();
        const digits = number.replace(/\D/g, '');
        phone.setCustomValidity(number && (!/^\+?[0-9() .-]+$/.test(number) || digits.length < 6 || digits.length > 15)
            ? 'Ingrese un teléfono sin letras, con entre 6 y 15 dígitos.' : '');
        const identity = ci.value.trim();
        ci.setCustomValidity(identity && !/^[\p{L}\p{N}]+(?:[ ./\-][\p{L}\p{N}]+)*$/u.test(identity)
            ? 'Revise el CI: utilice letras, números o separadores de identificación.' : '');
    };
    form.addEventListener('input', validate);
    validate();
    form.addEventListener('submit', event => {
        validate();
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            form.reportValidity();
        }
    }, true);
});
