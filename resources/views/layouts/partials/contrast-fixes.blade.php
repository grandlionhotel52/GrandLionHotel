<style>
    :root {
        --accessible-brand-text: #172033;
        --accessible-brand-dark: #6f5228;
        --accessible-muted: #596273;
        --accessible-focus: #6f5228;
    }

    :where(a, button, input, select, textarea, [tabindex]):focus-visible {
        outline-color: var(--accessible-focus) !important;
        outline-style: solid;
        outline-width: 3px;
    }

    .text-secondary,
    .text-muted {
        color: var(--accessible-muted) !important;
    }

    .form-control::placeholder,
    textarea::placeholder {
        color: #667085;
        opacity: 1;
    }

    .form-control,
    .form-select {
        color: #202938;
        background-color: #fff;
    }

    .form-control:disabled,
    .form-select:disabled {
        color: #4b5563;
        background-color: #edf0f3;
        opacity: 1;
    }

    .btn-ta,
    .btn-staff,
    .auth-link-register,
    .admin-pill-logout,
    .staff-pill-logout,
    .gallery-filter.is-active {
        color: var(--accessible-brand-text) !important;
        font-weight: 800;
    }

    .btn-ta:hover,
    .btn-ta:focus-visible,
    .btn-staff:hover,
    .btn-staff:focus-visible,
    .auth-link-register:hover,
    .auth-link-register:focus-visible,
    .admin-pill-logout:hover,
    .admin-pill-logout:focus-visible,
    .staff-pill-logout:hover,
    .staff-pill-logout:focus-visible,
    .gallery-filter.is-active:hover,
    .gallery-filter.is-active:focus-visible {
        border-color: var(--accessible-brand-dark) !important;
        background: var(--accessible-brand-dark) !important;
        color: #fff !important;
    }

    .text-primary {
        color: var(--accessible-brand-dark) !important;
    }

    .text-bg-primary,
    .bg-primary {
        background-color: #c5a166 !important;
        color: var(--accessible-brand-text) !important;
    }

    .text-bg-warning,
    .bg-warning {
        background-color: #8a5c00 !important;
        color: #fff !important;
    }

    .alert-warning {
        color: #5f4300 !important;
    }

    a:not(.btn):not(.nav-link):not(.navbar-brand):not(.footer-link):not(.gallery-link) {
        text-underline-offset: 0.15em;
    }

    .invalid-feedback,
    .text-danger {
        color: #a51d16 !important;
    }

    @media (forced-colors: active) {
        :where(.btn-ta, .btn-staff, .auth-link-register, .admin-pill-logout, .staff-pill-logout) {
            border: 2px solid ButtonText;
        }
    }
</style>
