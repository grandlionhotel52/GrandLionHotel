@extends('layouts.app')

@section('title', 'Home')

@push('head')
    <style>
        .hero-bleed {
            position: relative;
            left: 50%;
            right: 50%;
            width: 100vw;
            margin-left: -50vw;
            margin-right: -50vw;
        }

        .hero-bleed .hero-carousel {
            border-radius: 0;
            border: 0;
            box-shadow: none;
            position: relative;
        }
        .hero-carousel .carousel-item,
        .hero-carousel .hero-image {
            height: min(82vh, 760px);
        }
        .hero-carousel .hero-image {
            object-fit: cover;
        }
        .hero-fixed-content {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5rem 5% 4rem;
            text-align: center;
            pointer-events: none;
        }
        .hero-fixed-copy {
            width: min(1180px, 100%);
            pointer-events: auto;
        }
        .hero-fixed-copy h1 {
            max-width: 760px;
            margin-inline: auto;
            text-wrap: balance;
        }
        .hero-fixed-copy > p:not(.ta-eyebrow) {
            max-width: 680px;
            margin-inline: auto;
        }
        .hero-carousel .carousel-indicators {
            z-index: 3;
        }
        .hero-carousel .carousel-control-prev,
        .hero-carousel .carousel-control-next {
            z-index: 3;
        }
        .hero-carousel .ta-eyebrow {
            color: rgba(255, 255, 255, 0.85);
        }
        .hero-carousel .carousel-control-prev,
        .hero-carousel .carousel-control-next {
            width: auto;
            top: 50%;
            bottom: auto;
            transform: translateY(-50%);
            opacity: 1;
        }
        .hero-carousel .carousel-control-prev {
            left: 1.4rem;
        }
        .hero-carousel .carousel-control-next {
            right: 1.4rem;
        }
        .hero-carousel .carousel-control-prev-icon,
        .hero-carousel .carousel-control-next-icon {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.48);
            background-color: rgba(16, 24, 40, 0.42);
            background-size: 1rem 1rem;
            backdrop-filter: blur(2px);
        }
        .hero-carousel .carousel-control-prev:hover .carousel-control-prev-icon,
        .hero-carousel .carousel-control-next:hover .carousel-control-next-icon {
            background-color: rgba(16, 24, 40, 0.62);
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(8, 15, 25, 0.34) 0%, rgba(8, 15, 25, 0.62) 58%, rgba(8, 15, 25, 0.72) 100%);
            pointer-events: none;
            z-index: 1;
        }
        .hero-search {
            display: grid;
            grid-template-columns: minmax(220px, 1.35fr) minmax(170px, 0.8fr) minmax(170px, 0.8fr) auto;
            gap: 0.65rem;
            margin-top: 1.4rem;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 20px 48px rgba(4, 10, 20, 0.28);
            backdrop-filter: blur(8px);
            text-align: left;
        }
        .hero-search-field {
            position: relative;
        }
        .hero-search-field i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            z-index: 1;
            color: #8a6b3d;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .hero-date-label {
            position: absolute;
            top: 50%;
            left: 2.6rem;
            z-index: 2;
            color: #667085;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .hero-search .form-control {
            min-height: 58px;
            padding-left: 2.6rem;
            border-color: #ded4c4;
            background-color: #fff;
        }
        .hero-date-field .form-control {
            color: transparent;
        }
        .hero-date-field.has-value .hero-date-label {
            display: none;
        }
        .hero-date-field.has-value .form-control {
            color: inherit;
        }
        .hero-search .form-control:focus {
            border-color: #b89254;
            box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.16);
        }
        .hero-search .btn {
            min-width: 170px;
            min-height: 58px;
        }
        .home-welcome-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid rgba(184, 146, 84, 0.34);
            background: rgba(255, 255, 255, 0.94);
            color: #243446;
            border-radius: 999px;
            padding: 0.38rem 0.82rem;
            font-size: 0.84rem;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(16, 24, 40, 0.08);
        }
        .home-welcome-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: #b89254;
            display: inline-block;
        }
        .home-section-heading {
            max-width: 660px;
        }
        .home-gallery-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr 1fr;
            grid-template-rows: repeat(2, 180px);
            gap: 0.75rem;
        }
        .home-gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            min-height: 180px;
            box-shadow: 0 12px 26px rgba(16, 24, 40, 0.12);
        }
        .home-gallery-item:first-child { grid-row: 1 / 3; }
        .home-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }
        .home-gallery-item:hover img { transform: scale(1.04); }
        .home-gallery-item:focus-visible {
            outline: 3px solid #b89254;
            outline-offset: 3px;
        }
        .home-gallery-caption {
            position: absolute;
            inset: auto 0 0;
            padding: 2.4rem 1rem 0.85rem;
            color: #fff;
            font-weight: 700;
            background: linear-gradient(transparent, rgba(10, 18, 28, 0.78));
        }
        .home-gallery-caption small {
            display: block;
            color: rgba(255, 255, 255, 0.78);
            font-weight: 500;
        }
        .home-review-card,
        .home-nearby-card {
            height: 100%;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            padding: 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .home-review-card:hover,
        .home-nearby-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(16, 24, 40, 0.1);
        }
        .home-stars { color: #b88432; letter-spacing: 0.08em; }
        .home-review-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            background: #f8efe1;
            color: #87662f;
        }
        .home-promo {
            overflow: hidden;
            border-radius: 22px;
            background: linear-gradient(125deg, #243446 0%, #354c62 55%, #b89254 150%);
            color: #fff;
            box-shadow: 0 20px 42px rgba(25, 37, 51, 0.18);
        }
        .home-promo-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.35rem 0.7rem;
            color: rgba(255, 255, 255, 0.86);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .home-location-panel {
            border-radius: 22px;
            border: 1px solid var(--line);
            background: linear-gradient(145deg, #f7efe2, #fff);
        }
        .home-location-icon {
            width: 3rem;
            height: 3rem;
            display: inline-grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(184, 146, 84, 0.16);
            color: #8a682f;
            font-size: 1.35rem;
        }
        .home-location-note {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            color: #765a2e;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .home-faq .accordion-item {
            border: 1px solid var(--line);
            border-radius: 14px !important;
            overflow: hidden;
            margin-bottom: 0.65rem;
        }
        .home-faq .accordion-button { font-weight: 700; }
        .home-faq .accordion-button:not(.collapsed) {
            color: #6f5227;
            background: #fbf5eb;
            box-shadow: none;
        }
        .home-faq-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-radius: 16px;
            background: #243446;
            color: #fff;
            padding: 1rem 1.25rem;
        }
        .category-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            height: 100%;
            min-height: 88px;
            padding: 0.85rem 1rem;
        }
        .category-link .category-count {
            min-width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(184, 146, 84, 0.14);
            color: #7a5f33;
            font-size: 0.78rem;
            font-weight: 800;
        }
        .home-stat-strip {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.78);
            padding: 0.85rem 1rem;
        }
        .home-stat-strip .stat-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--ink);
        }
        .home-filter-link {
            padding: 0.42rem 0.72rem;
            font-size: 0.78rem;
            border-radius: 10px;
        }
        .feature-item {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            padding: 1rem;
            height: 100%;
        }
        .step-count {
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(184, 146, 84, 0.16);
            color: #7a5f33;
            font-weight: 800;
            font-size: 0.82rem;
        }
        @media (max-width: 991.98px) {
            .hero-carousel .carousel-item,
            .hero-carousel .hero-image {
                height: 560px;
            }
            .hero-fixed-content {
                padding-inline: 6%;
            }
            .hero-carousel h1 {
                font-size: 1.85rem;
            }
            .hero-search {
                grid-template-columns: 1fr 1fr;
            }
            .hero-search-field:first-of-type,
            .hero-search .btn {
                grid-column: 1 / -1;
            }
        }
        @media (max-width: 575.98px) {
            .home-gallery-grid {
                grid-template-columns: 1fr;
                grid-template-rows: none;
            }
            .home-gallery-item:first-child { grid-row: auto; }
            .home-faq-footer {
                align-items: stretch;
                flex-direction: column;
            }
            .hero-carousel .carousel-item,
            .hero-carousel .hero-image {
                height: 610px;
            }
            .hero-fixed-content {
                align-items: flex-end;
                padding: 4.5rem 1rem 3.5rem;
            }
            .hero-fixed-copy .ta-eyebrow {
                font-size: 0.72rem;
            }
            .hero-fixed-copy > p:not(.ta-eyebrow) {
                font-size: 0.92rem;
            }
            .hero-search {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                margin-top: 1rem;
                padding: 0.65rem;
            }
            .hero-search-field:first-of-type,
            .hero-search .btn {
                grid-column: auto;
            }
            .hero-search .form-control,
            .hero-search .btn {
                min-height: 50px;
            }
            .hero-carousel .carousel-control-prev {
                left: 0.8rem;
            }
            .hero-carousel .carousel-control-next {
                right: 0.8rem;
            }
            .hero-carousel .carousel-control-prev-icon,
            .hero-carousel .carousel-control-next-icon {
                width: 2.45rem;
                height: 2.45rem;
            }
        }
    </style>
