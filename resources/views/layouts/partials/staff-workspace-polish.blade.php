<style>
    body {
        background:
            radial-gradient(circle at 8% 0%, rgba(var(--theme-primary-rgb), 0.11), transparent 28rem),
            linear-gradient(180deg, #fbf8f2 0, var(--staff-bg) 22rem, #f5f1e9 100%);
    }
    #main-content {
        padding-top: clamp(1.25rem, 2.5vw, 2rem) !important;
        padding-bottom: 3rem !important;
    }
    #main-content h1 {
        color: #172033;
        font-size: clamp(1.45rem, 2.2vw, 1.85rem) !important;
        line-height: 1.2;
    }
    #main-content h2,
    #main-content h3 { color: #202a3d; }

    .soft-card,
    .table-shell,
    :where(
        .ops-health-shell,
        .ops-queue-card,
        .ops-booking-shell,
        .ops-booking-table-shell,
        .arrivals-table-shell,
        .walkin-shell,
        .walkin-summary,
        .booking-shell,
        .booking-side-shell
    ) {
        border-color: rgba(var(--theme-primary-rgb), 0.3) !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.97) !important;
        box-shadow: 0 10px 28px rgba(var(--theme-ink-rgb), 0.08) !important;
    }
    :where(.ops-focus-card, .ops-health-item, .ops-summary-card, .arrivals-stat, .booking-log-card) {
        border-color: rgba(var(--theme-primary-rgb), 0.28) !important;
        border-radius: 15px !important;
        background: linear-gradient(145deg, #fff 0%, #fdfbf7 100%) !important;
        box-shadow: 0 7px 20px rgba(var(--theme-ink-rgb), 0.07) !important;
    }
    .ops-focus-card {
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease !important;
    }
    .ops-focus-card:hover {
        border-color: rgba(var(--theme-primary-rgb), 0.58) !important;
        box-shadow: 0 14px 30px rgba(var(--theme-ink-rgb), 0.11) !important;
        transform: translateY(-2px);
    }
    .walkin-section,
    .booking-info-item,
    .booking-next-step {
        border-color: rgba(var(--theme-primary-rgb), 0.25) !important;
        background: #fcfaf6 !important;
    }

    .table-shell { padding: 0 !important; }
    .table-responsive {
        overflow-x: auto;
        overflow-y: hidden;
        overscroll-behavior-inline: contain;
        scrollbar-color: rgba(var(--theme-primary-rgb), 0.65) rgba(var(--theme-primary-rgb), 0.1);
        scrollbar-width: thin;
    }
    .table-responsive::-webkit-scrollbar { height: 9px; }
    .table-responsive::-webkit-scrollbar-track { background: rgba(var(--theme-primary-rgb), 0.1); }
    .table-responsive::-webkit-scrollbar-thumb {
        border: 2px solid #fff;
        border-radius: 999px;
        background: rgba(var(--theme-primary-rgb), 0.72);
    }
    .table-responsive > .table {
        min-width: 720px;
        margin: 0 !important;
    }
    .table-responsive > .table:has(thead th:nth-child(7)) { min-width: 1120px; }
    .table-responsive > .table:has(thead th:nth-child(8)) { min-width: 1260px; }
    .table thead,
    .staff-table thead {
        background: linear-gradient(180deg, #fcfaf6 0%, #f7f2e9 100%);
    }
    .table thead th,
    .staff-table thead th {
        color: #536074;
        padding: 0.82rem 0.9rem !important;
        border-bottom-color: rgba(var(--theme-primary-rgb), 0.34) !important;
        vertical-align: middle;
        white-space: nowrap;
    }
    .table tbody td,
    .table tbody th,
    .staff-table tbody td,
    .staff-table tbody th {
        padding: 0.82rem 0.9rem !important;
        line-height: 1.45;
        word-break: normal;
        overflow-wrap: normal;
    }
    .table tbody tr,
    .staff-table tbody tr { transition: background-color 0.15s ease; }
    .table tbody tr:last-child > *,
    .staff-table tbody tr:last-child > * { border-bottom: 0; }
    .table tbody tr:hover > *,
    .staff-table tbody tr:hover > * { --bs-table-bg-state: rgba(var(--theme-primary-rgb), 0.075); }
    .table .btn { white-space: nowrap; }

    .form-control,
    .form-select {
        min-height: 44px;
        border-color: rgba(var(--theme-primary-rgb), 0.4);
        background-color: #fff;
        color: #202a3d;
    }
    textarea.form-control { min-height: 110px; }
    .form-control::placeholder {
        color: #8791a2;
        opacity: 1;
    }
    .btn-staff,
    .btn-staff-outline {
        border-radius: 11px;
        white-space: nowrap;
    }

    .modal-content {
        overflow: hidden;
        border: 1px solid rgba(var(--theme-primary-rgb), 0.35);
        border-radius: 18px;
        box-shadow: 0 24px 70px rgba(var(--theme-ink-rgb), 0.22);
    }
    .modal-header,
    .modal-footer {
        border-color: rgba(var(--theme-primary-rgb), 0.24);
        background: #fcfaf6;
    }
    .modal-header { padding: 1rem 1.2rem; }
    .modal-body { padding: 1.2rem; }
    .modal-footer {
        gap: 0.45rem;
        padding: 0.9rem 1.2rem;
    }

    .pagination {
        flex-wrap: wrap;
        gap: 0.3rem;
    }
    .page-link {
        min-width: 38px;
        border-color: rgba(var(--theme-primary-rgb), 0.32);
        border-radius: 9px !important;
        color: #354055;
        text-align: center;
    }
    .active > .page-link,
    .page-link.active {
        border-color: var(--staff-brand);
        background: var(--staff-brand);
        color: #fff;
    }

    @media (max-width: 1199.98px) {
        .navbar-collapse {
            max-height: calc(100vh - 76px);
            overflow-y: auto;
            padding: 0.75rem 0 0.35rem;
        }
        .navbar-nav {
            align-items: stretch !important;
            gap: 0.15rem !important;
        }
        .navbar .nav-link {
            border-radius: 9px;
            padding: 0.68rem 0.75rem;
        }
        .navbar .nav-link:hover,
        .navbar .nav-link.active { background: rgba(var(--theme-primary-rgb), 0.11); }
        .nav-link.active::after { display: none; }
        .staff-cta-wrap {
            width: 100%;
            margin-left: 0;
            padding-left: 0;
            border-left: 0;
        }
    }
    @media (max-width: 767.98px) {
        #main-content {
            padding-right: 0.85rem;
            padding-left: 0.85rem;
        }
        #main-content > .d-flex:first-child,
        #main-content > section:first-child > .d-flex:first-child,
        .ops-page-head,
        .ops-section-head { align-items: stretch !important; }
        #main-content > .d-flex:first-child > :last-child,
        #main-content > section:first-child > .d-flex:first-child > :last-child,
        .ops-page-head > :last-child { width: 100%; }
        #main-content > .d-flex:first-child .btn,
        #main-content > section:first-child > .d-flex:first-child .btn,
        .ops-page-head > :last-child.btn { width: 100%; }
        :where(
            .soft-card,
            .ops-health-shell,
            .ops-queue-card,
            .ops-booking-shell,
            .ops-booking-table-shell,
            .arrivals-table-shell,
            .walkin-shell,
            .walkin-summary,
            .booking-shell,
            .booking-side-shell
        ) { padding: 1rem !important; }
        .table thead th,
        .table tbody td,
        .table tbody th {
            padding-right: 0.75rem !important;
            padding-left: 0.75rem !important;
        }
        .booking-actions,
        .arrivals-actions,
        .ops-action-stack { flex-wrap: wrap !important; }
        .modal-dialog { margin: 0.65rem; }
    }
    @media (max-width: 575.98px) {
        .navbar-brand { max-width: calc(100vw - 84px); }
        .brand-wordmark {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .row.g-3 { --bs-gutter-y: 0.8rem; }
        .modal-footer .btn { flex: 1 1 100%; }
    }
</style>
