<script>
    (() => {
        const forms = document.querySelectorAll('[data-ajax-list-form]');

        forms.forEach((form) => {
            const targetSelector = form.getAttribute('data-ajax-list-form');
            let requestController;
            let searchTimer;

            const getTarget = () => targetSelector ? document.querySelector(targetSelector) : null;

            const syncForm = (url) => {
                form.querySelectorAll('input[name], select[name]').forEach((control) => {
                    control.value = url.searchParams.get(control.name) ?? '';
                });
            };

            const load = async (url, updateHistory = true) => {
                const target = getTarget();
                if (!target) {
                    window.location.assign(url);
                    return;
                }

                requestController?.abort();
                requestController = new AbortController();
                const request = requestController;
                target.setAttribute('aria-busy', 'true');
                target.style.opacity = '0.55';

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: request.signal,
                    });
                    if (!response.ok) throw new Error(`Request failed with status ${response.status}`);

                    const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextTarget = documentCopy.querySelector(targetSelector);
                    if (!nextTarget) throw new Error('Updated list results were not found.');

                    target.innerHTML = nextTarget.innerHTML;
                    document.querySelectorAll('[data-ajax-list-sync][id]').forEach((currentElement) => {
                        const nextElement = documentCopy.getElementById(currentElement.id);
                        if (nextElement) currentElement.innerHTML = nextElement.innerHTML;
                    });
                    if (updateHistory) window.history.pushState({}, '', url);

                    target.querySelectorAll('.table-responsive').forEach((tableRegion) => {
                        tableRegion.setAttribute('tabindex', '0');
                        tableRegion.setAttribute('role', 'region');
                        tableRegion.setAttribute('aria-label', 'Scrollable data table');
                    });
                } catch (error) {
                    if (error.name !== 'AbortError') window.location.assign(url);
                } finally {
                    if (requestController === request) {
                        target.removeAttribute('aria-busy');
                        target.style.opacity = '';
                    }
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const url = new URL(form.action, window.location.origin);
                const params = new URLSearchParams(new FormData(form));
                params.delete('page');
                url.search = params.toString();
                load(url);
            });

            form.querySelectorAll('select, input[type="date"]').forEach((control) => {
                control.addEventListener('change', () => form.requestSubmit());
            });

            form.querySelectorAll('input[type="search"], input[data-ajax-search]').forEach((control) => {
                control.addEventListener('input', () => {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(() => form.requestSubmit(), 500);
                });
            });

            form.querySelectorAll('[data-ajax-list-reset]').forEach((resetLink) => {
                resetLink.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = new URL(resetLink.href);
                    syncForm(url);
                    load(url);
                });
            });

            document.addEventListener('click', (event) => {
                const paginationLink = event.target.closest(`${targetSelector} .pagination a`);
                if (!paginationLink) return;

                event.preventDefault();
                load(new URL(paginationLink.href));
            });

            window.addEventListener('popstate', () => {
                const url = new URL(window.location.href);
                syncForm(url);
                load(url, false);
            });
        });
    })();
</script>
