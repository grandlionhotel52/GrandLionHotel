<script>
    (() => {
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)
                || event.defaultPrevented
                || form.method.toLowerCase() === 'get'
                || form.hasAttribute('data-no-submit-lock')
                || form.hasAttribute('data-submit-lock')) {
                return;
            }

            if (form.dataset.submitting === '1') {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            form.dataset.submitting = '1';
            const submitButton = event.submitter instanceof HTMLElement
                ? event.submitter
                : form.querySelector('button[type="submit"], input[type="submit"]');

            window.setTimeout(() => {
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');
                });

                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.dataset.originalText ||= submitButton.textContent || '';
                    submitButton.textContent = submitButton.dataset.submittingText || 'Processing...';
                }
            }, 0);
        });
    })();
</script>
