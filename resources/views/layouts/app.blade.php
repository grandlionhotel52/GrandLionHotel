<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.name', 'The Grand Lion Hotel') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('brand/favicon-lion-transparent.png') }}?v=6">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}?v=5">
    <link rel="shortcut icon" href="{{ asset('brand/favicon-lion-transparent.png') }}?v=6">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #b89254;
            --brand-deep: #92713c;
            --ink: #1f2530;
            --muted: #6f7785;
            --line: #dfd4c3;
            --bg: #f7f3ec;
            --surface: #ffffff;
            --danger: #b42318;
            --success: #067647;
            --night: #101828;
            --font-main: 'Manrope', sans-serif;
        }
        body {
            font-family: var(--font-main);
            font-weight: 500;
            line-height: 1.6;
            letter-spacing: 0.003em;
            color: var(--ink);
            background-color: var(--bg);
            background-image:
                radial-gradient(circle at 7% 0%, rgba(184, 146, 84, 0.22), transparent 36%),
                radial-gradient(circle at 95% 18%, rgba(16, 24, 40, 0.08), transparent 32%),
                linear-gradient(180deg, #fdfbf7 0%, #f7f2e8 58%, #f2ebdf 100%);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        .skip-link {
            position: fixed;
            top: 0.75rem;
            left: 0.75rem;
            z-index: 2000;
            transform: translateY(-160%);
            border-radius: 10px;
            background: #101828;
            color: #fff;
            padding: 0.65rem 0.9rem;
            font-weight: 800;
            text-decoration: none;
        }
        .skip-link:focus {
            transform: translateY(0);
            color: #fff;
        }
        :where(a, button, input, select, textarea, [tabindex]):focus-visible {
            outline: 3px solid rgba(184, 146, 84, 0.55);
            outline-offset: 3px;
        }
        .form-label.is-required::after {
            content: " *";
            color: var(--danger);
        }
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        h1, h2, h3, h4, h5, .brand-serif {
            font-family: var(--font-main);
            font-weight: 800;
            letter-spacing: -0.018em;
            line-height: 1.14;
            color: #172132;
            text-wrap: balance;
        }
        h6 {
            font-family: var(--font-main);
            font-weight: 700;
            letter-spacing: 0.005em;
            color: #1b2536;
        }
        p {
            color: #445063;
            line-height: 1.65;
        }
        .display-4,
        .display-5,
        .display-6 {
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.08;
        }
        .site-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.08);
        }
        .navbar.home-nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1080;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.84) 100%) !important;
            border-bottom-color: rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 16px 30px rgba(16, 24, 40, 0.12);
        }
        .navbar-brand {
            font-family: var(--font-main);
            font-size: 1.62rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }
        .brand-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 1px 2px rgba(17, 24, 39, 0.28)) contrast(1.08) saturate(1.05);
            display: block;
            transform: scale(1.1);
            transform-origin: center;
        }
        .brand-wordmark {
            font-size: 1.16rem;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
            color: #1e2a3b;
        }
        @supports ((-webkit-background-clip: text) or (background-clip: text)) {
            .brand-wordmark {
                background: linear-gradient(115deg, #162033 0%, #b18a4d 48%, #162033 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
        }
        .nav-link {
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #283241;
            position: relative;
        }
        .nav-link.active,
        .nav-link:hover {
            color: #111827;
        }
        .auth-cta-wrap {
            margin-left: 0.65rem;
            padding-left: 0.9rem;
            border-left: 1px solid rgba(31, 41, 55, 0.16);
        }
        .auth-cta-group {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .auth-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 0.84rem;
            line-height: 1;
            font-weight: 800;
            text-decoration: none;
            padding: 0.58rem 0.9rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }
        .auth-link:hover {
            transform: translateY(-1px);
        }
        .auth-cta-group form {
            margin: 0;
        }
        .auth-link-signin {
            border: 1px solid #d8c7ad;
            color: #263345;
            background: rgba(255, 255, 255, 0.88);
        }
        .auth-link-signin:hover,
        .auth-link-signin.active {
            border-color: #b89254;
            background: #fff;
            color: #111827;
            box-shadow: 0 10px 22px rgba(184, 146, 84, 0.18);
        }
        .auth-link-register {
            border: 1px solid var(--brand);
            color: #fff;
            background: linear-gradient(140deg, #c8a364 0%, #ad8344 100%);
            box-shadow: 0 12px 24px rgba(160, 119, 51, 0.25);
        }
        .auth-link-register:hover,
        .auth-link-register.active {
            border-color: var(--brand-deep);
            background: linear-gradient(140deg, #b99153 0%, #966f39 100%);
            color: #fff;
            box-shadow: 0 14px 28px rgba(134, 98, 43, 0.3);
        }
        .nav-link.active::after {
            content: "";
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            bottom: 0.15rem;
            height: 2px;
            background: linear-gradient(90deg, #d0ae76, #a68449);
            border-radius: 999px;
        }
        .ta-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 0.72rem;
            font-weight: 800;
            color: #766546;
        }
        .ta-chip {
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            padding: 0.46rem 0.9rem;
            font-weight: 700;
            font-size: 0.84rem;
            color: #3e4654;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .soft-card {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--surface);
            box-shadow: 0 16px 32px rgba(14, 19, 31, 0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .soft-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 38px rgba(14, 19, 31, 0.1);
            border-color: #d2c2a8;
        }
        .result-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .result-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 38px rgba(14, 19, 31, 0.11);
            border-color: #d2c2a8;
        }
        .btn-ta {
            border-radius: 10px;
            border: 1px solid var(--brand);
            background: linear-gradient(135deg, #c8a364 0%, #b18a4d 100%);
            color: #fff;
            font-weight: 700;
            min-height: 42px;
            padding: 0.52rem 1rem;
            box-shadow: 0 7px 16px rgba(160, 119, 51, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .btn-ta:hover {
            border-color: var(--brand-deep);
            background: linear-gradient(135deg, #bb9658 0%, #9a753f 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 9px 20px rgba(134, 98, 43, 0.25);
        }
        .btn-ta-outline {
            border-radius: 10px;
            border: 1px solid #b9aa92;
            background: #fff;
            color: #1f2937;
            font-weight: 700;
            min-height: 42px;
            padding: 0.52rem 1rem;
        }
        .btn-ta-outline:hover {
            background: #1f2937;
            color: #fff;
            border-color: #1f2937;
        }
        :where(.btn-ta, .btn-ta-outline, .btn-outline-danger, .btn-secondary) {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            line-height: 1.15;
        }
        :where(.btn-ta, .btn-ta-outline, .btn-outline-danger, .btn-secondary).btn-sm {
            min-height: 34px;
            padding: 0.36rem 0.68rem;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        :where(.btn-ta, .btn-ta-outline, .btn-outline-danger, .btn-secondary):disabled,
        :where(.btn-ta, .btn-ta-outline, .btn-outline-danger, .btn-secondary).disabled {
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.6;
        }
        .price-tag {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.05;
        }
        .hotel-meta {
            color: #525e6f;
            font-size: 0.93rem;
        }
        .badge-status {
            border-radius: 999px;
            padding: 0.35rem 0.66rem;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .badge-status.available {
            background: rgba(6, 118, 71, 0.13);
            color: var(--success);
        }
        .badge-status.unavailable {
            background: rgba(180, 35, 24, 0.13);
            color: var(--danger);
        }
        .form-control,
        .form-select {
            border-radius: 14px;
            border-color: #d9cebb;
            padding-top: 0.62rem;
            padding-bottom: 0.62rem;
        }
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.22);
            border-color: rgba(184, 146, 84, 0.65);
        }
        .table {
            --bs-table-bg: transparent;
        }
        .table-shell {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .table thead th {
            color: #4b5563;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
            white-space: nowrap;
        }
        .table tbody td {
            vertical-align: middle;
            border-color: #efe9dd;
        }
        .footer-shell {
            background: linear-gradient(135deg, #111a2d 0%, #0f1728 100%);
        }
        .footer-link {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
        }
        .footer-link:hover {
            color: #fff;
        }
        .object-cover {
            object-fit: cover;
        }
        .flash-stack {
            display: grid;
            gap: 0.8rem;
            position: fixed;
            top: 5.75rem;
            right: max(1rem, calc((100vw - 1320px) / 2));
            width: min(430px, calc(100vw - 2rem));
            z-index: 1080;
            pointer-events: none;
        }
        .flash-card {
            border-radius: 18px;
            border: 1px solid #e4d8c8;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
            padding: 1rem 1.05rem;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            overflow: hidden;
            pointer-events: auto;
            animation: flash-card-in 0.32s cubic-bezier(0.22, 1, 0.36, 1);
        }
        @keyframes flash-card-in {
            from {
                opacity: 0;
                transform: translateY(-12px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .flash-card .flash-icon {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
            flex-shrink: 0;
        }
        .flash-card .flash-title {
            font-size: 0.7rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 0.3rem;
        }
        .flash-card .flash-body {
            flex: 1;
            min-width: 0;
            padding-top: 0.05rem;
            color: #344054;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .flash-card.success {
            border-color: rgba(6, 118, 71, 0.32);
            background: linear-gradient(180deg, rgba(244, 251, 247, 0.98) 0%, rgba(237, 248, 242, 0.98) 100%);
        }
        .flash-card.success .flash-icon {
            background: rgba(6, 118, 71, 0.14);
            color: var(--success);
        }
        .flash-card.success .flash-title {
            color: #075f3c;
        }
        .flash-card.error {
            border-color: rgba(180, 35, 24, 0.32);
            background: linear-gradient(180deg, rgba(254, 245, 245, 0.98) 0%, rgba(252, 237, 237, 0.98) 100%);
        }
        .flash-card.error .flash-icon {
            background: rgba(180, 35, 24, 0.14);
            color: var(--danger);
        }
        .flash-card.error .flash-title {
            color: #8f1d14;
        }
        .flash-close {
            opacity: 0.45;
            flex-shrink: 0;
            transform: scale(0.78);
            margin: -0.15rem -0.15rem 0 0;
        }
        .flash-close:hover {
            opacity: 1;
        }
        @media (max-width: 575.98px) {
            .flash-stack {
                top: 5rem;
                right: 0.75rem;
                width: calc(100vw - 1.5rem);
            }
            .flash-card {
                border-radius: 15px;
                padding: 0.85rem;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .flash-card {
                animation: none;
            }
        }
        @media (max-width: 991.98px) {
            .navbar.home-nav-overlay .navbar-collapse {
                margin-top: 0.5rem;
                padding: 0.6rem 0.7rem;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.96);
                box-shadow: 0 14px 24px rgba(16, 24, 40, 0.14);
            }
            .auth-cta-wrap {
                margin-left: 0;
                padding-left: 0;
                border-left: 0;
            }
            .auth-cta-group {
                width: 100%;
                margin-top: 0.45rem;
            }
            .auth-link {
                flex: 1 1 calc(50% - 0.5rem);
                padding-top: 0.7rem;
                padding-bottom: 0.7rem;
            }
            .brand-logo {
                width: 46px;
                height: 46px;
                transform: scale(1.1);
            }
            .brand-wordmark {
                font-size: 1.1rem;
            }
        }
    </style>
    @stack('head')
    @include('layouts.partials.contrast-fixes')
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to content</a>
    @php
        $isHomePage = request()->routeIs('home');
    @endphp
    <div class="site-shell">
        <nav class="navbar navbar-expand-lg navbar-light border-bottom sticky-top {{ $isHomePage ? 'home-nav-overlay' : '' }}">
            <div class="container-xl py-1">
                <a class="navbar-brand text-dark" href="{{ route('home') }}">
                    <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="brand-logo">
                    <span class="brand-wordmark">THE GRAND LION HOTEL</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}">Rooms</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('gallery') ? 'active' : '' }}" href="{{ route('gallery') }}">Gallery</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}" href="{{ route('blog.index') }}">Blog</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
                        @auth
                            @if(!auth()->user()->canManageBookings())
                                <li class="nav-item"><a class="nav-link {{ request()->routeIs('bookings.my') ? 'active' : '' }}" href="{{ route('bookings.my') }}">My Bookings</a></li>
                                <li class="nav-item dropdown">
                                    @php
                                        $recentNotifications = auth()->user()->notifications()->latest()->take(8)->get();
                                        $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
                                    @endphp
                                    <button class="nav-link position-relative border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Booking notifications">
                                        <i class="bi bi-bell"></i>
                                        @if($unreadNotificationCount > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                                        @endif
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-2 shadow" style="width: min(340px, 92vw)">
                                        <p class="small text-uppercase fw-bold text-secondary px-2 pt-1 mb-2">Booking updates</p>
                                        @forelse($recentNotifications as $notification)
                                            @php
                                                $notificationBooking = \App\Models\Booking::query()->find(data_get($notification->data, 'booking_id'));
                                            @endphp
                                            @if($notificationBooking)
                                                <div class="d-flex align-items-start gap-1 rounded px-1 py-1 {{ $notification->read_at ? '' : 'bg-light' }}">
                                                    <a class="dropdown-item rounded text-wrap py-2 flex-grow-1" href="{{ route('bookings.show', ['booking' => $notificationBooking, 'return_to' => request()->getRequestUri()]) }}">
                                                        <span class="d-flex align-items-center gap-2 small fw-bold">
                                                            @if(!$notification->read_at)<span class="rounded-circle bg-primary flex-shrink-0" style="width: .45rem; height: .45rem" aria-label="Unread"></span>@endif
                                                            {{ data_get($notification->data, 'message', 'Booking updated.') }}
                                                        </span>
                                                        <span class="d-block text-secondary" style="font-size: .72rem">{{ $notification->created_at->diffForHumans() }}{{ $notification->read_at ? ' · Read' : '' }}</span>
                                                    </a>
                                                    @if(!$notification->read_at)
                                                        <form method="POST" action="{{ route('notifications.read-one', $notification->id) }}" class="pt-2 pe-1">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-link text-secondary p-1" title="Mark as read" aria-label="Mark this notification as read"><i class="bi bi-check2" aria-hidden="true"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        @empty
                                            <p class="small text-secondary px-2 py-2 mb-0">No notifications yet.</p>
                                        @endforelse
                                        @if($unreadNotificationCount > 0)
                                            <form method="POST" action="{{ route('notifications.read') }}" class="px-2 pt-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-ta-outline w-100">Mark all as read</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endif
                            <li class="nav-item auth-cta-wrap">
                                <div class="auth-cta-group">
                                    <a class="auth-link auth-link-signin {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                                        {{ \Illuminate\Support\Str::limit(auth()->user()->name, 18) }}
                                    </a>
                                    @if(auth()->user()->isStaff())
                                        <a class="auth-link auth-link-signin {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">Staff</a>
                                    @endif
                                    @if(auth()->user()->isAdmin())
                                        <a class="auth-link auth-link-signin {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Admin</a>
                                    @endif
                                    <form method="POST" action="{{ route('logout') }}" data-confirm="Are you sure you want to log out?">
                                        @csrf
                                        <button class="auth-link auth-link-signin" type="submit">Logout</button>
                                    </form>
                                </div>
                            </li>
                        @else
                            <li class="nav-item auth-cta-wrap">
                                <div class="auth-cta-group">
                                    <a class="auth-link auth-link-signin {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Sign in</a>
                                    <a class="auth-link auth-link-register {{ request()->routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}">Create account</a>
                                </div>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main id="main-content" tabindex="-1" class="main-content container-xl {{ $isHomePage ? 'pt-0 pb-4 pb-lg-5' : 'py-4 py-lg-5' }}">
            @php
                $showGlobalErrors = $errors->any() && !request()->routeIs('register');
                $showGlobalStatus = session('status')
                    && !request()->routeIs('register')
                    && !request()->routeIs('home');
            @endphp
            @if($showGlobalStatus || $showGlobalErrors)
                <div class="flash-stack">
                    @if($showGlobalStatus)
                        <div class="flash-card success alert alert-dismissible fade show mb-0" role="alert">
                            <span class="flash-icon" aria-hidden="true">&#10003;</span>
                            <div class="flash-body">
                                <p class="flash-title mb-1">Success</p>
                                <p class="mb-0">{{ session('status') }}</p>
                            </div>
                            <button type="button" class="btn-close flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($showGlobalErrors)
                        <div class="flash-card error alert alert-dismissible fade show mb-0" role="alert" aria-live="assertive">
                            <span class="flash-icon" aria-hidden="true"><i class="bi bi-exclamation-lg"></i></span>
                            <div class="flash-body">
                                <p class="flash-title mb-1">Action Needed</p>
                                @foreach($errors->all() as $error)
                                    <p class="{{ $loop->last ? 'mb-0' : 'mb-1' }}">{{ $error }}</p>
                                @endforeach
                            </div>
                            <button type="button" class="btn-close flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="footer-shell border-top py-4">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col-12 col-md-4 text-center text-md-start mb-2 mb-md-0">
                        <div class="small text-white-50">
                            <strong class="text-white">The Grand Lion Hotel</strong> <span class="ms-1">Premium digital hotel reservation platform.</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-center mb-2 mb-md-0">
                        <div class="d-flex gap-3 small justify-content-center">
                            <a class="footer-link" href="{{ route('about') }}">About</a>
                            <a class="footer-link" href="{{ route('terms') }}">Terms</a>
                            <a class="footer-link" href="{{ route('blog.index') }}">Blog</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-center text-md-end">
                        <div class="small text-white-50">
                            &copy; {{ now()->year }} The Grand Lion Hotel
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            document.querySelectorAll('.nav-link.active').forEach((link) => {
                link.setAttribute('aria-current', 'page');
            });

            document.querySelectorAll('.table-responsive').forEach((tableRegion) => {
                tableRegion.setAttribute('tabindex', '0');
                tableRegion.setAttribute('role', 'region');
                tableRegion.setAttribute('aria-label', 'Scrollable data table');
            });

            document.querySelectorAll('label.form-label').forEach((label, index) => {
                let control = label.htmlFor ? document.getElementById(label.htmlFor) : null;
                control ??= label.parentElement?.querySelector('input, select, textarea');

                if (!control) {
                    return;
                }

                if (!control.id) {
                    control.id = `form-field-${index + 1}`;
                }

                label.htmlFor = control.id;
                if (control.required && !label.textContent.includes('*')) {
                    label.classList.add('is-required');
                }
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                const message = form.getAttribute('data-confirm');
                if (!message) {
                    return;
                }

                if (form.dataset.confirmed === '1') {
                    return;
                }

                if (!window.confirm(message)) {
                    event.preventDefault();
                    return;
                }

                form.dataset.confirmed = '1';
            });
        })();
    </script>
    @include('layouts.partials.history-refresh')
    @stack('scripts')
    @include('layouts.partials.image-fallback')
</body>
</html>
