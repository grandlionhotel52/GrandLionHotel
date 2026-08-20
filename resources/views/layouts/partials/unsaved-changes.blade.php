<script>
    (() => {
        const message = 'You have unsaved changes. Do you really want to leave and discard them?';

        const editableSelector = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea';

        const formState = (form) => {
            const data = new FormData(form);
            const entries = Array.from(data.entries()).map(([key, value]) => [
                key,
                value instanceof File ? `${value.name}:${value.size}:${value.lastModified}` : String(value),
            ]);

            return JSON.stringify(entries);
        };

        const isProtectedForm = (form) => {
            if (!(form instanceof HTMLFormElement) || form.dataset.unsavedProtection === 'off') {
                return false;
            }

            return form.method.toLowerCase() !== 'get' && form.querySelector(editableSelector) !== null;
        };

        const captureBaseline = (form) => {
            if (!isProtectedForm(form)) return;
            form.dataset.initialFormState = formState(form);
            form.dataset.dirty = '0';
        };

        const updateDirtyState = (form) => {
            if (!isProtectedForm(form)) return;
            form.dataset.dirty = formState(form) === form.dataset.initialFormState ? '0' : '1';
        };

        const hasUnsavedChanges = (form) => isProtectedForm(form)
            && form.dataset.dirty === '1'
            && form.dataset.unsavedSubmitting !== '1';

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form').forEach(captureBaseline);

            document.querySelectorAll('.modal').forEach((modal) => {
                modal.addEventListener('shown.bs.modal', () => {
                    modal.querySelectorAll('form').forEach(captureBaseline);
                });

                modal.addEventListener('hide.bs.modal', (event) => {
                    const dirtyForm = Array.from(modal.querySelectorAll('form')).find(hasUnsavedChanges);
                    if (dirtyForm) {
                        if (!window.confirm(message)) {
                            event.preventDefault();
                            return;
                        }

                        modal.dataset.discardUnsaved = '1';
                    }
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    if (modal.dataset.discardUnsaved !== '1') return;
                    modal.querySelectorAll('form').forEach((form) => {
                        form.reset();
                        captureBaseline(form);
                    });
                    delete modal.dataset.discardUnsaved;
                });
            });
        });

        document.addEventListener('input', (event) => {
            if (event.target instanceof Element) updateDirtyState(event.target.closest('form'));
        });

        document.addEventListener('change', (event) => {
            if (event.target instanceof Element) updateDirtyState(event.target.closest('form'));
        });

        document.addEventListener('submit', (event) => {
            if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
            event.target.dataset.unsavedSubmitting = '1';
            event.target.dataset.dirty = '0';
        });

        window.addEventListener('beforeunload', (event) => {
            const dirtyForm = Array.from(document.querySelectorAll('form')).find(hasUnsavedChanges);
            if (!dirtyForm) return;

            event.preventDefault();
            event.returnValue = '';
        });
    })();
</script>