@endpush

@section('content')
    @if(session('account_created_name'))
        <div class="d-flex justify-content-end mb-3">
            <div class="home-welcome-note">
                <span class="home-welcome-dot" aria-hidden="true"></span>
                Account created successfully. Welcome, {{ \Illuminate\Support\Str::limit(session('account_created_name'), 24) }}.
            </div>
        </div>
    @endif

    <section class="hero-bleed mb-4 mb-lg-5">
        <div id="homeHeroCarousel" class="carousel slide hero-carousel soft-card overflow-hidden" data-bs-ride="carousel" data-bs-interval="2500">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Bright premium hotel bedroom">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Comfortable modern hotel room interior">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Elegant hotel suite bedroom interior">
                </div>
            </div>
            <div class="hero-overlay"></div>
            <div class="hero-fixed-content">
                <div class="hero-fixed-copy">
                    <p class="ta-eyebrow text-light mb-2">Welcome to The Grand Lion Hotel</p>
                    <h1 class="display-5 text-white mb-2">Find your perfect stay</h1>
                    <p class="text-light mb-0">Choose your dates and check available rooms.</p>

                    <form id="homeAvailabilitySearch" method="GET" action="{{ route('rooms.index') }}" class="hero-search">
                        <input type="hidden" name="available_only" value="1">
                        <div class="hero-search-field">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <label class="visually-hidden" for="heroRoomType">Room type or view</label>
                            <input
                                id="heroRoomType"
                                type="search"
                                name="type"
                                class="form-control"
                                placeholder="Room type or view"
                                autocomplete="off"
                            >
                        </div>
                        <div class="hero-search-field hero-date-field">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <label class="hero-date-label" for="heroCheckIn">Check-in</label>
                            <input
                                id="heroCheckIn"
                                type="date"
                                name="check_in"
                                class="form-control"
                                min="{{ now()->toDateString() }}"
                                required
                            >
                        </div>
                        <div class="hero-search-field hero-date-field">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            <label class="hero-date-label" for="heroCheckOut">Check-out</label>
                            <input
                                id="heroCheckOut"
                                type="date"
                                name="check_out"
                                class="form-control"
                                min="{{ now()->addDay()->toDateString() }}"
                                required
                            >
                        </div>
                        <button class="btn btn-ta" type="submit">Check availability</button>
                    </form>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    @php
        $hasSignedInAccess = auth('customer')->check()
            || auth('admin')->check()
            || auth('staff')->check();
    @endphp

        <section class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <p class="ta-eyebrow mb-1">Categories</p>
                    <h2 class="mb-0">Browse by Room Type</h2>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline home-filter-link">
                    <i class="bi bi-sliders me-1" aria-hidden="true"></i>More filters
                </a>
            </div>
            <div class="row g-2">
                @forelse($roomCategories as $category)
                    <div class="col-6 col-md-4 col-xl-2">
                        <a
                            href="{{ route('rooms.index', ['type' => $category->type, 'available_only' => 1]) }}"
                            class="soft-card result-card category-link"
                        >
                            <div>
                                <h3 class="h6 mb-1">{{ $category->type }}</h3>
                                <p class="text-secondary small mb-0">{{ $category->total }} available</p>
                            </div>
                            <span class="category-count" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info soft-card border-0 mb-0">Room categories will appear here once rooms are added.</div>
                    </div>
                @endforelse
            </div>
        </section>

    <section class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="ta-eyebrow mb-1">Featured</p>
            <h2 class="mb-0">Top Room Picks</h2>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline home-filter-link">
            View all rooms<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </section>

    <section class="row g-4">
        @forelse($featuredRooms as $room)
            <div class="col-md-6 col-xl-4">
                <article class="soft-card h-100 result-card overflow-hidden">
                    <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="w-100 object-cover" style="height: 220px;">
                    <div class="p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h3 class="h5 mb-1">{{ $room->name }}</h3>
                                <p class="hotel-meta mb-0">
                                    {{ $room->type }}
                                    @if(filled($room->view_type))
                                        &middot; {{ $room->view_type }}
                                    @endif
                                    &middot; Standard occupancy: 2 guests
                                </p>
                            </div>
                            <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">
                                {{ $room->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                        <p class="text-secondary small mb-3">{{ \Illuminate\Support\Str::limit($room->description ?: 'Comfortable room with practical amenities and easy booking.', 95) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="price-tag">&#8369;{{ \App\Support\Money::display($room->price_per_night) }}</div>
                                <small class="text-secondary">/ night</small>
                            </div>
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-ta btn-sm">View details</a>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info soft-card border-0">No rooms are available yet. Add rooms from the admin dashboard.</div>
            </div>
        @endforelse
    </section>

    @if($hasSignedInAccess)
        <section class="home-stat-strip mt-4">
            <div class="row g-3 text-center">
                <div class="col-4">
                    <span class="stat-value d-block">{{ $platformStats['total_rooms'] }}</span>
                    <span class="small text-secondary">Rooms</span>
                </div>
                <div class="col-4 border-start border-end">
                    <span class="stat-value d-block">{{ $platformStats['available_rooms'] }}</span>
                    <span class="small text-secondary">Available</span>
                </div>
                <div class="col-4">
                    <span class="stat-value d-block">
                        {{ !is_null($platformStats['starting_price']) ? '₱'.number_format((float) $platformStats['starting_price'], 0) : '--' }}
                    </span>
                    <span class="small text-secondary">Starting rate</span>
                </div>
            </div>
        </section>
    @endif

    <section class="mt-5" aria-labelledby="homeGalleryTitle">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div class="home-section-heading">
                <p class="ta-eyebrow mb-1">A Look Inside</p>
                <h2 id="homeGalleryTitle" class="mb-1">Explore The Grand Lion</h2>
                <p class="text-secondary mb-0">Preview comfortable rooms and thoughtfully prepared spaces before your stay.</p>
            </div>
            <a href="{{ route('gallery') }}" class="btn btn-ta-outline">View full gallery</a>
        </div>
        <div class="home-gallery-grid">
            @forelse($featuredRooms->take(5) as $room)
                <a href="{{ route('rooms.show', $room) }}" class="home-gallery-item">
                    <img src="{{ $room->image_url }}" alt="{{ $room->name }} hotel room" loading="lazy">
                    <span class="home-gallery-caption">
                        {{ $room->name }}
                        <small>{{ $room->type }}{{ filled($room->view_type) ? ' · '.$room->view_type : '' }}</small>
                    </span>
                </a>
            @empty
                <div class="soft-card p-4 text-secondary">Gallery photos will appear when rooms are available.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-5" aria-labelledby="homeReviewsTitle">
        <div class="home-section-heading mb-3">
            <p class="ta-eyebrow mb-1">Guest Experience</p>
            <h2 id="homeReviewsTitle" class="mb-1">What Guests Value Most</h2>
            <p class="text-secondary mb-0">The qualities our reservation and service experience is designed around.</p>
        </div>
        <div class="row g-3">
            @foreach([
                ['icon' => 'bi-stars', 'title' => 'Clean, restful rooms', 'text' => 'Well-prepared spaces, comfortable bedding, and clearly presented room details.'],
                ['icon' => 'bi-lightning-charge', 'title' => 'Simple reservations', 'text' => 'Transparent rates, live date checks, and a straightforward booking flow.'],
                ['icon' => 'bi-headset', 'title' => 'Responsive support', 'text' => 'Helpful assistance for arrivals, payments, and changes to an upcoming stay.'],
            ] as $review)
                <div class="col-md-4">
                    <article class="home-review-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="home-review-icon"><i class="bi {{ $review['icon'] }}" aria-hidden="true"></i></span>
                            <span class="home-stars" aria-label="Five out of five stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        </div>
                        <h3 class="h5">{{ $review['title'] }}</h3>
                        <p class="text-secondary mb-0">{{ $review['text'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <section class="home-promo p-4 p-lg-5 mt-5" aria-labelledby="homePromoTitle">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="ta-eyebrow text-light mb-2">Seasonal Offers</p>
                @if($currentPromotion)
                    <h2 id="homePromoTitle" class="text-white mb-2">Save {{ App\Support\Money::display($currentPromotion->discount_percent) }}% on {{ $currentPromotion->room?->name ?? 'selected rooms' }}</h2>
                    <p class="text-white-50 mb-0">Special rates apply for stays from {{ $currentPromotion->discount_date_start->format('M d') }} to {{ $currentPromotion->discount_date_end->format('M d, Y') }}. Availability is limited.</p>
                @else
                    <h2 id="homePromoTitle" class="text-white mb-2">Seasonal rates for your next escape</h2>
                    <p class="text-white-50 mb-0">Explore available dates to see the latest room rates and newly added hotel offers.</p>
                @endif
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="home-promo-chip"><i class="bi bi-shield-check"></i> Secure booking</span>
                    <span class="home-promo-chip"><i class="bi bi-calendar-check"></i> Live availability</span>
                    <span class="home-promo-chip"><i class="bi bi-receipt"></i> Clear rates</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('rooms.index', ['available_only' => 1]) }}" class="btn btn-light btn-lg">Explore offers</a>
            </div>
        </div>
    </section>

    <section class="home-location-panel p-4 p-lg-5 mt-5" aria-labelledby="homeLocationTitle">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <span class="home-location-icon mb-3"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                <p class="ta-eyebrow mb-1">Location</p>
                <h2 id="homeLocationTitle">Stay Close to What Matters</h2>
                <p class="text-secondary mb-0">Enjoy convenient access to dining, local attractions, everyday essentials, and transport connections. Confirmed guests receive complete arrival directions with their reservation.</p>
                <span class="home-location-note"><i class="bi bi-signpost-split"></i> Arrival directions included after confirmation</span>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    @foreach([
                        ['icon' => 'bi-cup-hot', 'title' => 'Local dining', 'text' => 'Restaurants and cafés'],
                        ['icon' => 'bi-camera', 'title' => 'Attractions', 'text' => 'Popular local experiences'],
                        ['icon' => 'bi-bag', 'title' => 'Essentials', 'text' => 'Shopping and conveniences'],
                        ['icon' => 'bi-car-front', 'title' => 'Connections', 'text' => 'Accessible transport options'],
                    ] as $nearby)
                        <div class="col-sm-6">
                            <div class="home-nearby-card">
                                <i class="bi {{ $nearby['icon'] }} fs-4 text-secondary" aria-hidden="true"></i>
                                <h3 class="h6 mt-2 mb-1">{{ $nearby['title'] }}</h3>
                                <p class="small text-secondary mb-0">{{ $nearby['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="home-faq mt-5" aria-labelledby="homeFaqTitle">
        <div class="home-section-heading mb-3">
            <p class="ta-eyebrow mb-1">Good to Know</p>
            <h2 id="homeFaqTitle">Frequently Asked Questions</h2>
        </div>
        <div class="accordion" id="homeFaqAccordion">
            @foreach([
                ['What time are check-in and check-out?', 'Standard check-in begins at 2:00 PM and check-out is at 12:00 PM.'],
                ['How do I know if a room is available?', 'Choose your check-in and check-out dates in the availability search. The system checks those dates before you continue booking.'],
                ['What payment options are available?', 'The reservation checkout displays the currently supported cash and online payment options. Online payments require a valid reference and payment proof.'],
                ['Can I change or cancel my reservation?', 'Eligible reservations can be cancelled or submitted for a schedule change from your booking details page. Conditions depend on the booking and payment status.'],
                ['Can I request an extra bed?', 'Yes. Extra bedding can be requested when available and may add a nightly surcharge to the booking total.'],
            ] as $index => [$question, $answer])
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#homeFaq{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="homeFaq{{ $index }}">
                            {{ $question }}
                        </button>
                    </h3>
                    <div id="homeFaq{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#homeFaqAccordion">
                        <div class="accordion-body text-secondary">{{ $answer }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="home-faq-footer mt-3">
            <div>
                <strong class="d-block">Ready to find your room?</strong>
                <span class="small text-white-50">Check live dates, rates, and availability.</span>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-light flex-shrink-0">Browse rooms</a>
        </div>
    </section>

    <section class="soft-card p-3 p-lg-4 mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <p class="ta-eyebrow mb-1">How It Works</p>
                <h2 class="mb-0">Fast Booking in 3 Steps</h2>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-sm btn-ta">Start Booking</a>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">1</span>
                    <h3 class="h5 mb-1">Choose your stay</h3>
                    <p class="mb-0 text-secondary small">Select your dates and preferred room type or view.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">2</span>
                    <h3 class="h5 mb-1">Compare top options</h3>
                    <p class="mb-0 text-secondary small">Review rates, amenities, and availability before checkout.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">3</span>
                    <h3 class="h5 mb-1">Confirm your stay</h3>
                    <p class="mb-0 text-secondary small">Finish payment and receive your booking details immediately.</p>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkIn = document.getElementById('heroCheckIn');
            const checkOut = document.getElementById('heroCheckOut');

            if (!checkIn || !checkOut) {
                return;
            }

            const updateDateDisplay = (input) => {
                input.closest('.hero-date-field')?.classList.toggle('has-value', Boolean(input.value));
            };

            const nextDate = (dateString) => {
                const date = new Date(`${dateString}T00:00:00`);
                date.setDate(date.getDate() + 1);

                return [
                    date.getFullYear(),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0'),
                ].join('-');
            };

            const updateCheckout = () => {
                if (!checkIn.value) {
                    return;
                }

                const minimumCheckout = nextDate(checkIn.value);
                checkOut.min = minimumCheckout;

                if (checkOut.value && checkOut.value < minimumCheckout) {
                    checkOut.value = '';
                }

                updateDateDisplay(checkOut);
            };

            checkIn.addEventListener('change', () => {
                updateDateDisplay(checkIn);
                updateCheckout();
            });
            checkOut.addEventListener('change', () => updateDateDisplay(checkOut));
            updateDateDisplay(checkIn);
            updateDateDisplay(checkOut);
            updateCheckout();
        });
    </script>
@endpush
