<style>
    /* Shared safeguards for customer, admin, and staff screens. */
    html,
    body {
        max-width: 100%;
        overflow-x: clip;
    }

    img,
    svg,
    video,
    canvas {
        max-width: 100%;
        height: auto;
    }

    .row > *,
    .card,
    .card-body,
    .modal-content,
    .navbar-brand,
    .navbar-collapse {
        min-width: 0;
    }

    h1, h2, h3, h4, h5, h6,
    p, dd, td, th,
    .alert,
    .dropdown-item {
        overflow-wrap: anywhere;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-responsive > .table {
        margin-bottom: 0;
    }

    .btn-toolbar,
    .card-header,
    .card-footer,
    .page-actions,
    .action-buttons {
        flex-wrap: wrap;
    }

    .ux-back-to-top {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1040;
        width: 2.9rem;
        height: 2.9rem;
        display: inline-grid;
        place-items: center;
        border: 1px solid rgba(184, 146, 84, .55);
        border-radius: 50%;
        background: #172132;
        color: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .24);
        opacity: 0;
        visibility: hidden;
        transform: translateY(.6rem);
        transition: opacity .2s ease, transform .2s ease, visibility .2s ease, background-color .2s ease;
    }

    .ux-back-to-top.is-visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .ux-back-to-top:hover {
        background: #9a753f;
        color: #fff;
    }

    input,
    select,
    textarea,
    .form-control,
    .form-select,
    .input-group {
        max-width: 100%;
    }

    .mobile-nav-label {
        display: none;
    }

    .notification-count {
        position: absolute;
        top: 0;
        right: -0.35rem;
    }

    @media (max-width: 991.98px) {
        .navbar {
            padding-block: .35rem;
        }

        .navbar-toggler {
            width: 44px;
            height: 44px;
            display: inline-grid;
            place-items: center;
            flex: 0 0 44px;
            padding: 0;
            border-color: rgba(146, 113, 60, .35);
            border-radius: 12px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 5px 14px rgba(15, 23, 42, .08);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 .2rem rgba(184, 146, 84, .2);
        }

        .navbar-collapse,
        .navbar.home-nav-overlay .navbar-collapse {
            width: 100%;
            margin-top: .55rem;
            padding: .65rem;
            border: 1px solid rgba(184, 146, 84, .24);
            border-radius: 16px;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 16px 32px rgba(15, 23, 42, .12);
        }

        .navbar-nav {
            width: 100%;
            gap: .2rem;
        }

        .navbar-nav .nav-link {
            display: flex;
            align-items: center;
            min-height: 44px;
            padding: .68rem .8rem;
            border-radius: 10px;
        }

        .navbar-nav .dropdown-toggle {
            justify-content: space-between;
            width: 100%;
            text-align: left;
        }

        .navbar-nav .dropdown-toggle::after {
            margin-left: auto;
        }

        .mobile-nav-label {
            display: inline;
            margin-left: .55rem;
        }

        .notification-count {
            position: static;
            margin-left: auto;
            transform: none;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(184, 146, 84, .12);
        }

        .navbar-nav .nav-link.active::after {
            display: none;
        }

        .navbar-nav .dropdown-menu {
            position: static !important;
            width: 100%;
            margin: .15rem 0 .45rem;
            padding: .4rem;
            transform: none !important;
            border-color: rgba(184, 146, 84, .22);
            box-shadow: none !important;
        }

        .navbar-nav .dropdown-menu[style] {
            width: 100% !important;
            max-width: 100% !important;
        }

        .navbar-nav .dropdown-item {
            min-height: 42px;
            display: flex;
            align-items: center;
            border-radius: 9px;
        }

        .auth-cta-group,
        .admin-cta-group,
        .staff-cta-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: .5rem;
        }

        :where(.auth-cta-group, .admin-cta-group, .staff-cta-group) > *,
        :where(.auth-cta-group, .admin-cta-group, .staff-cta-group) form,
        :where(.auth-cta-group, .admin-cta-group, .staff-cta-group) button,
        :where(.auth-cta-group, .admin-cta-group, .staff-cta-group) a,
        :where(.auth-cta-group, .admin-cta-group, .staff-cta-group) span {
            width: 100%;
            min-width: 0;
        }
    }

    @media (max-width: 767.98px) {
        body {
            font-size: .95rem;
        }

        main.container,
        main.container-fluid,
        main.container-xl {
            padding-left: .85rem !important;
            padding-right: .85rem !important;
            padding-top: 1.25rem !important;
            padding-bottom: 1.75rem !important;
        }

        .navbar > .container,
        .navbar > .container-fluid,
        .navbar > .container-xl {
            flex-wrap: wrap;
            padding-left: .85rem;
            padding-right: .85rem;
        }

        .navbar-brand {
            width: calc(100% - 58px);
            max-width: calc(100% - 58px);
            flex: 0 1 calc(100% - 58px);
            margin-right: .35rem;
            overflow: hidden;
        }

        .brand-wordmark {
            display: block;
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .navbar-collapse {
            width: 100%;
            max-height: calc(100dvh - 76px);
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
        }

        main > :where(header, .page-header),
        main > .d-flex.justify-content-between {
            align-items: stretch !important;
            flex-direction: column;
        }

        main > :where(header, .page-header) :where(.btn, .ui-back-button),
        main > .d-flex.justify-content-between > :where(.btn, .ui-back-button) {
            width: 100%;
        }

        .row {
            --bs-gutter-x: 1rem;
        }

        .navbar-nav .dropdown-menu {
            max-width: 100%;
        }

        .modal-dialog {
            margin: .6rem;
        }

        .modal-content {
            max-height: calc(100dvh - 1.2rem);
            border-radius: 18px;
        }

        .modal-body {
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .modal-body,
        .modal-header,
        .modal-footer,
        .card-body,
        .card-header,
        .card-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .d-flex.gap-2:not(.navbar-brand):not(.input-group),
        .d-flex.gap-3:not(.navbar-brand):not(.input-group) {
            flex-wrap: wrap;
        }

        .form-control,
        .form-select {
            min-height: 46px;
            font-size: 16px;
        }

        input[type="date"],
        input[type="datetime-local"],
        input[type="time"] {
            min-width: 0;
        }

        textarea.form-control {
            min-height: 7rem;
        }

        .btn:not(.btn-close):not(.navbar-toggler),
        .ui-back-button {
            min-height: 44px;
        }

        .soft-card,
        .table-shell,
        .search-filter-shell {
            border-radius: 16px !important;
        }

        .nav-tabs,
        .nav-pills.mobile-scroll {
            flex-wrap: nowrap;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }

        .nav-tabs .nav-link,
        .nav-pills.mobile-scroll .nav-link {
            white-space: nowrap;
        }

        .admin-action-col,
        .staff-action-col {
            min-width: 0 !important;
        }

        .table-responsive {
            margin-bottom: .25rem;
            border: 1px solid rgba(184, 146, 84, .2);
            border-radius: 12px;
            background: #fff;
            scrollbar-color: #b89254 #eee7dc;
            scrollbar-width: thin;
        }

        .table-responsive::-webkit-scrollbar {
            height: 7px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #eee7dc;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #b89254;
        }

        .table > :not(caption) > * > * {
            padding: .72rem .65rem;
        }
    }

    @media (max-width: 575.98px) {
        h1, .h1 { font-size: clamp(1.65rem, 8vw, 2.15rem); }
        h2, .h2 { font-size: clamp(1.4rem, 7vw, 1.85rem); }

        h3, .h3 { font-size: clamp(1.2rem, 6vw, 1.5rem); }

        .brand-wordmark {
            font-size: .78rem !important;
            letter-spacing: .07em !important;
        }

        .card-body,
        .card-header,
        .card-footer {
            padding-left: .9rem;
            padding-right: .9rem;
        }

        .soft-card.p-4,
        .soft-card.p-5,
        .booking-shell.p-4,
        .booking-side-shell.p-4,
        .ops-booking-shell.p-4 {
            padding: 1rem !important;
        }

        .modal-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .modal-footer > :where(.btn, form),
        .modal-footer > form > .btn {
            width: 100%;
            margin: 0;
        }

        .page-actions,
        .action-buttons {
            display: grid !important;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .page-actions > *,
        .action-buttons > * {
            width: 100%;
        }

        .alert {
            padding: .85rem;
            border-radius: 12px;
        }

        main > :where(header, .page-header) {
            margin-bottom: 1rem !important;
        }

        .badge,
        .badge-status {
            white-space: normal;
            text-align: center;
        }

        .btn-group:not(.btn-group-sm) {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .btn-group:not(.btn-group-sm) > .btn {
            flex: 1 1 auto;
            border-radius: .375rem !important;
        }

        .pagination {
            flex-wrap: wrap;
            gap: .2rem;
        }

        .pagination .page-link {
            border-radius: .375rem;
            min-width: 40px;
            min-height: 40px;
            display: inline-grid;
            place-items: center;
        }

        .ux-back-to-top {
            right: .75rem;
            bottom: .75rem;
            width: 2.65rem;
            height: 2.65rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .ux-back-to-top { transition: none; }
    }

    @media (max-width: 389.98px) {
        .brand-wordmark {
            font-size: .7rem !important;
            letter-spacing: .06em !important;
        }

        .admin-brand-suffix,
        .staff-brand-suffix {
            display: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('main table.table').forEach((table) => {
            if (table.closest('.table-responsive')) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            wrapper.setAttribute('tabindex', '0');
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', table.getAttribute('aria-label') || 'Scrollable data table');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });

        document.querySelectorAll('.navbar-collapse').forEach((collapseElement) => {
            collapseElement.querySelectorAll('a:not(.dropdown-toggle)').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth >= 992 || !collapseElement.classList.contains('show')) return;

                    window.bootstrap?.Collapse.getOrCreateInstance(collapseElement).hide();
                });
            });
        });
    });
</script>
