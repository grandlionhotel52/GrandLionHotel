<script>
    (() => {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        const attachEmailValidation = (input, index) => {
            if (input.readOnly || input.disabled || input.type !== 'email') return;

            let validationAttempted = input.classList.contains('is-invalid');

            if (!input.id) input.id = `live-email-${index + 1}`;
            const feedback = document.createElement('small');
            feedback.id = `${input.id}-live-feedback`;
            feedback.className = 'form-text live-email-feedback';
            feedback.setAttribute('aria-live', 'polite');
            input.insertAdjacentElement('afterend', feedback);
            input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), feedback.id].filter(Boolean).join(' '));

            const validate = (fromUser = false) => {
                const value = input.value.trim();
                if (!fromUser && input.classList.contains('is-invalid')) return false;

                input.classList.remove('is-valid', 'is-invalid');
                feedback.classList.remove('text-success', 'text-danger');

                if (value === '') {
                    const validWhenEmpty = !input.required;
                    input.setCustomValidity(validWhenEmpty ? '' : 'Email address is required.');
                    if (fromUser && input.required) {
                        input.classList.add('is-invalid');
                        feedback.classList.add('text-danger');
                        feedback.textContent = 'Email address is required.';
                    } else {
                        feedback.textContent = '';
                    }
                    return validWhenEmpty;
                }

                if (value.length > 255) {
                    input.setCustomValidity('Email address is too long.');
                    input.classList.add('is-invalid');
                    feedback.classList.add('text-danger');
                    feedback.textContent = `Email is too long (${value.length}/255 characters).`;
                    return false;
                }

                if (!emailPattern.test(value)) {
                    input.setCustomValidity('Enter a complete email address like name@example.com.');
                    input.classList.add('is-invalid');
                    feedback.classList.add('text-danger');
                    feedback.textContent = 'Enter a complete email like name@example.com.';
                    return false;
                }

                input.setCustomValidity('');
                input.classList.add('is-valid');
                feedback.classList.add('text-success');
                feedback.textContent = 'Valid email address.';
                return true;
            };

            input.addEventListener('input', () => {
                if (validationAttempted) validate(true);
            });
            input.addEventListener('blur', () => {
                if (validationAttempted) validate(true);
            });
            input.addEventListener('invalid', () => {
                validationAttempted = true;
                validate(true);
            });
            input.form?.addEventListener('submit', (event) => {
                validationAttempted = true;
                if (!validate(true)) event.preventDefault();
            });
        };

        document.querySelectorAll('input[type="email"]').forEach(attachEmailValidation);

        document.querySelectorAll('[data-live-ph-phone]').forEach((input, index) => {
            const feedback = document.createElement('small');
            feedback.className = 'form-text';
            feedback.id = `live-phone-${index + 1}`;
            feedback.setAttribute('aria-live', 'polite');
            input.insertAdjacentElement('afterend', feedback);
            input.setAttribute('aria-describedby', feedback.id);

            const validate = () => {
                const withPlus = input.value.trim().startsWith('+');
                const digits = input.value.replace(/\D/g, '');
                input.value = withPlus ? `+${digits}` : digits;
                const expected = withPlus ? 12 : 11;
                const valid = /^(?:09\d{9}|\+639\d{9})$/.test(input.value);

                input.classList.remove('is-valid', 'is-invalid');
                feedback.classList.remove('text-success', 'text-danger');
                if (input.value === '') {
                    input.setCustomValidity('');
                    feedback.textContent = 'Use 09XXXXXXXXX or +639XXXXXXXXX.';
                    return true;
                }
                if (digits.length > expected) {
                    input.setCustomValidity('Phone number has too many digits.');
                    feedback.textContent = `Too many digits: ${digits.length}/${expected}.`;
                } else if (!valid) {
                    input.setCustomValidity('Complete a valid number starting with 09 or +639.');
                    feedback.textContent = `${digits.length}/${expected} digits entered. Use 09 or +639.`;
                } else {
                    input.setCustomValidity('');
                    input.classList.add('is-valid');
                    feedback.classList.add('text-success');
                    feedback.textContent = 'Valid phone number.';
                    return true;
                }
                input.classList.add('is-invalid');
                feedback.classList.add('text-danger');
                return false;
            };

            input.addEventListener('input', validate);
            input.addEventListener('blur', () => {
                validate();
                if (input.value !== '') input.reportValidity();
            });
            validate();
        });
    })();
</script>
