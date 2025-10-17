// Client-side validation using HTML5 Constraint Validation API

(function () {
    function getElement(id) {
        return document.getElementById(id);
    }

    function setError(container, message) {
        if (!container) return;
        container.textContent = message;
        container.hidden = !message;
    }

    // Standardized patterns
    const PASSWORD_PATTERN = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/; // 8+, upper, lower, number, special
    const USERNAME_OR_EMAIL = /^(?:[^\s@]+@[^\s@]+\.[^\s@]+|[A-Za-z0-9_\.\-]{3,50})$/; // email OR 3-50 allowed chars username

    function applyRegisterConstraints() {
        const username = getElement('username');
        const email = getElement('email');
        const password = getElement('password');
        const confirm = getElement('confirm_password');
        if (!username || !email || !password || !confirm) return;

        username.required = true;
        username.minLength = 3;
        username.maxLength = 50;

        email.required = true; // type=email enforces format

        password.required = true;
        password.minLength = 8;
        password.pattern = PASSWORD_PATTERN.source;

        confirm.required = true;

        const errorBox = getElement('registerError');

        function validatePasswordField() {
            if (!PASSWORD_PATTERN.test(password.value)) {
                password.setCustomValidity('Password must be 8+ chars and include uppercase, lowercase, number, and special character.');
            } else {
                password.setCustomValidity('');
            }
        }

        function validateConfirmField() {
            if (confirm.value !== password.value) {
                confirm.setCustomValidity('Passwords do not match.');
            } else {
                confirm.setCustomValidity('');
            }
        }

        password.addEventListener('input', function () {
            validatePasswordField();
            validateConfirmField();
            setError(errorBox, '');
        });
        confirm.addEventListener('input', function () {
            validateConfirmField();
            setError(errorBox, '');
        });

        const form = getElement('registerForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            validatePasswordField();
            validateConfirmField();
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                // surface top-level message if needed
                setError(errorBox, 'Please correct the highlighted fields.');
            } else {
                setError(errorBox, '');
            }
        });
    }

    function applyLoginConstraints() {
        const usernameOrEmail = getElement('username');
        const password = getElement('password');
        const form = getElement('loginForm');
        const errorBox = getElement('loginError');
        if (!usernameOrEmail || !password || !form) return;

        usernameOrEmail.required = true;
        password.required = true;
        password.minLength = 8;
        password.pattern = PASSWORD_PATTERN.source;

        usernameOrEmail.addEventListener('input', function () {
            if (!USERNAME_OR_EMAIL.test(usernameOrEmail.value)) {
                usernameOrEmail.setCustomValidity('Enter a valid email or a username (3-50 letters, numbers, _ . -).');
            } else {
                usernameOrEmail.setCustomValidity('');
            }
            setError(errorBox, '');
        });
        password.addEventListener('input', function () {
            if (!PASSWORD_PATTERN.test(password.value)) {
                password.setCustomValidity('Password must be 8+ chars with uppercase, lowercase, number, and special character.');
            } else {
                password.setCustomValidity('');
            }
            setError(errorBox, '');
        });

        form.addEventListener('submit', function (e) {
            // Trigger Constraint Validation API
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                setError(errorBox, 'Please correct the highlighted fields.');
            } else {
                setError(errorBox, '');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyRegisterConstraints();
        applyLoginConstraints();
    });
})();


