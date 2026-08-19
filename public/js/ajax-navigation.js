(() => {
    if (window.GrandLionAjaxNavigation) {
        window.GrandLionAjaxNavigation.enhance(document);
        return;
    }

    const state = {
        controller: null,
        navigating: false,
    };

    const sameOrigin = (url) => url.origin === window.location.origin;

    const isModifiedClick = (event) =>
        event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

    const shouldHandleLink = (link, event) => {
        if (
            isModifiedClick(event)
            || link.hasAttribute('download')
            || link.hasAttribute('data-no-ajax')
            || link.target
            || link.closest('[data-no-ajax]')
        ) {
            return false;
        }

        const rawHref = link.getAttribute('href');
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) {
            return false;
        }

        const url = new URL(link.href, window.location.href);
        return sameOrigin(url) && ['http:', 'https:'].includes(url.protocol);
    };

    const shouldHandleForm = (form, event) => {
        if (
            event.defaultPrevented
            || form.hasAttribute('data-no-ajax')
            || form.closest('[data-no-ajax]')
            || form.target
        ) {
            return false;
        }

        const url = new URL(form.action || window.location.href, window.location.href);
        return sameOrigin(url);
    };

    const setBusy = (form, busy) => {
        form.setAttribute('aria-busy', busy ? 'true' : 'false');
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            if (busy) {
                button.dataset.ajaxWasDisabled = button.disabled ? '1' : '0';
                button.disabled = true;
                if (button instanceof HTMLButtonElement && !button.dataset.ajaxOriginalText) {
                    button.dataset.ajaxOriginalText = button.textContent;
                    button.textContent = button.dataset.loadingText || 'Processing…';
                }
            } else {
                button.disabled = button.dataset.ajaxWasDisabled === '1';
                if (button instanceof HTMLButtonElement && button.dataset.ajaxOriginalText) {
                    button.textContent = button.dataset.ajaxOriginalText;
                    delete button.dataset.ajaxOriginalText;
                }
                delete button.dataset.ajaxWasDisabled;
            }
        });
    };

    const setNavigating = (active) => {
        state.navigating = active;
        document.documentElement.classList.toggle('ajax-navigating', active);
        document.querySelector('[data-ajax-progress]')?.setAttribute('aria-hidden', active ? 'false' : 'true');
    };

    const runScripts = async (container, previouslyLoadedScripts) => {
        const scripts = Array.from(container.querySelectorAll('script'));

        for (const oldScript of scripts) {
            const source = oldScript.src ? new URL(oldScript.src, window.location.href).href : null;
            if (source && previouslyLoadedScripts.has(source)) {
                oldScript.remove();
                continue;
            }

            const script = document.createElement('script');
            Array.from(oldScript.attributes).forEach((attribute) => {
                script.setAttribute(attribute.name, attribute.value);
            });
            script.textContent = oldScript.textContent;

            const completed = source
                ? new Promise((resolve) => {
                    script.addEventListener('load', resolve, { once: true });
                    script.addEventListener('error', resolve, { once: true });
                })
                : Promise.resolve();

            oldScript.replaceWith(script);
            await completed;
        }
    };

    const renderHtml = async (html, finalUrl, addHistory) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        if (!parsed.body) {
            throw new Error('The server returned an invalid HTML document.');
        }

        const loadedScripts = new Set(
            Array.from(document.scripts)
                .filter((script) => script.src)
                .map((script) => new URL(script.src, window.location.href).href),
        );

        document.head.replaceWith(document.importNode(parsed.head, true));
        document.body.replaceWith(document.importNode(parsed.body, true));

        if (addHistory) {
            window.history.pushState({ ajax: true }, '', finalUrl);
        }

        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
        await runScripts(document.head, loadedScripts);
        await runScripts(document.body, loadedScripts);
        document.dispatchEvent(new CustomEvent('ajax:navigated', { detail: { url: finalUrl } }));
    };

    const downloadResponse = async (response) => {
        const blob = await response.blob();
        const disposition = response.headers.get('content-disposition') || '';
        const encodedName = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
        const plainName = disposition.match(/filename="?([^";]+)"?/i)?.[1];
        const name = encodedName ? decodeURIComponent(encodedName) : (plainName || 'download');
        const objectUrl = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = objectUrl;
        anchor.download = name;
        anchor.click();
        URL.revokeObjectURL(objectUrl);
    };

    const request = async (url, options = {}, addHistory = true) => {
        state.controller?.abort();
        state.controller = new AbortController();
        setNavigating(true);

        try {
            const response = await fetch(url, {
                ...options,
                credentials: 'same-origin',
                signal: state.controller.signal,
                headers: {
                    Accept: 'text/html, application/xhtml+xml',
                    ...(options.headers || {}),
                },
            });

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const payload = await response.json();
                if (payload.redirect) {
                    return request(payload.redirect, {}, addHistory);
                }
                document.dispatchEvent(new CustomEvent('ajax:response', { detail: payload }));
                return;
            }

            if (!contentType.includes('text/html') && !contentType.includes('application/xhtml+xml')) {
                await downloadResponse(response);
                return;
            }

            await renderHtml(await response.text(), response.url || url, addHistory);
        } finally {
            setNavigating(false);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');
        if (!link || !shouldHandleLink(link, event)) {
            return;
        }

        event.preventDefault();
        request(link.href).catch(() => {
            window.location.assign(link.href);
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const confirmation = form.getAttribute('data-confirm');
        if (confirmation && form.dataset.confirmed !== '1') {
            if (!window.confirm(confirmation)) {
                event.preventDefault();
                return;
            }
            form.dataset.confirmed = '1';
        }

        if (!shouldHandleForm(form, event)) {
            return;
        }

        if (form.dataset.ajaxSubmitting === '1') {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        form.dataset.ajaxSubmitting = '1';
        setBusy(form, true);

        const method = (form.method || 'GET').toUpperCase();
        const formData = new FormData(form, event.submitter || undefined);
        let url = new URL(form.action || window.location.href, window.location.href);
        const options = { method };

        if (method === 'GET') {
            url.search = new URLSearchParams(formData).toString();
        } else {
            options.body = formData;
        }

        request(url.href, options)
            .catch(() => {
                delete form.dataset.ajaxSubmitting;
                setBusy(form, false);
                form.submit();
            });
    });

    window.addEventListener('popstate', () => {
        request(window.location.href, {}, false).catch(() => window.location.reload());
    });

    window.GrandLionAjaxNavigation = {
        enhance: () => {},
        visit: (url) => request(url),
    };
})();
