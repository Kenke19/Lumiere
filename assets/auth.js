document.addEventListener('DOMContentLoaded', () => {
    const flipContainer = document.querySelector('.form-flip');
    const body = document.body;

    function flipForm() {
        body.classList.add('form-animating');
        flipContainer.classList.toggle('flipped');
        setTimeout(() => body.classList.remove('form-animating'), 600);
    }
    document.querySelectorAll('button[data-action="flip"]').forEach(button => {
        button.addEventListener('click', flipForm);
    });

  // Password visibility toggle buttons
    document.querySelectorAll('button[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const inputId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Floating label functionality 
    document.querySelectorAll('.input-field').forEach(input => {
        input.addEventListener('focus', () => {
            const label = input.nextElementSibling;
            if (label) label.classList.add('text-indigo-500');
        });

        input.addEventListener('blur', () => {
            const label = input.nextElementSibling;
            if (label && !input.value) label.classList.remove('text-indigo-500');
        });

        if (input.value) {
            const label = input.nextElementSibling;
            if (label) label.classList.add('text-indigo-500');
        }
    });

    if (window.shouldFlipToRegister) {
        if (flipContainer && !flipContainer.classList.contains('flipped')) {
            flipContainer.classList.add('flipped');
        }
    }
});
