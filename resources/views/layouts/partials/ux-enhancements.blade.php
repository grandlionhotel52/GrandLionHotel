<button class="ux-back-to-top" type="button" aria-label="Back to top" title="Back to top">
    <span aria-hidden="true">&#8593;</span>
</button>

<script>
    (() => {
        const ready = (callback) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback, { once: true });
            } else {
                callback();
            }
        };

        ready(() => {
            const backToTop = document.querySelector('.ux-back-to-top');
            const updateBackToTop = () => {
                backToTop?.classList.toggle('is-visible', window.scrollY > 600);
            };

            backToTop?.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                });
            });
            window.addEventListener('scroll', updateBackToTop, { passive: true });
            updateBackToTop();

            document.querySelectorAll('.is-invalid').forEach((field, index) => {
                field.setAttribute('aria-invalid', 'true');

                const feedback = field.parentElement?.querySelector('.invalid-feedback')
                    ?? field.closest('.mb-3, .col-12, [class*="col-"]')?.querySelector('.invalid-feedback');

                if (feedback) {
                    feedback.id ||= `field-error-${index + 1}`;
                    const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                    describedBy.add(feedback.id);
                    field.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
                }
            });

            const firstInvalid = document.querySelector('.is-invalid:not([type="hidden"]):not([disabled])');
            if (firstInvalid instanceof HTMLElement) {
                window.requestAnimationFrame(() => {
                    firstInvalid.focus({ preventScroll: true });
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }
        });
    })();
</script>
