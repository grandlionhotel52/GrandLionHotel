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

    @media (max-width: 767.98px) {
        main.container,
        main.container-fluid,
        main.container-xl {
            padding-left: .85rem !important;
            padding-right: .85rem !important;
        }

        .navbar > .container,
        .navbar > .container-fluid,
        .navbar > .container-xl {
            flex-wrap: wrap;
            padding-left: .85rem;
            padding-right: .85rem;
        }

        .navbar-brand {
            max-width: calc(100% - 58px);
            margin-right: .35rem;
            overflow: hidden;
        }

        .brand-wordmark {
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
        }

        .navbar-nav .dropdown-menu {
            max-width: 100%;
        }

        .modal-dialog {
            margin: .75rem;
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

        .admin-action-col,
        .staff-action-col {
            min-width: 0 !important;
        }

        .table-responsive {
            margin-bottom: .25rem;
            border-radius: .5rem;
        }
    }

    @media (max-width: 575.98px) {
        h1, .h1 { font-size: clamp(1.65rem, 8vw, 2.15rem); }
        h2, .h2 { font-size: clamp(1.4rem, 7vw, 1.85rem); }

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
            font-size: .72rem !important;
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
    });
</script>
